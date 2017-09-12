<?php
namespace TwinePM\Getters;

class ServerDomainNameGetter implements IGetter {
    public static function get(array $context = null): string {
        return getenv("SERVER_URL");
    }
}