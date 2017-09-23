<?php
namespace TwinePM\Getters;

use Defuse\Crypto;
use Defuse\Crypto\Key;
use TwinePM\ServiceProviders\EndpointServiceProvider;
use TwinePM\ServiceProviders\FilterServiceProvider;
use TwinePM\ServiceProviders\GetterServiceProvider;
use TwinePM\ServiceProviders\LoggerServiceProvider;
use TwinePM\ServiceProviders\PersisterServiceProvider;
use TwinePM\ServiceProviders\SearchServiceProvider;
use TwinePM\ServiceProviders\SorterServiceProvider;
use TwinePM\ServiceProviders\SqlAbstractionServiceProvider;
use TwinePM\ServiceProviders\TransformerServiceProvider;
use TwinePM\ServiceProviders\ValidatorServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Container;
class TwinePmContainerGetter {
    function __invoke(
        ServerRequestInterface $request,
        array $settings): Container
    {
        $container = new Container();

        $container["decrypt"] = function() {
            $key = $this->get("key");
            return function(string $cipherText) use ($key) {
                return Crypto::decrypt($cipherText, $key);
            };
        };

        $container["encrypt"] = function() {
            $key = $this->get("key");
            return function(string $plainText) use ($key) {
                return Crypto::encrypt($plainText, $key);
            };
        };

        $container["generateKey"] = function() {
            return function() {
                return Key::createNewRandomKey();
            };
        };

        $container["generateHmac"] = function() {
            $key = $this->get("key");
            return function(string $message) use ($key) {
                $algo = "sha256";
                $keyStr = $key->saveToAsciiSafeString();
                return hash_hmac($algo, $message, $keyStr)
            };
        };

        $container["makeDsn"] = function() {
            return function(
                string $driver,
                string $host,
                string $port,
                string $dbName,
                string $charset): string
            {
                if ($driver !== "pgsql" and $charset) {
                    return sprintf("%s:host=%s;port=%s;dbname=%s;charset=%s;",
                        $driver,
                        $host,
                        $port,
                        $dbName,
                        $charset);
                } else {
                    return sprintf("%s:host=%s;port=%s;dbname=%s;",
                        $driver,
                        $host,
                        $port,
                        $dbName);
                }
            }
        };

        $container["request"] = $request;
        
        $container["settings"] = $settings;
        
        $container["successObject"] = [
            "status" => 200,
        ];

        $container->register(new EndpointServiceProvider());
        $container->register(new FilterServiceProvider());
        $container->register(new GetterServiceProvider());
        $container->register(new LoggerServiceProvider());
        $container->register(new PersisterServiceProvider());
        $container->register(new SearchServiceProvider());
        $container->register(new SorterServiceProvider());
        $container->register(new SqlAbstractionServiceProvider());
        $container->register(new TransformerServiceProvider());
        $container->register(new ValidatorServiceProvider());

        return $container;
    }
}