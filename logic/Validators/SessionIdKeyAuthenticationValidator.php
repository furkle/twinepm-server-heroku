<?php
namespace TwinePM\Validators;

use TwinePM\Responses;
use Defuse\Crypto\Key;
use Psr\Container\ContainerInterface as IContainer;
class SessionIdAuthenticationValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $container = null;
        if (isset($context["container"]) and
            $context["container"] instanceof IContainer)
        {
            $container = $context["container"];
        }

        $algo = "sha256";
        $message = mb_substr($value, 64, null, "8bit");
        $key = null;
        if (isset($context["key"]) and $context["key"] instanceof Key) {
            $key = $context["key"];
        } else if ($container and $container->has("saltKey")) {
            $key = $container->get("saltKey");
        } else {
            $key = SessionIdKeyGetter::get();
        }

        $keyStr = $key->saveToAsciiSafeString();

        $validMAC = hash_hmac($algo, $message, $keyStr);
        $msgMAC = mb_substr($value, 0, 64, "8bit");
        if (hash_equals($validMAC, $msgMAC)) {
            return $container->get("getSuccessResponse")();
        } else {
            $errorCode = "SaltAuthenticationValidatorNonEqualHash";
            return $container->get("getErrorResponse")($errorCode);
        }
    }
}