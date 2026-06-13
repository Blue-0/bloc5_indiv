<?php

namespace Core;

use PDO;
use App\Config;

/**
 * Modèle de base (abstrait)
 *
 * Fournit une connexion PDO partagée (singleton) à tous les modèles
 * de l'application. Les paramètres de connexion sont lus depuis les
 * variables d'environnement, avec repli sur la configuration statique.
 *
 * PHP version 7.0
 */
abstract class Model
{
    /**
     * Retourne la connexion PDO à la base de données.
     *
     * La connexion est instanciée une seule fois (singleton statique)
     * et réutilisée pour toutes les requêtes suivantes.
     *
     * @return PDO
     */
    protected static function getDB(): PDO
    {
        static $db = null;

        if ($db === null) {
            $host    = getenv('DB_HOST')    ?: Config::DB_HOST;
            $name    = getenv('DB_NAME')    ?: Config::DB_NAME;
            $user    = getenv('DB_USER')    ?: Config::DB_USER;
            $password = getenv('DB_PASSWORD') ?: Config::DB_PASSWORD;
            $charset = getenv('DB_CHARSET') ?: 'utf8';

            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
            $db  = new PDO($dsn, $user, $password);

            // Lève une exception PDOException à chaque erreur SQL
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $db;
    }
}
