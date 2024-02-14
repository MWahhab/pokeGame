<?php

class LoginController
{
    /**
     * @var User               $user       Refers to the user attempting to log in
     */
    private User              $user;

    /**
     * @var \database\Database $connection Refers to the database connection
     */
    private database\Database $connection;

    /**
     * @param \database\Database $connection Refers to the database connection
     * @param User               $user       Refers to the user attempting to log in
     *
     * Sets the database and user as properties upon instantiation
     */
    public function __construct(database\Database $connection, User $user)
    {
        $this->connection = $connection;
        $this->user       = $user;
    }

    /**
     * @return User|null Returns an existing user or null
     *
     * Checks to see whether a user exists or not
     */
    public function validateLogin(): User|null
    {
        $email = $this->user->getEmail();
        $pass  = $this->user->getPassword();

        $queriedUser = $this->connection->select("user", [], "email = '$email' AND password = '$pass'", 1);

        if(empty($queriedUser)) {
            return null;
        }

        $this->user->setId($queriedUser["id"]);
        $this->user->setFullName($queriedUser["full_name"]);

        return $this->user;
    }
}