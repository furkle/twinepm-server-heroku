<?php
use Slim\ContainerInterface;
use Slim\DefaultServicesProvider;
use TwinePM\Getters\AuthorizeTokenGetter;
use TwinePM\Getters\AuthorizationServerGetter;
use TwinePM\Getters\CacheClientGetter;
use TwinePM\Getters\CacheServerUrlGetter;
use TwinePM\Getters\DatabaseClientGetter;
use TwinePM\Getters\DatabaseClientArgsGetter;
use TwinePM\Getters\DatabaseServerUrlGetter;
use TwinePM\Getters\DsnGetter;
use TwinePM\Getters\FileContentsGetter;
use TwinePM\Getters\RequestIdGetter;
use TwinePM\Getters\RequestIdKeyGetter;
use TwinePM\Getters\SaltGetter;
use TwinePM\Getters\SaltKeyGetter;
use TwinePM\Getters\ServerDomainNameGetter;

class GetterServiceProvider extends DefaultServicesProvider {
    public function register(ContainerInterface $container) {
        $container["authorizeToken"] = function() {
            return AuthorizeTokenGetter($this->get("request"));
        };

        $container["authorizationServer"] = function() {
            return AuthorizationServerGetter();
        };

        $container["authorizedUser"] = function() {
            $token = $this->get("authorizeToken");
            $db = $this->get("databaseClient");
            return AuthorizedUserGetter($token, $db);
        };

        $container["cacheClient"] = function() {
            return CacheClientGetter($this->get("cacheServerUrl"));
        };

        $container["cacheServerUrl"] = function() {
            return CacheServerUrlGetter();
        };

        $container["databaseClient"] = function() {
            $dsn = $this->get("dsn");
            $dbArgs = $this->get("databaseArgs");
            $username = $dbArgs["user"];
            $password = $dbArgs["pass"];
            return DatabaseClientGetter($dsn, $username, $password);
        };

        $container["databaseClientArgs"] = function() {
            return DatabaseClientArgsGetter($this->get("databaseServerUrl"));
        };

        $key = "databaseClientWithExceptions";
        $container[$key] = $container->factory(function() {
            $client = $this->get("databaseClient");
            $errmodeKey = $database::ATTR_ERRMODE;
            $errmodeValue = $database::ERRMODE_EXCEPTION;
            $client->setAttribute($errmodeKey, $errmodeValue);
            return $client;
        });

        $container["databaseServerDsn"] = function() {
            $driver = "pgsql";
            $dbName = ltrim($dbArgs["path"], "/");
            $charset = "utf8";
            return $this->get("makeDsn")(
                $driver,
                $dbArgs["host"],
                $dbArgs["port"],
                $dbName,
                $charset);
        };

        $container["databaseServerUrl"] = function() {
            return DatabaseServerUrlGetter();
        };

        $container["dsn"] = function() {
            return DsnGetter($this->get("databaseClientArgs"));
        };

        $container["fileContentsGetter"] = function() {
            return new FileContentsGetter();
        };

        $container["loggedInUser"] = $container->factory(function() {
            $request = $this->get("request");
            $cache = $this->get("cacheClient");
            return LoggedInUserGetter($request, $cache);
        });

        $container["requestId"] = function() {
            return RequestIdGetter($this->get("key"));
        };

        $container["key"] = function() {
            return KeyGetter(
                $this->get("loadKeyFromString"),
                $this->get("fileContentsGetter"),
                $this->get("fileContentsPersister"),
                $this->get("generateKey"));
        };

        $container["salt"] = $container->factory(function() {
            return SaltGetter($this->get("key"));
        });

        $container["serverDomainName"] = function() {
            return ServerDomainNameGetter();
        };
    }
}