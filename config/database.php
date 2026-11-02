<?php

class Database
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = new PDO(
            "mysql:host=localhost;dbname=php_form_validation;charset=utf8mb4",
            "root",
            ""
        );
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
