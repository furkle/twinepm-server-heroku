<?php
namespace TwinePM\SqlAbstractions\Authorizations;

use \TwinePM\SqlAbstractions\ISqlAbstraction;
use \TwinePM\Responses;
interface IAuthorization extends ISqlAbstraction {
    public function getAccount(): Responses\IResponse;
    public function getCredential(): Responses\IResponse;
    public function getClientObject(): ?array;
    
    public function getGlobalAuthorizationId(): ?int;
    public function getUserId(): int;
    public function getClient(): string;
    public function getScopes(): array;
    public function getOAuthToken(): string;
    public function getTimeCreated(): ?int;
}