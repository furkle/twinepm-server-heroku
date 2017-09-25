<?php
namespace TwinePM\Getters;

use Defuse\Crypto\Key;
use Psr\Container\ContainerInterface as IContainer;
class RequestIdGetter {
    function __invoke(int $length = 128): string {
        return base64_encode(random_bytes($length));
    }
}