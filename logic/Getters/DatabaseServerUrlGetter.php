<?php
namespace TwinePM\Getters;

class DatabaseServerUrlGetter {
    function __invoke(): string {
        return getenv("DATABASE_URL");
    }
}