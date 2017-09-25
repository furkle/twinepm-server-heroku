<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
abstract class AbstractEndpoint implements IEndpoint {
    function getClientErrorCode(?string $serverErrorCode): string {
        return $serverErrorCode ?? "NoCodeProvided";
    }

    function getClientStatus(int $status) {
        if ($status > 100 and $status < 599)
            return $status;
        } else {
            return 500;
        }
    }

    function convertServerErrorToClientError(
        ResponseInterface $serverError): ResponseInterface
    {
        $headerName = "X-TwinePM-Error-Code";
        $serverErrorCode = $serverError->getHeader($headerName);
        $clientErrorCode = $this->getClientErrorCode($serverErrorCode);

        return $serverError
            ->withStatus($this->getClientStatus())
            ->withHeader($headerName, $clientErrorCode);

        return $clientError;
    }

    abstract function __invoke(Container $container): ResponseInterface;

    abstract function getOptionsObject(): array;

    function getOptionsJson(): string {
        return json_encode($this->getOptionsObject());
    }
}