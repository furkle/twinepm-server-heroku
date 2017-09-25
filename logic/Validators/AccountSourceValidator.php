<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\SqlAbstractions\Accounts\Account;
class AccountSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (isset($value["id"])) {
            $validationResponse = IdValidator::validate($value);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        } else {
            $errorCode = "AccountSourceValidatorIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validationResponse = NameValidator::validate($value);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $nameVisible = isset($value["nameVisible"]) ?
            $value["nameVisible"] : null;
        if (isset($value["nameVisible"]) and
            gettype($nameVisible) !== "boolean")
        {
            $errorCode = "AccountSourceValidatorNameVisibleInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $description = isset($value["description"]) ?
            $value["description"] : null;
        if (isset($value["description"]) and
            gettype($description) !== "string")
        {
            $errorCode = "AccountSourceValidatorDescriptionInvalid";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $timeCreated = isset($value["timeCreated"]) ?
            $value["timeCreated"] : null;
        if (isset($value["timeCreated"]) and
            (gettype($timeCreated) !== "integer" or $timeCreated <= 0))
        {
            $errorCode = "AccountSourceValidatorTimeCreatedInvalid";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $timeCreatedVisible = isset($value["timeCreatedVisible"]) ?
            $value["timeCreatedVisible"] : null;
        if (isset($value["timeCreatedVisible"]) and
            gettype($timeCreatedVisible) !== "boolean")
        {
            $errorCode = "AccountSourceValidatorTimeCreatedVisibleInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $email = isset($value["email"]) ? $value["email"] : null;
        if (isset($value["email"]) and gettype($email) !== "string") {
            $errorCode = "AccountSourceValidatorEmailInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $emailVisible = isset($value["emailVisible"]) ?
            $value["emailVisible"] : null;
        if (isset($value["emailVisible"]) and
            gettype($emailVisible) !== "boolean")
        {
            $errorCode = "AccountSourceValidatorEmailVisibleInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $dateStyle = isset($value["dateStyle"]) ? $value["dateStyle"] : null;
        $yesStrict = true;
        if (isset($value["dateStyle"])) {
            $dateStyles = Account::DATE_STYLES;
            if (gettype($dateStyle) !== "string") {
                $errorCode = "AccountSourceValidatorDateStyleInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!in_array($dateStyle, $dateStyles, $yesStrict)) {
                $errorCode = "AccountSourceValidatorDateStyleInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        }

        $timeStyle = isset($value["timeStyle"]) ? $value["timeStyle"] : null;
        if (isset($value["timeStyle"])) {
            $timeStyles = Account::TIME_STYLES;
            if (gettype($timeStyle) !== "string") {
                $errorCode = "AccountSourceValidatorTimeStyleInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!in_array($timeStyle, $timeStyles, $yesStrict)) {
                $errorCode = "AccountSourceValidatorTimeStyleUnrecognized";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        }

        $homepage = isset($value["homepage"]) ? $value["homepage"] : null;
        if (isset($value["homepage"]) and gettype($homepage) !== "string") {
            $errorCode = "AccountSourceValidatorHomepageInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $response = new Responses\Response();
        return $response;
    }
}