<?php
namespace TwinePM\Getters;

class SessionIdGetter implements IGetter {
    public static function get(array $context = null): string {
        $length = 64;
        if (isset($context["length"]) and
            (ctype_digit($context["length"]) or
                is_integer($context["length"])))
        {
            $length = (int)$context["length"];
        }

        return base64_encode(random_bytes($length));
    }
}