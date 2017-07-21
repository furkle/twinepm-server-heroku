<?php
require_once __DIR__ . "/../globals/getSearchParams.php";
require_once __DIR__ . "/../globals/searchPackages.php";
require_once __DIR__ . "/../globals/searchUsers.php";
function search() {
    $response = array("status" => 200);

    $params = getSearchParams();

    $results = null;
    if ($type === "packages") {
        $results = searchPackages($params);
    } else if ($type === "users") {
        $results = searchUsers($params);
    } else {
        $response["status"] = 400;
        $response["error"] = "The type argument must be packages, users, or " .
            "both.";
    }

    if (!$results) {
        $response["status"] = 500;
        $response["error"] = "Unknown error searching.";
    } else if (isset($results["error"])) {
        $status = isset($results["status"]) ? $results["status"] : 500;
        $response["status"] = $status;
        $response["error"] = $results["error"];
    } else if ($results["status"] !== 200) {
        $status = isset($results["status"]) ? $results["status"] : 500;
        $response["status"] = $status;
        $response["error"] = "Unknown error. Search subroutine did not " .
            "return 200.";
    } else {
        $response["results"] = $results["results"];
    }

    return $response;
}
?>
