<?php

class Pokemon
{
    /**
     * @var int $id             Refers to the pokemon's id
     */

    private int    $id;
    /**
     * @var string $name        Refers to the pokemon's name
     */

    private string $name;
    /**
     * @var string $type        Refers to the pokemon's element type
     */

    private string $type;
    /**
     * @var string $gender      Refers to the pokemon's gender
     */

    private string $gender;
    /**
     * @var string $image       Refers to the pokemon's image link
     */

    private string $image;
    /**
     * @var float $capture_rate Refers to the capture rate of the pokemon
     */

    private float  $capture_rate;

    /**
     * @param string $name         Refers to the pokemon's name
     * @param string $type         Refers to the pokemon's element type
     * @param string $image        Refers to the pokemon's image link
     * @param float  $capture_rate Refers to the capture rate of the pokemon
     *
     * Upon instantiation, set's the values for the name, type, gender, image and capture_rate properties
     */
    public function __construct(string $name, string $type, string $image, float $capture_rate)
    {
        $this->name         = $name;
        $this->type         = $type;
        $this->image        = $image;
        $this->capture_rate = $capture_rate;
    }

    /**
     * @param  array $data Refers to the pokemon's data
     * @return bool        Validates the pokemon's details
     */
    public static function validatePokemonDetails(array $data): bool
    {
        if(!isset($data["name"], $data["type"], $data["image"], $data["capture_rate"], )) {
            return false;
        }

        return true;
    }
}