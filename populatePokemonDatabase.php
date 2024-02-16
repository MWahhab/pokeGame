<?php
require_once ("database/config.php");
require_once ("Pokemon.php");
require_once ("StartingAreaController.php");

$insertArr = [];
$pokeApi   = "https://pokeapi.co/api/v2/pokemon";

while($pokeApi) {
    $pokemonApiJson = file_get_contents($pokeApi);
    $pokemonApi     = json_decode($pokemonApiJson, true);

    $pokemonList = $pokemonApi["results"];

    foreach ($pokemonList as $pokemon) {
        $pokemonJson = file_get_contents($pokemon["url"]);
        $pokemonData = json_decode($pokemonJson, true);

        $pokemonArr = [
            "name"         => $pokemonData["name"],
            "type"         => $pokemonData["types"][0]["type"]["name"],
            "image"        => $pokemonData["sprites"]["other"]["official-artwork"]["front_default"],
            "capture_rate" => rand(0, 100),
        ];

        if(!Pokemon::validatePokemonDetails($pokemonArr)) {
            continue;
        }

        $insertArr[] = $pokemonArr;
    }

    $pokeApi = $pokemonApi["next"] ?? null;
}

/**
 * @var database\Database $connection
 */
StartingAreaController::populatePokemon($connection, $insertArr);

exit();
