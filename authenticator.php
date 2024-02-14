<?php
require_once ("database/config.php");
require_once ("User.php");
require_once ("LoginController.php");

$inputtedUser = new User(htmlspecialchars($_POST["email"]), htmlspecialchars($_POST["password"]));

/**
 * @var database\Database $connection
 */
$controller    = new LoginController($connection, $inputtedUser);
$validatedUser = $controller->validateLogin();

if($validatedUser == null) {
    die("Invalid Input Data");
}

$_SESSION["isLoggedIn"] = true;
$_SESSION["user"]       = serialize($validatedUser);

header("Location: http://localhost/pokeGame/startingArea.php");
unset($inputtedUser, $controller, $validatedUser);
exit();