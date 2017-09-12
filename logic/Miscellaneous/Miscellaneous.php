<?php
namespace TwinePM\Miscellaneous;

use \TwinePM\Loggers;
use \TwinePM\Getters;
class Miscellaneous {
    public static function makeDsn(
        string $driver,
        string $host,
        string $port,
        string $dbname,
        string $charset = null): string
    {
        if ($driver !== "pgsql" and $charset) {
            $dsn = sprintf("%s:host=%s;port=%s;dbname=%s;charset=%s;",
                $driver,
                $host,
                $port,
                $dbname,
                $charset);
        } else {
            $dsn = sprintf("%s:host=%s;port=%s;dbname=%s;",
                $driver,
                $host,
                $port,
                $dbname);
        }

        return $dsn;
    }

    public static function reapUnclaimedReservations(
        PDO $database = null): void
    {
        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare(
            "SELECT id " .
            "FROM email_validation " .
            "WHERE time_reserved < :lifetime");

        $defaults = Getters\ReservationsDefaultsGetter::get();
        $sqlParams = [ ":lifetime" => $defaults["lifetime"], ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $message = "Deleting user reservation failed.";
            $data = [
                "exception" => $e,
                "sqlError" => $stmt->errorInfo(),
            ];

            $source = [
                "message" => $message,
                "data" => $data,
            ];

            Loggers\ReapUnclaimedReservationsLogger::log($source);
        }

        if ($stmt->rowCount() === 0) {
            return;
        }

        $emailValidationQuery = "DELETE FROM email_validation WHERE ";
        $userdataQuery = "DELETE FROM accounts WHERE ";
        $passwordsQuery = "DELETE FROM passwords WHERE ";
        $emailParams = [];
        $userdataParams = [];
        $passwordsParams = [];
        $fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = count($fetchAll);
        for ($i = 0; $i < $count; $i++) {
            if ($i === $count - 1) {
                /* There are no more IDs to add. */
                $emailValidationQuery .= "id = ?";
                $passwordsQuery .= "id = ?";
                $userdataQuery .= "id = ?";    
            } else {
                /* There are more IDs to add. */
                $emailValidationQuery .= "id = ? OR ";
                $passwordsQuery .= "id = ? OR ";
                $userdataQuery .= "id = ? OR ";
            }

            $id = $row["id"];
            $emailParams[] = $id;
            $userdataParams[] = $id;
            $passwordsParams[] = $id;
        }

        $stmt = $db->prepare(
            $emailValidationQuery . "; " .
            $userdataQuery . "; " .
            $passwordsQuery);
        
        $params = array_merge($emailParams, $userdataParams, $passwordsParams);
        try {
            $stmt->execute($params);
        } catch (Exception $e) {
            $message = "Deleting user reservation failed.";
            $data = [
                "exception" => $e,
                "sqlError" => $stmt->errorInfo(),
            ];

            $source = [
                "message" => $message,
                "data" => $data,
            ];

            Loggers\ReapUnclaimedReservationsLogger::log($source);
        }

        if ($stmt->rowCount() === 0) {
            $message = "Deleting user reservation failed.";
            $data = [
                "exception" => $e,
                "sqlError" => $stmt->errorInfo(),
            ];

            $source = [
                "message" => $message,
                "data" => $data,
            ];

            Loggers\ReapUnclaimedReservationsLogger::log($message);
        }
    }

    public static function sendMail(
        string $address,
        string $title,
        string $body,
        string $sender): void
    {
        mail($address, $title, $body, "From: $sender");
    }
}