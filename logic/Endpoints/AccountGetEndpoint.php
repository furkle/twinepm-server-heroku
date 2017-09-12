<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\SqlAbstractions\Accounts\Account;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class AccountGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container = null): Responses\IResponse
    {
        $token = $request->getHeader("Authorize")[0];
        $db = $container->get(PDO::class);
        $accountResponse = Account::getFromToken($token, $db);
        if ($accountResponse->isError()) {
            return static::convertServerErrorToClientError($accountResponse);
        }

        $response = new Responses\Response();
        $response->account = $accountResponse->account;
        return $response;
    }
}