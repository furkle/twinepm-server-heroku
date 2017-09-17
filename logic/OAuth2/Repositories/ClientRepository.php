<?php
namespace TwinePM\OAuth2\Repositories;

use \League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use \TwinePM\OAuth2\Entities\ClientEntity;
class ClientRepository implements ClientRepositoryInterface {
    private $clients = [];

    public function __construct() {
        $clientDir = __DIR__ . "/../clients/";
        $entries = scandir($clientDir);
        foreach ($entries as $entry) {
            if (is_file($clientDir . $entry)) {
                $contents = file_get_contents($clientDir . $entry);
                $yesAssoc = true;
                $clientObject = json_decode($contents, $yesAssoc);
                if (gettype($clientObject) === "array") {
                    $identifier = $entry;
                    $dotPos = strrpos($entry, ".");
                    if ($dotPos !== false) {
                        $identifier = substr($entry, $dotPos);
                    }

                    $this->clients[$identifier] = $clientObject;
                }
            }
        }
    }

    public function getClients(): array {
        $clients = $this->clients;
        foreach ($clients as $key => $value) {
            unset($clients[$key]["secret"]);
        }

        return $clients;
    }

    public function getClientEntity(
        $clientIdentifier,
        $grantType,
        $clientSecret = null,
        $mustValidateSecret = true)
    {
        $clients = $this->clients;

        /* Check if client is registered. */
        if (!isset($this->clients[$clientIdentifier])) {
            return;
        }

        $client = $clients[$clientIdentifier];
        if ($mustValidateSecret and $client["isConfidential"]) {
            $realClientSecret = $clients[$clientIdentifier]["secret"];
            if ($clientSecret !== $realClientSecret) {
                return;
            }
        }

        $clientEntity = new ClientEntity();
        $clientEntity->setIdentifier($clientIdentifier);
        $clientEntity->setName($client["name"]);

        $redirectUri = $client["domain"] . "/" . $client["redirectPath"];
        $clientEntity->setRedirectUri($redirectUri);
        return $clientEntity;
    }
}