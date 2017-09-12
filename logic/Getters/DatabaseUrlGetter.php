<?php
namespace TwinePM\Getters;

class DatabaseUrlGetter implements IGetter {
    public static function get(array $context = null): string {
        return getenv("DATABASE_URL");
    }
}