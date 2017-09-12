<?php
namespace TwinePM\Getters;

class DatabaseArgsGetter implements IGetter {
    public static function get(array $context = null): array {
        $url = DatabaseUrlGetter::get();
        return parse_url($url);
    }
}
