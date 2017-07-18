<?php
function reapUnclaimedReservations() {
	$dsn = "mysql:host=localhost;dbname=twinepm;";

	$emailValidationUsername = "tpm_emailvalidation_delete_user";
	$emailValidationPassword = trim(file_get_contents(__DIR__ .
		"/../delete/tpm_emailvalidation_delete_user.txt"));
	
	$emailValidationDB = new PDO(
		$dsn,
		$emailValidationUsername,
		$emailValidationPassword);
	$emailValidationDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$stmt = $emailValidationDB->prepare("SELECT id FROM email_validation " .
		"WHERE time_reserved < ?");
	try {
		$stmt->execute(array(time() - 86400));
	} catch (Exception $e) {
		$response["status"] = 500;
		$response["error"] = "An unknown error was encountered searching for " .
			"expired reservations";
		return $response;
	}

	$passwordsUsername = "tpm_passwords_delete_user";
	$passwordsPassword = trim(file_get_contents(__DIR__ .
		"/../delete/tpm_passwords_delete_user.txt"));

	$passwordsDB = new PDO(
		$dsn,
		$passwordsUsername,
		$passwordsPassword);
	$passwordsDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if ($fetchAll) {
		$userdataUsername = "tpm_userdata_delete_user";
		$userdataPassword = trim(file_get_contents(__DIR__ .
			"/../delete/tpm_userdata_delete_user.txt"));

		$userdataDB = new PDO(
			$dsn,
			$userdataUsername,
			$userdataPassword);
		$userdataDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$emailValidationQuery = "DELETE FROM email_validation WHERE ";
		$userdataQuery = "DELETE FROM userdata WHERE ";
		$passwordsQuery = "DELETE FROM passwords WHERE ";

		$params = array();

		$foundYet = false;
		foreach ($fetchAll as $row) {
			$emailValidationQuery .= "id=? OR ";
			$passwordsQuery .= "id=? OR ";
			$userdataQuery .= "id=? OR ";

			$params[] = $row["id"];
		}

		$emailValidationQuery = substr(
			$emailValidationQuery,
			0,
			strlen($emailValidationQuery) - 3);

		$userdataQuery = substr(
			$userdataQuery,
			0,
			strlen($userdataQuery) - 3);

		$passwordsQuery = substr(
			$passwordsQuery,
			0,
			strlen($passwordsQuery) - 3);

		$stmt = $emailValidationDB->prepare($emailValidationQuery);
		$emailValidationDB->beginTransaction();
		
		try {
			$stmt->execute($params);
		} catch (Exception $e) {
			$response["status"] = 500;
			$response["error"] = "Unknown error encountered deleting " .
				"username reservation. Please contact webmaster.";
			return $response;
		}

		$stmt = $userdataDB->prepare($userdataQuery);
		$userdataDB->beginTransaction();

		try {
			$stmt->execute($params);
		} catch (Exception $e) {
			$emailValidationDB->rollBack();

			$response["status"] = 500;
			$response["error"] = "Unknown error encountered deleting " .
				"userdata. Please contact webmaster.";
			return $response;
		}

		$stmt = $passwordsDB->prepare($passwordsQuery);
		$passwordsDB->beginTransaction();

		try {
			$stmt->execute($params);
		} catch (Exception $e) {
			$emailValidationDB->rollBack();
			$userdataDB->rollBack();

			$response["status"] = 500;
			$response["error"] = "Unknown error encountered deleting " .
				"credentials. Please contact webmaster.";
			return $response;
		}

		echo "worked";

		$emailValidationDB->commit();
		$userdataDB->commit();
		$passwordsDB->commit();
	}

	return $response;
}
?>
