<?php
namespace TwinePM\Getters;

use Defuse\Crypto\Key;
class SaltGetter {
    function __invoke(int $length = 64): string {
        return base64_encode(random_bytes($length));
    }
}