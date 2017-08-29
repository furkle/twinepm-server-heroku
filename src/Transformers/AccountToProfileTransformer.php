<?php
namespace TwinePM\Transformers;

use \TwinePM\Responses;
class AccountToProfileTransformer implements ITransformer {
    public static function transform(
        $value,
        array $context = null): Responses\IResponse
    {
        if (!($value instanceof Account)) {
            $errorCode = "AccountToProfileTransformerValueInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $profile = [];
        $accountArr = $account->toArray();
        foreach ($accountArr as $key, $value) {
            if ($key === "nameVisible" and $value) {
                $profile["name"] = $value->getName();
            } else if ($key === "dateCreatedVisible" and $value) {
                $profile["dateCreated"] = $value->getDateCreated();
            } else if ($key === "emailVisible") {
                $profile["email"] = $value->getEmail();
            } else if ($key !== "name" and
                $key !== "dateCreated" and
                $key !== "email")
            {
                $profile[$key] = $value;
            }
        }

        $success = new Responses\Response();
        $success->transformed = $profile;
        return $response;
    }
}