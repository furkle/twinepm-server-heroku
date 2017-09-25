<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
class AuthorizeTokenGetter {
    function __invoke(ServerRequestInterface $request): ResponseInterface {
        return $request->getHeader("Authorize")[0];
    }
}