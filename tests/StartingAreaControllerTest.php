<?php

require "vendor/autoload.php";

require "StartingAreaController.php";
require_once ("database/Database.php");
require_once ("User.php");
require_once ("Event.php");

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\DataSet\MockDataSet;
class StartingAreaControllerTest extends TestCase
{
    private database\Database      $connection;
    private User                   $user;
    private Event                  $event;
    private StartingAreaController $controller;

    /**
     * Cannot use a constructor as it would override the parent (TestCase), so use setup instead to carry out procedural
     * logic
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getMockBuilder(\database\Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new StartingAreaController($this->connection, $this->user, $this->event);
    }

    /**
     * Tests all cases for validateCaptureAttemptData method
     *
     * @dataProvider captureAttemptDataProvider
     *
     * @param  array $captureAttemptData Refers to the array being passed to the method for a result
     * @param  bool  $expectedData       Refers to the expected result of the test case
     * @return void
     */
    public function testValidateCaptureAttemptData(array $captureAttemptData, bool $expectedData): void
    {
        $result = $this->controller->validateCaptureAttemptData($captureAttemptData);

        $this->assertSame($expectedData, $result);

    }

    /**
     * Acts as a provider so that multiple test cases can be executed in a single testing method
     *
     * @return array[]
     */
    public static function captureAttemptDataProvider(): array
    {
        return [
            "valid data" => [
                [
                    "pokeBall"           => "master-ball",
                    "pokemonId"          => 3,
                    "pokemonName"        => "Pikaachu",
                    "pokemonType"        => "lightning",
                    "pokemonGender"      => "female",
                    "pokemonImage"       => "yourMun.jpg",
                    "pokemonCaptureRate" => 0.32,
                    "finalAttempt"       => 2
                ],
                true
            ],
            "partially missing data" => [
                [
                    "pokeBall"           => "master-ball",
                    "pokemonName"        => "Pikaachu",
                    "pokemonGender"      => "female",
                    "pokemonCaptureRate" => 0.32,
                    "finalAttempt"       => 2
                ],
                false
            ],
            "completely missing data" => [
                [],
                false
            ]
        ];
    }

    /**
     * tests initiateCaptureAttempt method
     *
     * @dataProvider initiateCaptureAttemptProvider
     */
    public function testInitiateCaptureAttempt(array $captureAttemptData, array $dbResponse, bool $expectUpdate, bool $expectEvent, int $expectedError): void
    {
        $this->user->method('getId')->willReturn(1);

        $this->connection->method('select')
            ->willReturn($dbResponse);

        $expectUpdate && $this->connection->expects($this->atLeastOnce())->method("update");

        $expectEvent && $this->event->expects($this->once())->method("addEvent");

        $this->event->expects($this->once())->method('setError')->with($expectedError);

        $this->controller->initiateCaptureAttempt($captureAttemptData);
    }

    /**
     * Provider for initiateCaptureAttempt method
     *
     * @return array[]
     */
    public static function initiateCaptureAttemptProvider(): array
    {
        return [
            "Valid capture" => [
                [
                    "pokeBall"           => "master-ball",
                    "pokemonId"          => 3,
                    "pokemonName"        => "Pikaachu",
                    "pokemonType"        => "lightning",
                    "pokemonGender"      => "female",
                    "pokemonImage"       => "yourMun.jpg",
                    "pokemonCaptureRate" => 100,
                    "finalAttempt"       => 1
                ],
                [
                    "id"        => 1,
                    "name"      => "master-ball",
                    "quantity"  => 10
                ],
                false,
                true,
                200
            ],
            "Invalid pokéball (not in inventory)" => [
                [
                    "pokeBall"           => "great-ball",
                    "pokemonId"          => 3,
                    "pokemonName"        => "Pikaachu",
                    "pokemonType"        => "lightning",
                    "pokemonGender"      => "female",
                    "pokemonImage"       => "yourMun.jpg",
                    "pokemonCaptureRate" => 100,
                    "finalAttempt"       => 1
                ],
                [],
                false,
                true,
                400
            ]
        ];
    }

    /**
     * tests populatePokemon method
     *
     * @dataProvider populatePokemonProvider
     *
     * @param  array $pokemonList Refers to the list being used to populate the db
     * @return void
     */
    public function testPopulatePokemon(array $pokemonList): void
    {
        if(empty($pokemonList)){
            $this->expectOutputString("");

            return;
        }

        $this->connection->method('select')->willReturn([['id' => 1]]);
        $this->connection->expects($this->once())->method('deleteAll')->with('pokemon');
        $this->connection->expects($this->once())->method('insertMultiple')->with('pokemon', $this->anything());

        $this->controller->populatePokemon($this->connection, $pokemonList);
    }

    /**
     * Provides test cases for testPopulatePokemon method
     *
     * @return array[][]
     */
    public static function populatePokemonProvider(): array
    {
        return [
            "empty pokemon list" => [
                []
            ],
            "populated pokemon list" => [
                [
                    "name"         => "pikachu",
                    "type"         => "lightning",
                    "image"        => "pikachu.jpg",
                    "capture_rate" => 5
                ]
            ]

        ];
    }

    /**
     * Tests cases where there are no pokemons in the list and when there are
     *
     * @return void
     */
    public function testRetrieveRandomPokemon(): void
    {
        $this->connection->method('select')->willReturn(['amountOfPokemon' => 0]);
        $this->event->expects($this->atLeastOnce())->method('addEvent')->with("Empty pokemon list! Could not retrieve pokemon!");
        $this->event->expects($this->atLeastOnce())->method('setError')->with(204);

        $result = $this->controller->retrieveRandomPokemon();

        $this->assertNull($result);

        $this->connection->method('select')
            ->willReturn([
                [
                    'id'           => 1,
                    'name'         => 'Pikachu',
                    'type'         => 'Electric',
                    'capture_rate' => 50
                ]
            ]);

        $result = $this->controller->retrieveRandomPokemon();

        $this->assertNotEmpty($result);
    }

    /**
     * Tests calculateRealCaptureRate method
     *
     * @dataProvider calculateRealCaptureRateProvider
     *
     * @return void
     */
    public function testCalculateRealCaptureRate(array $pokemon, array|null $expectedResult): void
    {
        if(empty($pokemon)){
            $this->event->expects($this->once())->method('addEvent');
            $this->event->expects($this->once())->method('setError');
        } else {
            $pokeballs = [
                [
                    "name"         => "master ball",
                    "capture_rate" => 6
                ],
                [
                    "name"         => "basic ball",
                    "capture_rate" => 2
                ]

            ];

            $this->connection->method("select")->willReturn($pokeballs);

            $this->connection->expects($this->once())->method("select");
        }

        $result = $this->controller->calculateRealCaptureRate($pokemon);

        $this->assertSame($expectedResult, $result);

    }

    /**
     * Provides data for different test cases to use in testRetrieveRandomPokemon
     *
     * @return array
     */
    public static function calculateRealCaptureRateProvider(): array
    {
        return [
            "invalid pokemon data" => [
                [],
                null
            ],
            "valid pokemon data" => [
                [
                    "id"           => 1,
                    "name"         => "pikachu",
                    "type"         => "lightning",
                    "gender"       => "male",
                    "image"        => "pikachu.png",
                    "capture_rate" => 20
                ],
                [
                    "id"           => 1,
                    "name"         => "pikachu",
                    "type"         => "lightning",
                    "gender"       => "male",
                    "image"        => "pikachu.png",
                    "capture_rate" =>
                        [
                            [
                                "name"         => "master ball",
                                "capture_rate" => 30
                            ],
                            [
                                "name"         => "basic ball",
                                "capture_rate" => 10
                            ]
                        ]
                ]
            ]
        ];
    }


}