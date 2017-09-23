<?php
namespace TwinePM\Getters;

class DatabaseArgsGetter {
    function __invoke(string $url): array {
        return parse_url($url);
    }
}