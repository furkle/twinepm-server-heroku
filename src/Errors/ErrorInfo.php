<?php
namespace TwinePM\Errors;

class ErrorInfo {
    public static function get(string $name): ?array {
        $contents = null;
        try {
            $contents = file_get_contents(__DIR__ . "/definitions/$name.json");
        } catch (Exception $e) { /* no-op */ }

        $yesAssoc = true;
        $error = json_decode($contents, $yesAssoc);
        if (!$error) {
            $error = [];
        }

        $error["name"] = $name;
        $error["status"] = isset($error["status"]) ? $error["status"] : 500;
        $error["logger"] = isset($error["logger"]) ?
            $error["logger"] : "GenericErrorLogger";
        $error["message"] = isset($error["message"]) ?
            $error["message"] : "No error definition.";

        return $error;
    }
}