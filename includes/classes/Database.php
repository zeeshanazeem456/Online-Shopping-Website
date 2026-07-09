<?php

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dbHost = '127.0.0.1';
            $dbName = 'webhive_shop';
            $dbUser = 'root';
            $dbPass = 'admin123';

            $dsn = "mysql:host={$dbHost};dbname={$dbName}";

            self::$connection = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }
}
