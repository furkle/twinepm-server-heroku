<?php
namespace TwinePM\Getters;

class CacheServerUrlGetter {
    function __invoke(): string {
        return getenv("REDIS_URL");
    }
}