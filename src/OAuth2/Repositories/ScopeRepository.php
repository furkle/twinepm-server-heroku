<?php
namespace TwinePM\OAuth2\Repositories;

use \League\OAuth2\Server\Entities\ClientEntityInterface;
use \League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use \TwinePM\Getters\OAuth2ScopesGetter;
use \TwinePM\OAuth2\Entities\ScopeEntity;
class ScopeRepository implements ScopeRepositoryInterface {
    const SCOPES = [
        "readAccount" => [
            "name" => "Read Account",
            "description" => "Get your account details, including your " .
                "e-mail.",
        ],

        "updateAccount" => [
            "name" => "Update Account",
            "description" => "Change your account details.",
        ],

        "deleteAccount" => [
            "name" => "Delete Account",
            "description" => "Permanently delete your account.",
        ],

        "createPackage" => [
            "name" => "Create Package",
            "description" => "Create new packages under your name/ID.",
        ],

        "updatePackage" => [
            "name" => "Update Package",
            "description" => "Change the details of your currently " .
                "existing packages.",
        ],

        "deletePackage" => [
            "name" => "Delete Package",
            "description" => "Remove your ownership of a package.",
        ],

        "createVersion" => [
            "name" => "Create Version",
            "description" => "Create new versions of your currently " .
                "existing packages.",
        ],

        "deleteVersion" => [
            "name" => "Delete Version",
            "description" => "Remove your ownership of a package.",
        ],

        "transferPackageOwnership" => [
            "name" => "Transfer Package Ownership",
            "description" => "Transfer the ownership of a package to " .
                "another user.",
        ],
    ];

    public function getScopeEntityByIdentifier(
        $scopeIdentifier): ?ScopeEntity
    {
        if (!array_key_exists($scopeIdentifier, static::SCOPES)) {
            return null;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($scopeIdentifier);
        return $scope;
    }

    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null): array
    {
        return $scopes;
    }
}
?>