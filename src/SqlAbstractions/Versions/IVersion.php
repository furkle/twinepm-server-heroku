<?php
namespace TwinePM\SqlAbstractions\Versions;

use \TwinePM\SqlAbstractions\ISqlAbstraction;
use \TwinePM\Responses;
interface IVersion extends ISqlAbstraction {
    const DATA_LEVELS = [
        "metadata",
        "full",
    ];

    const DEFAULTS = [
        "js" => "",
        "css" => "",
        "description" => "",
        "homepage" => "",
        "tag" => "",
        "packageDataLevel" => "metadata",
    ];

    public function getPackage(): Responses\IResponse;
    public function getOwner(): Responses\IResponse;

    public function getPackageId(): int;

    public function getGlobalVersionId(): ?int;

    public function getJs(): ?string;
    public function setJs(?string $js): Responses\IResponse;

    public function getCss(): ?string;
    public function setCss(?string $css): Responses\IResponse;

    public function getDescription(): string;
    public function setDescription(string $description): Responses\IResponse;

    public function getHomepage(): string;
    public function setHomepage(string $homepage): Responses\IResponse;

    public function getVersion(): string;
    public function setVersion(string $version): Responses\IResponse;

    public function getTag(): ?string;
    public function setTag(?string $tag): Responses\IResponse;

    public function getAuthorId(): int;

    public function getTimeCreated(): ?int;

    public function getName(): string;
}
?>