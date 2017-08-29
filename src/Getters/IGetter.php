<?php
namespace TwinePM\Getters;

interface IGetter {
    public static function get(array $context = null);
}