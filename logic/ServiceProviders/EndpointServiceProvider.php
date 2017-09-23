<?php
use Slim\Container;
use Slim\DefaultServicesProvider;
use TwinePM\Endpoints\AccountCreationEndpoint;
use TwinePM\Endpoints\AccountReadEndpoint;
use TwinePM\Endpoints\AccountUpdateEndpoint;
use TwinePM\Endpoints\AccountDeletionEndpoint;
use TwinePM\Endpoints\AuthorizationCreateEndpoint;
use TwinePM\Endpoints\AuthorizationHtmlEndpoint;
use TwinePM\Endpoints\ClientsReadEndpoint;
use TwinePM\Endpoints\FileContentsGetter;
use TwinePM\Endpoints\RequestIdGetter;
use TwinePM\Endpoints\RequestIdKeyGetter;
use TwinePM\Endpoints\SaltGetter;
use TwinePM\Endpoints\SaltKeyGetter;
use TwinePM\Endpoints\ServerDomainNameGetter;

class EndpointServiceProvider extends DefaultServicesProvider {
    function register(Container $container) {
        
    }
}