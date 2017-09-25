<?php
namespace TwinePM\SqlAbstractions;

use PDO;
interface ISqlAbstraction {
    static function getFromPrimaryKey($primaryKey, PDO $database);

    static function getFromToken(string $token, PDO $database);

    static function getFromUserId(int $userId, PDO $database);

    static function convertFetchToSource(array $fetch): array;

    function __construct(array $source);
    function __get(string $propName);

    function toArray(): array;

    function isInDatabase(PDO $database): bool;
    function serializeToDatabase(PDO $database): void;
    function updateFromDatabase(PDO $database): void;
    function deleteFromDatabase(PDO $database): void;
}