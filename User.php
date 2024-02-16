<?php

class User
{
    /**
     * @var int    $id       Refers to the user's ID
     */
    private int    $id;

    /**
     * @var string $fullName Refers to the user's full name
     */
    private string $fullName;

    /**
     * @var string $email    Refers to the user's email address
     */
    private string $email;

    /**
     * @var string $password Refers to the user's password
     */
    private string $password;

    /**
     * @param string $email    Refers to the email of the user
     * @param string $password Refers to the password of the user
     * @param string $fullName Refers to the full name of the user
     *
     * Upon instantiation, sets the email, password and full name properties
     */
    public function __construct(string $email, string $password, string $fullName = "")
    {
        $this->email    = $email;
        $this->password = $password;
        $this->fullName = $fullName;
    }

    /**
     * @return int Retrieves the user's ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id Refers to the user's ID
     * @return void     Sets the user's ID
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string Retrieves the user's full name
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * @param  string $fullName Refers to the name being set
     * @return void             Sets the user's name
     */
    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }



    /**
     * @return string Retrieves the user's email
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string Retrieves the user's password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param  array $data Refers to the user's registration info
     * @return bool        Makes sure data has been inputted - as well as in the correct format
     */
    public static function validateRegistrationData(array $data): bool
    {
        if(!isset($data["fullName"], $data["email"], $data["password"], $data["confirmPassword"])) {
            return false;
        }

        if(!filter_var($data["email"], FILTER_SANITIZE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * @param  \database\Database $connection Refers to the database connection
     * @return void                           Registers a new user if the required field are filled
     */
    public function registerUser(database\Database $connection): void
    {
        if(!empty($connection->select("user", [], "email = '$this->email'", 1))) {
            die("User already exists");
        }

        $userInfo = [
            "full_name" => $this->getFullName(),
            "password"  => $this->getPassword(),
            "email"     => $this->getEmail()
        ];

        $connection->insert("user", $userInfo);

        $insertedUser = $connection->select("user", [], "email = '$this->email'", 1);
        $userId       = $insertedUser["id"];

        $connection->insertMultiple(
            "inventory",
            [
                [
                    "user_fid"     => $userId,
                    "pokeball_fid" => 1,
                    "quantity"     => 20
                ],
                [
                    "user_fid"     => $userId,
                    "pokeball_fid" => 2,
                    "quantity"     => 20
                ],
                [
                    "user_fid"     => $userId,
                    "pokeball_fid" => 3,
                    "quantity"     => 20
                ],
                [
                    "user_fid"     => $userId,
                    "pokeball_fid" => 4,
                    "quantity"     => 20
                ],
            ]
        );

        header("Location: http://localhost/pokeGame/login.html");
    }
}