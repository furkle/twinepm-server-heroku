<?php
class AccountCreateSourceValidator {
    function __invoke(array $source): void {
        $email = isset($source["email"]) ? $source["email"] : null;
        if (!isset($email)) {
            $errorCode = "EmailInvalid";
            throw new InvalidRequestFieldException($errorCode);
        }

        $name = isset($source["name"]) ? $source["name"] : null;

        $password = isset($source["password"]) ?
            $source["password"] : null;
        if (!$password) {
            $errorCode = "PasswordInvalid";
            throw new InvalidRequestFieldException($errorCode);
        }
    }
}