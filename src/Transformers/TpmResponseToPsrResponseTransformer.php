<?php
namespace TwinePM\Transformers;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \Psr\Http\Message\ResponseInterface as IPsrResponse;
class TpmResponseToPsrResponseTransformer implements ITransformer {
    public static function transform(
        $value,
        array $context = null): Responses\IResponse
    {
        if (!($value instanceof Responses\IResponse)) {
            $errorCode = "TpmResponseToPsrResponseTransformerValueInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $response = isset($context["psrResponse"]) ?
            $context["psrResponse"] : null;
        if (!($response instanceof IPsrResponse)) {
            $errorCode =
                "TpmResponseToPsrResponseTransformerPsrResponseInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $error = $value->getError();
        $templateVarsSet = isset($value->templateVars);
        if ($error) {
            $output = $error->getOutput();
            $hName = "X-TwinePM-Error-Code";
            $response = $response->withHeader($hName, $error->errorCode);
            if (!$templateVarsSet) {
                $response = $response->withJson($output, $error->status);
            }
        } else if (!$templateVarsSet) {
            /* Do not set json type for templated HTML endpoints. */
            $output = $value->getOutput();
            $response = $response->withJson($output, $value->status); 
        } else {
            $response = $response
                ->withHeader("X-Frame-Options", "DENY")
                ->withStatus($value->status);
        }

        $success = new Responses\Response();
        $success->transformed = $response;
        return $success;
    }
}