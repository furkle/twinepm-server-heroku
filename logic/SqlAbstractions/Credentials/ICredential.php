<?php
namespace TwinePM\SqlAbstractions\Credentials;

use \TwinePM\SqlAbstractions\ISqlAbstraction;
use \TwinePM\Responses;
use \PDO;
interface ICredential extends ISqlAbstraction {
    public function getAccount(): Responses\IResponse;

    public function resetPassword(
        string $cleartextPassword): Responses\IResponse;

    public function getId(): ?int;

    public function getName(): ?string;
    public function setName(?string $name): Responses\IResponse;

    public function getHash(): string;

    public function getValidated(): bool;
    public function setValidated(bool $validated): Responses\IResponse;
}
?>