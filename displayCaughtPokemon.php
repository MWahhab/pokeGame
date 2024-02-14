<?php
require_once ("database/config.php");
require_once ("User.php");

/**
 * @var User $user
 */
$user = unserialize($_SESSION["user"]);

/**
 * @var database\Database $connection
 */
$caughtPokemon = $connection->select(
    "caught_pokemon",
    [
        "name",
        "type",
        "gender",
        "image",
        "quantity"
    ],
    "user_fid = {$user->getId()}"
);

$noPokeImage = "https://i.redd.it/2j4p8vqbpvsz.jpg";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Pokémon</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>
<div class="container">
    <h1>Your Pokémon</h1>
    <a href="http://localhost/pokeGame/startingArea.php" class="return-button">Return to Starting Area</a>
    <?php if(empty($caughtPokemon)) :?>
        <div class="pokemon-card">
            <div class="pokemon-image">
                <img src="<?= $noPokeImage ?>" alt="No Pokemon">
            </div>
            <div class="pokemon-details">
                <h2 class="pokemon-name">No Pokemon Captured :'(</h2>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($caughtPokemon as $pokemon) :?>
            <div class="pokemon-card">
                <div class="pokemon-image">
                    <img src="<?= htmlspecialchars($pokemon["image"]) ?>" alt="<?= htmlspecialchars($pokemon["name"]) ?>">
                </div>
                <div class="pokemon-details">
                    <h2 class="pokemon-name"><?= ucfirst(htmlspecialchars($pokemon["name"])) ?></h2>
                    <p><strong>Type:</strong> <?= ucfirst(htmlspecialchars($pokemon["type"])) ?></p>
                    <p><strong>Gender:</strong> <?= ucfirst(htmlspecialchars($pokemon["gender"])) ?></p>
                    <p><strong>Quantity Caught:</strong> <?= htmlspecialchars($pokemon["quantity"]) ?></p>
                </div>
            </div>
        <?php endforeach;?>
    <?php endif;?>

</div>
</body>
</html>

