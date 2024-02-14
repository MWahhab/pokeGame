<?php
require_once ("database/config.php");
require_once ("User.php");

$registrationData = [
    "fullName"          => htmlspecialchars($_POST["full-name"]),
    "email"             => htmlspecialchars($_POST["email"]),
    "password"          => htmlspecialchars($_POST["password"]),
    "confirmPassword"   => htmlspecialchars($_POST["confirm-password"])
];

$isDataValid = User::validateRegistrationData($registrationData);

if(!$isDataValid) {
    die("Insufficient or incorrectly formatted registration data");
}

$newUser = new User($registrationData["email"], $registrationData["password"], $registrationData["fullName"]);

/**
 * @var database\Database $connection
 */
$newUser->registerUser($connection);