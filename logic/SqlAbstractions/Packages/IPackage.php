<?php
namespace TwinePM\SqlAbstractions\Packages;

use \TwinePM\SqlAbstractions\ISqlAbstraction;
use \TwinePM\Responses;
use \PDO;
interface IPackage extends ISqlAbstraction {
    const TYPES = [
        "macro",
        "script",
        "style",
        "passagetheme",
        "storytheme",
    ];

    public function getOwner(): Responses\IResponse;

    public function getCurrentVersionObject(): Responses\IResponse;
    
    public function getVersions(string $dataLevel): Responses\IResponse;

    public function getId(): ?int;

    public function getOwnerId(): int;
    public function setOwnerId(int $id): Responses\IResponse;

    public function getAuthorId(): int;

    public function getName(): string;
    public function setName(string $name): Responses\IResponse;

    public function getType(): string;
    public function setType(string $type): Responses\IResponse;

    public function getCurrentVersion(): string;
    public function setCurrentVersion(
        string $currentVersion): Responses\IResponse;

    public function getDescription(): string;
    public function setDescription(string $description): Responses\IResponse;

    public function getHomepage(): string;
    public function setHomepage(string $homepage): Responses\IResponse;
}
?>