<?php
require_once ("database/config.php");
require_once ("StartingAreaController.php");
require_once ("Event.php");
require_once ("User.php");

/**
 * @var User $user
 */
$user  = unserialize($_SESSION["user"]);
$event = new Event();

/**
 * @var database\Database $connection
 */
$controller = new StartingAreaController($connection, $user, $event);

$dbPokemon = $controller->retrieveRandomPokemon();

$alteredPokemon = $controller->calculateRealCaptureRate($dbPokemon);

$json = json_encode($alteredPokemon);
echo $json;

exit();