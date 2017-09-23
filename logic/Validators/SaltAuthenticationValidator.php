<?php
namespace TwinePM\Validators;

use TwinePM\Getters;
use TwinePM\Responses;
use Defuse\Crypto\Key;
use Psr\Container\ContainerInterface as IContainer;
use Psr\Container\NotFoundExceptionInterface as INotFoundException;
class SaltAuthenticationValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $container = isset($context["container"]);
        if (isset($context["container"]) and
            $context["container"] instanceof IContainer)
        {
            $container = $context["container"];
        }

        try {
            $getErrorResponse = $container->get("getErrorResponse");
        } catch (INotFoundException $e) {
            /* TODO: add logging/handling. */
            return;
        }

        $key = null;
        if (isset($context["key"]) and $context["key"] instanceof Key) {
            $key = $context["key"];
        } else if ($container->has("saltKey")) {
            $key = $container->get("saltKey");
        } else {
            $key = SaltKeyGetter::get();
        }

        $keyStr = $key->saveToAsciiSafeString();

        $message = mb_substr($value, 64, null, "8bit");
        $validMAC = hash_hmac("sha256", $message, $keyStr);
        $msgMAC = mb_substr($value, 0, 64, "8bit");
        if (hash_equals($validMAC, $msgMAC)) {
            return $container->get("getSuccessResponse")();
        } else {
            $errorCode = "SaltAuthenticationValidatorNonEqualHash";
            return ($errorCode);
        }
    }
}