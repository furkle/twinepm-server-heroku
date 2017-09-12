<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Filters\IdFilter;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
use \Exception;
class ValidateEmailEndpoint extends AbstractEndpoint {
    function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $params = $request->getQueryParams();
        $id = isset($params["id"]) ? $params["id"] : null;
        if (!array_key_exists("id", $params)) {

        }

        $filterResponse = IdFilter::filter($id);
        if ($filterResponse->isError()) {
            return static::convertServerErrorToClientError($filterResponse);
        }

        $id = $filterResponse->filtered;

        $db = $container->get(PDO::class);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* Clear all unclaimed reservations before checking. */
        $reapResult = Miscellaneous::reapUnclaimedReservations($db);
        $db->beginTransaction();
        $stmt = $db->prepare(
            "DELETE " .
            "FROM email_validation " .
            "WHERE id = :id AND token = :token");
        try {
            $sqlParams = [
                ":id" => $userId,
                ":token" => $source["emailToken"],
            ];

            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $db->rollBack();

            $errorCode = "EmailValidationDeleteFailed";
            $errorData = [ "exception" => (string)$e, ];
            $response = new Responses\ErrorResponse($errorCode, $errorData);
            return $response;
        }

        if ($stmt->rowCount() === 0) {
            $db->rollBack();

            $errorCode = "EmailValidationEndpointRecordNotFound";
            $response = new Responses\ErrorResponse($errorCode);
            return $response;
        }

        $stmt = $db->prepare(
            "UPDATE passwords " .
            "SET validated = 1 " .
            "WHERE id = :id");
        
        $sqlParams = [ ":id" => $userId, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $db->rollBack();

            $errorCode = "ValidateEmailEndpointQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $response = new Responses\ErrorResponse($errorCode, $errorData);
            return $response;
        }

        $db->commit();

        $response = new Responses\Response();
        $response->plainMessage = "Thank you for validating your e-mail. You " .
            "may now log into TwinePM.<br>" .
            "If you registered without a username, use the ID: " .
            "<b>$userId/b>.<br>" .
            "<a href='https://furkleindustries.com/twinepm/login'>Log in</a>";
        return $response;
    }
}