<?php

require_once ("database/config.php");
require_once ("StartingAreaController.php");
require_once ("Event.php");
require_once ("User.php");

$json        = file_get_contents("php://input");
$requestData = json_decode($json, true);

$captureData = [
    "pokeBall"           => (string) $requestData["pokeBall"],
    "pokemonName"        => (string) $requestData["pokemonName"],
    "pokemonType"        => (string) $requestData["pokemonType"],
    "pokemonGender"      => (string) $requestData["pokemonGender"],
    "pokemonImage"       => (string) $requestData["pokemonImage"],
    "pokemonCaptureRate" => (string) $requestData["pokemonCaptureRate"]
];

/**
 * @var User $user
 */
$user  = unserialize($_SESSION["user"]);
$event = new Event();

/**
 * @var database\Database $connection
 */
$controller = new StartingAreaController($connection, $user, $event);

if(!$controller->validateCaptureAttemptData($captureData)) {
    die("Invalid Capture Data Provided");
}

$controller->initiateCaptureAttempt($captureData);

$json = json_encode($event);
echo $json;