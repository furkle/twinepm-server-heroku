<?php
namespace TwinePM\SqlAbstractions\Accounts;

use \TwinePM\SqlAbstractions\ISqlAbstraction;
use \TwinePM\Responses;
interface IAccount extends ISqlAbstraction {
    const DATE_STYLES = [
        "mmdd",
        "ddmm",
    ];

    const TIME_STYLES = [
        "12h",
        "24h",
    ];

    const DEFAULTS = [
        "nameVisible" => true,
        "description" => "",
        "timeCreatedVisible" => true,
        "email" => "",
        "emailVisible" => false,
        "dateStyle" => "mmdd",
        "timeStyle" => "12h",
        "homepage" => "",
    ];

    public function getPackages(): Responses\IResponse;

    public function getCredential(): Responses\IResponse;

    public function getId(): int;
    public function setId(int $id): Responses\IResponse;

    public function getName(): ?string;
    public function setName(?string $name): Responses\IResponse;

    public function getNameVisible(): bool;
    public function setNameVisible(bool $visible): Responses\IResponse;

    public function getDescription(): string;
    public function setDescription(string $description): Responses\IResponse;

    public function getTimeCreated(): ?int;

    public function getTimeCreatedVisible(): bool;
    public function setTimeCreatedVisible(
        bool $timeCreatedVisible): Responses\IResponse;

    public function getEmail(): string;
    public function setEmail(string $email): Responses\IResponse;

    public function getEmailVisible(): bool;
    public function setEmailVisible(bool $visible): Responses\IResponse;

    public function getDateStyle(): string;
    public function setDateStyle(string $dateStyle): Responses\IResponse;

    public function getTimeStyle(): string;
    public function setTimeStyle(string $timeStyle): Responses\IResponse;

    public function getHomepage(): string;
    public function setHomepage(string $homepage): Responses\IResponse;
}