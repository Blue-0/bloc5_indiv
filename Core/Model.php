<?php

namespace Core;

use PDO;
use App\Config;

/**
 * Base model
 *
 * PHP version 7.0
 */
abstract class Model
{

    /**
     * Get the PDO database connection
     *
     * @return mixed
     */
    protected static function getDB()
    {
        static $db = null;

        if ($db === null) {
            $host = getenv('DB_HOST') ?: Config::DB_HOST;
            $name = getenv('DB_NAME') ?: Config::DB_NAME;
            $user = getenv('DB_USER') ?: Config::DB_USER;
            $password = getenv('DB_PASSWORD') ?: Config::DB_PASSWORD;
            $charset = getenv('DB_CHARSET') ?: 'utf8';

            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=' . $charset;
            $db = new PDO($dsn, $user, $password);

            // Throw an Exception when an error occurs
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $db;
    }
}
