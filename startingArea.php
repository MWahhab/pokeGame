<?php

require_once ("User.php");
require_once ("database\config.php");

if(!isset($_SESSION["isLoggedIn"])) {
    die("You need to log in to view this page");
}

/**
 * @var User $user
 */
$user = unserialize($_SESSION['user']);

/**
 * @var database\Database $connection
 */
$pokeballs = $connection->select(
        "inventory",
        [
                "user.full_name",
                "pokeball.name",
                "pokeball.tier",
                "pokeball.image",
                "inventory.quantity"
        ],
        "user_fid = " . $user->getId(),
        0,
        [
                "pokeball" => "pokeball.id = inventory.pokeball_fid",
                "user"     => "user.id     = inventory.user_fid"
            ]);
// you're accessing array keys directly of this $pokeballs without ever checking if its populated

if (empty($pokeballs)) {

    //handle it

    return;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokemon Capture Game</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">

    <div class="sidebar">
        <a href="http://localhost/pokeGame/displayCaughtPokemon.php">
        <button id="view-pokemon" class="sidebar-btn">View caught pokemon</button>
        </a>
    </div>

    <h1 id="title">Welcome to the starting area <?= htmlspecialchars($pokeballs["0"]["full_name"])?></h1>

    <div class="pokeball-info">
        <h3>Your inventory:</h3>
        <?php foreach ($pokeballs as $pokeball) : ?>
            <div class="pokeball">
                <p><?= htmlspecialchars($pokeball["name"])?></p>
                <img src='<?= htmlspecialchars($pokeball["image"])?>' alt='<?= htmlspecialchars($pokeball["name"])?>'>
                <span id="quantity-<?= htmlspecialchars($pokeball["name"])?>">x<?= htmlspecialchars($pokeball["quantity"])?></span>
                <p>Tier: <?= htmlspecialchars($pokeball["tier"])?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <p id="instigate" style="display: block">You think you're ready to explore the region and capture whatever pokemon you come up against?</p>
    <button id="embark" class="embark-btn" style="display: block;" onclick="initiateExploration()">Embark on your journey</button>

    <div id="pokemon-info" style="display: none;">

        <h2 id="pokemon-name"></h2>

        <img id="pokemon-image" src="" alt="Pokemon Image">
        <div id="pokemon-details">
            <p id="pokemon-gender"></p>
            <p id="pokemon-type"></p>
            <p id="pokemon-capture-rate"></p>
            <div id="pokemon-events" style="display: none"></div>
            <select id="pokemon-ball">
                <?php foreach ($pokeballs as $pokeball) : ?>
                    <?php if ($pokeball["quantity"] > 0) : ?>
                        <option value="<?= htmlspecialchars($pokeball["name"]) ?>">
                            <?= htmlspecialchars($pokeball["name"]) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach;?>
            </select>
            <button id="attempt-capture" onclick="attemptCapture()">Attempt Capture</button>
            <button id="head-back" onclick="headBack()">Head Back</button>
            <button id="continue-exploring" onclick="continueExploration()">Continue Exploration</button>
        </div>
        <div id="notification-area"></div>
    </div>

    <div class="trainer-image">
        <img src="https://images-wixmp-ed30a86b8c4ca887773594c2.wixmp.com/f/ec831f9b-4918-4233-8b6f-8656435ea6f8/d31uqvy-2c36d2cb-2624-4e81-a297-12df96946fdb.jpg?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1cm46YXBwOjdlMGQxODg5ODIyNjQzNzNhNWYwZDQxNWVhMGQyNmUwIiwiaXNzIjoidXJuOmFwcDo3ZTBkMTg4OTgyMjY0MzczYTVmMGQ0MTVlYTBkMjZlMCIsIm9iaiI6W1t7InBhdGgiOiJcL2ZcL2VjODMxZjliLTQ5MTgtNDIzMy04YjZmLTg2NTY0MzVlYTZmOFwvZDMxdXF2eS0yYzM2ZDJjYi0yNjI0LTRlODEtYTI5Ny0xMmRmOTY5NDZmZGIuanBnIn1dXSwiYXVkIjpbInVybjpzZXJ2aWNlOmZpbGUuZG93bmxvYWQiXX0.XSTv1yjaOLCLpbWprUPeMj9rgOq6V-MLq1Zg8cMwm3s" alt="Trainer">
    </div>

    <div class="tip-sticky">
        <p>Tip: Every 5 minutes, a couple of Pokeballs are added to your inventory.</p>
    </div>
</div>
</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>

    function initiateExploration() {
        let randomPokemon = Math.floor(Math.random() * 1025) + 1;

        // every time we are exploring we're making an api call. this is a third party api
        // it costs resources to maintain and we're abusing it.
        // lets fetch all the data we need from it, store it in our own database and use our own database from here onwards
        axios.get("https://pokeapi.co/api/v2/pokemon/" + randomPokemon)
            .then(function(response) {
            displayPokemon(response.data);
        })
            .catch(function (e) {
                console.log("Error fetching pokemon from API:", e)
            })
    }

    function displayPokemon(pokemonData) {
        document.getElementById("embark").style.display                = "none";
        document.getElementById("instigate").style.display             = "none";
        document.getElementById("head-back").style.display             = "none";
        document.getElementById("continue-exploring").style.display    = "none";
        document.getElementById("pokemon-info").style.display          = "block";

        document.getElementById("title").innerHTML = "You've stumbled onto a pokemon!"

        let pokemonImage       = pokemonData["sprites"]["other"]["official-artwork"]["front_default"];
        let pokemonName        = pokemonData["name"];
        let pokemonGender      = ((Math.floor(Math.random() * 2) + 1) === 1 ? "male" : "female");
        let pokemonType        = pokemonData["types"][0]["type"]["name"];
        let pokemonCaptureRate = Math.floor(Math.random() * 100) + 1;

        document.getElementById("pokemon-image").src              = pokemonImage;
        document.getElementById("pokemon-name").innerHTML         = "You ran into a wild " + pokemonName + "! Get your pokeballs ready!";
        document.getElementById("pokemon-gender").innerHTML       = "It appears to be a " + pokemonGender + "!";
        document.getElementById("pokemon-type").innerHTML         = "It's of type: " + pokemonType + "!";
        document.getElementById("pokemon-capture-rate").innerHTML = "The chance of capturing it is " + pokemonCaptureRate + "%!";

        document.getElementById("pokemon-image").value        = pokemonImage;
        document.getElementById("pokemon-name").value         = pokemonName;
        document.getElementById("pokemon-gender").value       = pokemonGender;
        document.getElementById("pokemon-type").value         = pokemonType;
        document.getElementById("pokemon-capture-rate").value = pokemonCaptureRate;
    }

    function attemptCapture() {
        axios.post("http://localhost/pokeGame/attemptCaptureScript.php", {
            pokeBall          : document.getElementById("pokemon-ball").value,
            pokemonName       : document.getElementById("pokemon-name").value,
            pokemonType       : document.getElementById("pokemon-type").value,
            pokemonGender     : document.getElementById("pokemon-gender").value,
            pokemonImage      : document.getElementById("pokemon-image").value,
            pokemonCaptureRate: document.getElementById("pokemon-capture-rate").value
        }).then((response) => {
            if(response.data.length<=0) {
                console.log("Nothing was sent in the response!");
                return;
            }

            console.log(response.data);
            displayChanges(response.data);
        })
            .catch((e) => {
                console.log("Error retrieving response", e);
            })
    }

    function displayChanges(responseData) {
        document.getElementById("attempt-capture").style.display    = "none";
        document.getElementById("pokemon-ball").style.display       = "none";
        document.getElementById("head-back").style.display          = "block";
        document.getElementById("continue-exploring").style.display = "block";

        if (responseData["error"] == 200) {
            let name = document.getElementById("pokemon-ball").value;
            let quantityElement = document.getElementById(`quantity-${name}`);
            let currentValue = parseInt(quantityElement.innerHTML.substring(1)); // Remove the "x" character
            if (!isNaN(currentValue) && currentValue > 0) {
                quantityElement.innerHTML = "x" + (currentValue - 1);
            }
        }


        let pokEvents = document.getElementById("pokemon-events");
        pokEvents.innerHTML = "";

        for(let i=0; i<responseData["events"].length; i++) {
            let pokEvent = document.createElement("p");
            pokEvent.textContent = responseData["events"][i];

            document.getElementById("pokemon-events").appendChild(pokEvent);
        }

        document.getElementById("pokemon-events").style.display = "block";
    }

    function headBack() {
        document.getElementById("embark").style.display    = "block";
        document.getElementById("instigate").style.display = "block";
        resetExploration();

        document.getElementById("title").innerHTML = "You're back at the starting area!";
    }

    function continueExploration() {
        resetExploration();
        initiateExploration();
    }

    function resetExploration() {
        document.getElementById("pokemon-info").style.display    = "none";
        document.getElementById("attempt-capture").style.display = "block";
        document.getElementById("pokemon-ball").style.display    = "block";
        document.getElementById("pokemon-events").style.display  = "none";
    }

</script>
