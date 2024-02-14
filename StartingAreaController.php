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
            $captureAttemptData["pokemonName"],
            $captureAttemptData["pokemonType"],
            $captureAttemptData["pokemonGender"],
            $captureAttemptData["pokemonImage"],
            $captureAttemptData["pokemonCaptureRate"]
        ))
        {
            $this->event->addEvent("Invalid Capture Data");
            $this->event->setError(400); //400 due to missing keys
            return false;
        }

        return true;
    }

    /**
     * @param  array $pokeBallData Refers to the pokeball data
     * @return void                Reduces the amount of pokeballs in the user's inventory
     */
    private function reducePokeBallQuantity(array $pokeBallData): void
    {
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
                "pokeball.capture_rate",
                "pokeball.id",
                "pokeball.name",
                "inventory.quantity"
                ],
            "pokeball.name = '{$captureAttemptData['pokeBall']}' AND inventory.user_fid = {$this->user->getId()}",
            1,
            ['inventory' => 'inventory.pokeball_fid = pokeball.id']
        );

        $realCaptureChance = ($pokeBallData["capture_rate"]/$captureAttemptData["pokemonCaptureRate"]) * 100;

        if(rand(1, 100) > $realCaptureChance) {
            $this->reducePokeBallQuantity($pokeBallData);

            $this->event->addEvent("{$captureAttemptData['pokemonName']} escaped from your ball! Try again!");
            $this->event->setError(200); //200 due to no issues

            return;
        }

        $this->reducePokeBallQuantity($pokeBallData);

        $this->event->addEvent("{$captureAttemptData['pokemonName']} has successfully been captured! Continue exploring or head back home!");
        $this->event->setError(200); //200 due to no issues

        $caughtPokemon = $this->connection->select(
            "caught_pokemon",
            [],
            "user_fid = {$this->user->getId()} AND name = '{$captureAttemptData["pokemonName"]}'",
            1
        );

        if(empty($caughtPokemon))
        {
            $this->connection->insert(
                "caught_pokemon",
                [
                    "user_fid" => $this->user->getId(),
                    "name"     => $captureAttemptData["pokemonName"],
                    "type"     => $captureAttemptData["pokemonType"],
                    "gender"   => $captureAttemptData["pokemonGender"],
                    "image"    => $captureAttemptData["pokemonImage"],
                    "quantity" => 1
                ]);

            return;
        }

        $this->connection->update(
            "caught_pokemon",
            ["quantity" => $caughtPokemon["quantity"] + 1],
            ["user_fid" => $this->user->getId(), "name" => $captureAttemptData["pokemonName"]]
        );
    }
}