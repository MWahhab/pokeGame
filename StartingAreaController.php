<?php

class StartingAreaController
{
    /**
     * @var \database\Database Refers to the database connection
     */
    private database\Database $connection;
    /**
     * @var User               Refers to the user currently logged in
     */
    private User              $user;
    /**
     * @var Event              Refers to the events
     */
    private Event             $event;

    /**
     * @param \database\Database $connection Refers to the database connection
     * @param User               $user       Refers to the user currently logged in
     * @param Event              $event      Refers to the events
     *
     * Upon instantiation, sets the connection, user and event properties
     */
    public function __construct(database\Database $connection, User $user, Event $event)
    {
        $this->connection = $connection;
        $this->user       = $user;
        $this->event      = $event;
    }

    /**
     * @param  array $captureAttemptData Refers to the capture data
     * @return bool                      Returns a boolean based on the validity of the data provided
     */
    public function validateCaptureAttemptData(array $captureAttemptData): bool
    {
        if(!isset(
            $captureAttemptData["pokeBall"],
            $captureAttemptData["pokemonId"],
            $captureAttemptData["pokemonName"],
            $captureAttemptData["pokemonType"],
            $captureAttemptData["pokemonGender"],
            $captureAttemptData["pokemonImage"],
            $captureAttemptData["pokemonCaptureRate"],
            $captureAttemptData["finalAttempt"]
        ))
        {
            $this->event->addEvent("Invalid Capture Data");
            $this->event->setError(400); //400 due to missing keys
            return false;
        }

        return true;
    }

    /**
     * @param  array $pokeBallData Refers to the pokeball data []
     * @return void                Reduces the amount of pokeballs in the user's inventory
     */
    private function reducePokeBallQuantity(array $pokeBallData): void
    {
        //not validating this data $pokeBallData?
        $quantityCheck = $this->connection->select(
            "inventory",
            [],
            "user_fid = {$this->user->getId()} AND  pokeball_fid = '{$pokeBallData["id"]}'",
            1
        );

        if($quantityCheck["quantity"] < 1) {
            $this->event->addEvent("You've not got any more {$pokeBallData['name']}s");
            return;
        }

        $this->connection->update(
            "inventory",
            ["quantity" => $pokeBallData["quantity"] - 1],
            ["user_fid" => $this->user->getId(), "pokeball_fid" => $pokeBallData["id"]]
        );
    }

    /**
     * @param  array $captureAttemptData Refers to the capture data
     * @return void                      Attempts to capture the Pokémon based on it's difficulty and how good the ball being used is
     */
    public function initiateCaptureAttempt(array $captureAttemptData):void
    {
        $pokeBallData = $this->connection->select(
            "pokeball",
            [
                "pokeball.id",
                "pokeball.name",
                "inventory.quantity"
                ],
            "pokeball.name = '{$captureAttemptData['pokeBall']}' AND inventory.user_fid = {$this->user->getId()}",
            1,
            ['inventory' => 'inventory.pokeball_fid = pokeball.id']
        );

        if (empty($pokeBallData)) {
            $this->event->addEvent("Invalid capture attempt data. Cannot initiate capture");
            $this->event->setError(400);
            return;
        }

        $this->reducePokeBallQuantity($pokeBallData);

        if(rand(1, 100) > $captureAttemptData["pokemonCaptureRate"]) {
            $this->event->addEvent(
                $captureAttemptData["finalAttempt"] ? "{$captureAttemptData['pokemonName']} escaped from your ball into the wilderness! Continue exploring or head back home!"
                    : "{$captureAttemptData['pokemonName']} escaped from your ball! Try again! Or continue exploring or head back home!");
            return;
        }

        $this->event->addEvent("{$captureAttemptData['pokemonName']} has successfully been captured! Continue exploring or head back home!");
        $this->event->setError(200); //200 due to no issues

        $caughtPokemon = $this->connection->select(
            "caught_pokemon",
            [],
            "user_fid = {$this->user->getId()} AND pokemon_fid = {$captureAttemptData['pokemonId']} AND gender = '{$captureAttemptData['pokemonGender']}'",
            1
        );

        if(empty($caughtPokemon))
        {
            $this->connection->insert(
                "caught_pokemon",
                [
                    "user_fid"    => $this->user->getId(),
                    "pokemon_fid" => $captureAttemptData['pokemonId'],
                    "gender"      => $captureAttemptData['pokemonGender'],
                    "quantity"    => 1
                ]);

            return;
        }

        $this->connection->update(
            "caught_pokemon",
            ["quantity" => $caughtPokemon["quantity"] + 1],
            [
                "user_fid"    => $this->user->getId(),
                "pokemon_fid" => $captureAttemptData['pokemonId'],
                "gender"      => $captureAttemptData['pokemonGender']
            ]
        );
    }

    /**
     * @param  \database\Database $connection  Refers to the database connection
     * @param  array              $pokemonList Refers to the list of pokemon
     * @return void                            populates the pokemon table in the database
     */
    public static function populatePokemon(database\Database $connection, array $pokemonList): void
    {
        if(empty($pokemonList)) {
            die("Empty list of pokemons provided");
        }

        if(!empty($connection->select("pokemon"))) {
            $connection->deleteAll("pokemon");
        }

        $connection->insertMultiple("pokemon", $pokemonList);
    }

    /**
     * @return array|null Retrieves a random pokemon from the database
     */
    public function retrieveRandomPokemon(): array|null
    {
        $pokemonCount = $this->connection->select("pokemon", ['COUNT(id) as amountOfPokemon'], '', 1);
        $pokemonCount = (int) $pokemonCount['amountOfPokemon'];
        if(!$pokemonCount) {
            $this->event->addEvent("Empty pokemon list! Could not retrieve pokemon!");
            $this->event->setError(204); //no content

            return null;
        }

        return $this->connection->select("pokemon", [], 'id = ' . rand(1, $pokemonCount), 1);
    }

    /**
     * @param  array      $pokemon Refers to the pokemon
     * @return array|null          Calculates the real capture rate after taking into account both the difficulty of the
     *                             pokemon and the effectiveness of the pokeball being used
     */
    public function calculateRealCaptureRate(array $pokemon):array|null
    {
        if(empty($pokemon)) {
            $this->event->addEvent("No pokemon passed to calculate real capture rate");
            $this->event->setError("400");
            return null;
        }

        $pokeballs = $this->connection->select("pokeball", ["name", "capture_rate"]);

        for($i=0; $i<count($pokeballs); $i++) {
            $pokeballs[$i]["capture_rate"] *= (100 / $pokemon["capture_rate"]);
        } //was using a foreach here before but it didnt alter the array. figured out its because
          // foreach simply makes a copy

        $pokemon["capture_rate"] = $pokeballs;

        return $pokemon;
    }

    /**
     * @param  \database\Database $connection Refers to the database connection
     * @return void                           Updates every player's inventory every 5 minutes
     */
    public static function updateInventory(database\Database $connection):void
    {
        $minutes               = 5;
        $pokeballRefreshAmount = 3;
        $currentTimestamp      = time();
        $oldTimestamp          = time();

        if (file_exists("C:/xampp/htdocs/pokeGame/pokeball_timestamp.txt")) {
            $oldTimestamp = intval(file_get_contents("C:/xampp/htdocs/pokeGame/pokeball_timestamp.txt"));
        } else {
            file_put_contents("C:/xampp/htdocs/pokeGame/pokeball_timestamp.txt", $oldTimestamp);
        }

        $refreshAt = $oldTimestamp + ($minutes * 60);

        if ($refreshAt > $currentTimestamp) {
            return;
        }

        $pokeballs = $connection->select(
            "inventory",
            [
                "pokeball.name",
                "inventory.quantity",
                "inventory.pokeball_fid",
                "inventory.user_fid"
            ],
            '',
            0,
            [
                "pokeball" => "pokeball.id = inventory.pokeball_fid",
            ]);

        foreach ($pokeballs as $ball) {
            $connection->update(
                "inventory",
                ["quantity" => ($ball['quantity'] + $pokeballRefreshAmount)],
                [
                    "pokeball_fid" => $ball['pokeball_fid'],
                    "user_fid" => $ball['user_fid']
                ]
            );
        }

        file_put_contents("C:/xampp/htdocs/pokeGame/pokeball_timestamp.txt", $currentTimestamp);
    }

}