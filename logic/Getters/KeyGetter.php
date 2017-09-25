<?php
namespace TwinePM\Getters;

use Defuse\Crypto\Key;
use Exception;
class RequestIdKeyGetter implements IGetter {
    function __invoke(
        callable $initKeyFromString,
        callable $getFromFile,
        callable $writeToFile,
        callable $generateKey): Key
    {
        $filepath = __DIR__ . "/../crypto/requestIdKey";
        $contents = null;
        $loaded = false;
        try {
            $contents = $getFromFile($filepath);
            $loaded = true;
        } catch (Exception $e) {
            $key = $generateKey();

            try {
                $writeToFile($filepath, $key->saveToAsciiSafeString());
            } catch (Exception $e) {
                /* TODO: add logs here. */
                return;
            }
        }

        if ($loaded) {
            $key = $initKeyFromString($contents);
        }

        return $key;
    }
}