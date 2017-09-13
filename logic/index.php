<?php
declare(strict_types = 1);

namespace TwinePM;

require_once __DIR__ . "/vendor/autoload.php";

use \TwinePM\Getters;
use \TwinePM\Endpoints;
use \TwinePM\Responses;
use \TwinePM\Loggers\AccessLogger;
use \TwinePM\Loggers\LoggerRouter;
use \TwinePM\Transformers\TpmResponseToPsrResponseTransformer;
use \TwinePM\OAuth2\Repositories\AccessTokenRepository;
use \TwinePM\OAuth2\Repositories\AuthCodeRepository;
use \TwinePM\OAuth2\Repositories\ClientRepository;
use \TwinePM\OAuth2\Repositories\ScopeRepository;
use \TwinePM\OAuth2\Entities\UserEntity;
use \TwinePM\OAuth2\Entities\ClientEntity;
use \League\OAuth2\Server\Grant\ImplicitGrant;
use \Slim\App;
use \Slim\Views\Twig;
use \Slim\Views\TwigExtension;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Predis\Client as RedisClient;
use \League\OAuth2\Server\AuthorizationServer;
use \League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use \League\OAuth2\Server\Exception\OAuthServerException;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \PDO;
use \Exception;
use \Closure;
$app = new App([
    "settings" => [
        "displayErrorDetails" => true,
    ],

    PDO::class => function(): PDO {
        return Getters\DatabaseGetter::get();
    },

    AuthorizationServer::class => function(): AuthorizationServer {
        return Getters\OAuth2AuthorizationServerGetter::get();
    },

    RedisClient::class => function(): RedisClient {
        return Getters\RedisServerGetter::get();
    },

    "processTpmResponse" => function(): Closure {
        return function(
            Responses\IResponse $tpmResponse,
            Response $psrResponse): Response
        {
            $src = $tpmResponse;
            $ctx = [ "psrResponse" => $psrResponse, ];
            $transformResponse =
                TpmResponseToPsrResponseTransformer::transform($src, $ctx);

            if ($transformResponse->isError()) {
                $src = $transformResponse;
                $transformErrorResponse =
                    TpmResponseToPsrResponseTransformer::transform($src, $ctx);
                if ($transformErrorResponse->isError()) {
                    $arr = [ "error" => "Response transformer error."];
                    $status = 500;
                    return $res->withJson($arr, $status);
                }

                $res = $transformErrorResponse->transformed;
                return $res;
            }

            $res = $transformResponse->transformed;
            return $res;
        };
    },

    "processTemplateVars" => function(): Closure {
        return function(Responses\IResponse $tpmResponse): array {
            $templateVars = isset($tpmResponse->templateVars) ?
                $tpmResponse->templateVars : [];
            if ($tpmResponse->isError()) {
                $error = $tpmResponse->getError()->toArray();
                $status = $error->status;
                $reasons = Responses\Response::REASON_PHRASES;
                $error["reason"] = $reasons[$status] ?? "Invalid status.";
                $splitError = explode("_", $error["errorCode"]);
                $error["phrase"] = array_reduce($splitError, function($a, $b) {
                    return $a . " " . strtoupper($b[0]) . substr($b, 1);
                });

                $templateVars["error"] = $error;
            }

            return $templateVars;
        };
    },

    "processRestResponse" => function(): Closure {
        return function(
            Response $response,
            string $methods,
            array $client = null): Response
        {
            $res = $response->withHeader(
                "Access-Control-Allow-Methods",
                $methods);

            $origin = "*";
            if (isset($client["domain"])) {
                $origin = $client["domain"];
            }

            $res = $res->withHeader("Access-Control-Allow-Origin", $origin);
            return $res;
        };
    },
]);

$container = $app->getContainer();
$container[Twig::class] = function ($container) {
    $view = new Twig(__DIR__ . "/templates/", [
        'cache' => false,
    ]);
    
    /* Instantiate and add Slim specific extension. */
    $untrimmed = str_ireplace(
        "index.php",
        "",
        $container["request"]->getUri()->getBasePath());

    $basePath = rtrim($untrimmed, "/");
    $view->addExtension(new TwigExtension($container->get("router"), $basePath));

    return $view;
};

$accessLogger = new AccessLogger();
$loggerMiddleware = function (
    Request $req,
    Response $res,
    App $next) use ($accessLogger)
{
    $bodyParams = $req->getParsedBody() ?? [];
    $logArray = [
        "query" => $req->getQueryParams(),
        "body" => $bodyParams,
        "headers" => $req->getHeaders(),
        "server" => $req->getServerParams(),
    ];

    unset($logArray["query"]["password"]);
    unset($logArray["body"]["password"]);
    unset($logArray["server"]["SERVER_SOFTWARE"]);
    unset($logArray["server"]["SCRIPT_NAME"]);
    unset($logArray["server"]["DOCUMENT_ROOT"]);

    /* Deduplicate headers from server. */
    foreach ($logArray["server"] as $key => $value) {
        if (array_key_exists($key, $logArray["headers"])) {
            unset($logArray["server"][$key]);
        }
    }

    $accessLogger->log($logArray);

    $response = $next($req, $res);
    $errorCode = $response->getHeader("X-TwinePM-Error-Code");
    if ($errorCode) {
        LoggerRouter::route($errorCode[0]);
    }

    return $response;
};

$app->add($loggerMiddleware);

$root = function (Request $request, Response $response) {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\RootGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $filepath = "index.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, OPTIONS");
    }
};

$app->map([ "GET", "OPTIONS", ], "[/]", $root);

$accountCreation = function(Request $request, Response $response) {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\AccountCreationGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $filepath = "createAccount.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, OPTIONS");
    }
};

$app->map([ "GET", "OPTIONS", ], "/createAccount[/]", $accountCreation);

$login = function (Request $request, Response $response): Response {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\LoginGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $filepath = "login.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isPost()) {
        $container = $this;
        $tpmResponse = Endpoints\LoginPostEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        if ($res->getHeader("X-TwinePM-Error-Code")) {
            return $res;
        }

        return $response->withRedirect("options", 302);
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, POST, OPTIONS");
    }
};

$app->map([ "GET", "POST", "OPTIONS", ], "/login[/]", $login);

$logout = function (Request $request, Response $response): Response {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\LogoutGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $filepath = "logout.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isPost()) {
        $container = $this;
        $tpmResponse = Endpoints\LogoutPostEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        if ($res->getHeader("X-TwinePM-Error-Code")) {
            return $res;
        }

        return $res->withRedirect("/", 302);
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, POST, OPTIONS");
    }
};

$app->map([ "GET", "POST", "OPTIONS", ], "/logout[/]", $logout);

$authorize = function (Request $request, Response $response): Response {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\AuthorizeGetEndpoint::execute(
            $request,
            $container);

        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $tpmResponse->templateVars = $templateVars;
        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $filepath = "authorize.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isPost()) {
        $domain = Getters\ServerDomainNameGetter::get();
        $container = $this;
        $tpmResponse = Endpoints\AuthorizePostEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        if ($res->getHeader("X-TwinePM-Error-Code")) {
            return $res;
        }

        $server = $this->get(AuthorizationServer::class);
        $redirect = $server->completeAuthorizationRequest(
            $tpmResponse->authRequest,
            $res);

        return $redirect;
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, POST, OPTIONS");
    }
};

$app->map([ "GET", "POST", "OPTIONS", ], "/authorize[/]", $authorize);

$unauthorize = function (Request $request, Response $response): Response {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\UnauthorizeGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $filepath = "unauthorize.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isPost()) {
        $container = $this;
        $tpmResponse = Endpoints\UnauthorizePostEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, POST, OPTIONS");
    }
};

$app->map([ "GET", "POST", "OPTIONS", ], "/unauthorize[/]", $unauthorize);

$clients = function(Request $request, Response $response) {
    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\ClientsGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        $templateVars = $this->get("processTemplateVars")($tpmResponse);
        $filepath = "clients.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, POST, OPTIONS");
    }
};

$app->map([ "GET", "OPTIONS", ], "/clients[/]", $clients);

$options = function(Request $request, Response $response) {
    if ($request->isGet()) {
        $res = $response->withHeader("X-Frame-Options", "DENY");
        $container = $this;
        $tpmResponse = Endpoints\ServerUserOptionsGetEndpoint::execute(
            $request,
            $container);

        $templateVars = isset($tpmResponse->templateVars) ?
            $tpmResponse->templateVars : [];

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        if ($res->getHeader("X-TwinePM-Error-Code")) {
            return $res;
        }

        $filepath = "options.html.twig";
        return $this->get(Twig::class)->render($res, $filepath, $templateVars);
    } else if ($request->isOptions()) {
        return $response->withHeader("Allow", "GET, POST, OPTIONS");
    }
};

$app->map([ "GET", "OPTIONS", ], "/options[/]", $options);

$account = function(Request $request, Response $response): Response {
    $res = $response->withHeader(
        "Access-Control-Allow-Methods",
        "GET, POST, PUT, DELETE, OPTIONS");

    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\AccountGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isPost()) {
        $container = $this;
        $tpmResponse = Endpoints\AccountCreationEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isPut()) {
        $container = $this;
        $tpmResponse = Endpoints\AccountUpdateEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isDelete()) {
        $container = $this;
        $tpmResponse = Endpoints\AccountDeleteEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isOptions()) {
        $options = [
            "GET" => Endpoints\AccountGetEndpoint::getOptionsObject(),
            "POST" => Endpoints\AccountCreationEndpoint::getOptionsObject(),
            "PUT" => Endpoints\AccountUpdateEndpoint::getOptionsObject(),
            "DELETE" => Endpoints\AccountDeleteEndpoint::getOptionsObject(),
        ];

        return $res
            ->withHeader("Allow", "GET, POST, PUT, DELETE, OPTIONS")
            ->withJson($options);
    }
};

$app->map(
    [
        "GET",
        "POST",
        "PUT",
        "DELETE",
        "OPTIONS",
    ],
    "/account[/]",
    $account);

$profile = function (Request $request, Response $response): Response {
    $res = $response->withHeader(
        "Access-Control-Allow-Methods",
        "GET, OPTIONS");

    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\ProfileGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isOptions()) {
        $options = [
            "GET" => Endpoints\ProfileGetEndpoint::getOptionsObject(),
        ];

        return $res->withHeader("Allow", "GET, OPTIONS")->withJson($options);
    }
};

$app->map([ "GET", "OPTIONS", ], "/profile[/]", $profile);

$package = function (Request $request, Response $response): Response {
    $res = $response->withHeader(
        "Access-Control-Allow-Methods",
        "GET, POST, PUT, DELETE, OPTIONS");

    if ($request->isGet()) {
        $container = $this;
        $tpmResponse = Endpoints\PackageGetEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isPost()) {
        $container = $this;
        $tpmResponse = Endpoints\PackageCreationEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isPut()) {
        $container = $this;
        $tpmResponse = Endpoints\PackageUpdateEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isDelete()) {
        $container = $this;
        $tpmResponse = Endpoints\PackageDeleteEndpoint::execute(
            $request,
            $container);

        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isOptions()) {
        $options = [
            "GET" => Endpoints\PackageGetEndpoint::getOptionsObject(),
            "POST" => Endpoints\PackageCreationEndpoint::getOptionsObject(),
            "PUT" => Endpoints\PackageUpdateEndpoint::getOptionsObject(),
            "DELETE" => Endpoints\PackageDeleteEndpoint::getOptionsObject(),
        ];

        return $res
            ->withHeader("Allow", "GET, POST, PUT, DELETE, OPTIONS")
            ->withJson($options);
    }
};

$app->map(
    [
        "GET",
        "POST",
        "PUT",
        "DELETE",
        "OPTIONS",
    ],
    "/package[/]",
    $package);

$version = function (Request $request, Response $response): Response {
    $container = $this;
    $res = $this->get("processRestResponse")($response);

    if ($request->isGet()) {
        $tpmResponse = Endpoints\VersionGetEndpoint::execute(
            $request,
            $container);
        
        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isPost()) {
        $tpmResponse = Endpoints\VersionCreationEndpoint::execute(
            $request,
            $container);
        
        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isDelete()) {
        $tpmResponse = Endpoints\VersionDeleteEndpoint::execute(
            $request,
            $container);
        
        $res = $this->get("processTpmResponse")($tpmResponse, $response);
        return $res;
    } else if ($request->isOptions()) {
        $options = [
            "GET" => Endpoints\AccountGetEndpoint::getOptionsObject(),
            "POST" => Endpoints\AccountCreationEndpoint::getOptionsObject(),
            "DELETE" => Endpoints\AccountDeleteEndpoint::getOptionsObject(),
        ];

        return $res
            ->withHeader("Allow", "GET, POST, PUT, DELETE, OPTIONS")
            ->withJson($options);
    }
};

$app->map(
    [
        "GET",
        "POST",
        "PUT",
        "DELETE",
        "OPTIONS",
    ],
    "/version[/]",
    $version);

$app->run();