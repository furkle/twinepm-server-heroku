<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Searches\Search;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class PackagesSearchEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $db = $container->get(PDO::class);
        $params = $request->getQueryParams();
        $query = isset($params["query"]) ? $params["query"] : null;
        unset($params["query"]);
        $search = new Search($params, $db);
        $queryType = "packages";
        return $search->query($queryType, $query);
    }
}