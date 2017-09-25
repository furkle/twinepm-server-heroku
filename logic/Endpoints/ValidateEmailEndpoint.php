<?php
namespace TwinePM\Endpoints;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
use TwinePM\Exceptions\PermissionDeniedException;
use TwinePM\Exceptions\PersistenceFailedException;
use TwinePM\Exceptions\UserRequestFieldInvalidException;
class ValidateEmailEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $params = $request->getQueryParams();
        if (!array_key_exists("request", $params)) {
            $errorCode = "RequestInvalid";
            throw new UserRequestFieldInvalidException($errorCode);
        }

        $encryptedRequest = $params["request"];
        $decryptedRequest = $decrypt($encryptedRequest);
        $yesAssoc = true;
        $request = json_decode($decryptedRequest, $yesAssoc);
        $request = $container->get("filterId")($idStr);
        $getFromPrimaryKey = $container->get("getAbstractionFromPrimaryKey");
        $emailValidation = $getFromPrimaryKey($sqlAbstractionType, $requestId);
        $generateHmac = $container->("generateHmac");
        $serverHmac = $emailValidation->getRequestHmac();
        $clientHmac = $generateHmac($encryptedRequest);
        if ($serverHmac !== $clientHmac) {
            $errorCode = "InvalidClientHmac";
            throw new PermissionDeniedException($errorCode);
        }

        /* Clear all unclaimed reservations before checking. */
        $container->get("reapUnclaimedReservations")();

        $db->beginTransaction();

        $emailValidation->deleteFromDatabase();

        $stmt = $db->prepare(
            "UPDATE passwords " .
            "SET validated = 1 " .
            "WHERE id = :id");
        
        $sqlParams = [ ":id" => $userId, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $db->rollBack();

            $errorCode = "PasswordsUpdateValidationQueryFailed";
            throw new PersistenceFailedException($errorCode);
        }

        $db->commit();

        $response = $container->get("response");
        $response->templateVars = [
            "userId" => $userId,
        ];

        return $response;
    }
}