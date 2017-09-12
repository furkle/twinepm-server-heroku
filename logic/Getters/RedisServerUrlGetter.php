<?php
namespace TwinePM\Getters;

class RedisServerUrlGetter implements IGetter {
    public static function get(array $context = null): string {
        return getenv("REDIS_URL");
    }
}