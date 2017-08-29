<?php
namespace TwinePM\SqlAbstractions;

use \TwinePM\Responses;
use \PDO;
interface ISqlAbstraction {
    public static function get(
        array $source,
        PDO $database = null): Responses\IResponse;

    public static function getFromToken(
        string $token,
        PDO $database = null): Responses\IResponse;

    public static function convertFetchToSource(array $fetch): array;

    public function __construct(array $source, PDO $database = null);
    public function __get(string $propName);

    public function toArray(): array;

    public function isInDatabase(): bool;
    public function serializeToDatabase(): Responses\IResponse;
    public function updateFromDatabase(): Responses\IResponse;
    public function deleteFromDatabase(): Responses\IResponse;
    public function getDatabase(): PDO;

    public function isError(): bool;
    public function getError(): ?Responses\ErrorResponse;
}
?>