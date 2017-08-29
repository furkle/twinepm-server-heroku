<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
interface IEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse;

    public static function convertServerErrorToClientError(
        Responses\IResponse $serverError): Responses\ErrorResponse;

    public static function getOptionsObject(): array;
}