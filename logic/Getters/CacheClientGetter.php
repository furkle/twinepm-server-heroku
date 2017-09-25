<?php
namespace TwinePM\Getters;

use Predis\Client;
class CacheClientGetter {
    function __invoke(string $url): Client {
        return new Client($url);
    }
}