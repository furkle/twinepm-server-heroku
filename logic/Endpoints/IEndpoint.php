<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
interface IEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface;

    function getOptionsObject(): array;
}