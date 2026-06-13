<?php

namespace App\Utility;

/**
 * Utilitaire de hachage
 *
 * Fournit des méthodes de génération de hachages SHA-256,
 * de salts aléatoires et d'identifiants uniques.
 *
 * PHP version 7.0
 */
class Hash
{
    /** Alphabet utilisé pour la génération des salts */
    private const SALT_CHARSET = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/\\][{}'\"`;:?.,<!@#$%^&*()-_=+|";

    /**
     * Génère un hachage SHA-256 d'une chaîne, avec un salt optionnel.
     *
     * @param string $string La chaîne à hacher
     * @param string $salt   Le salt à concaténer avant le hachage (vide par défaut)
     *
     * @return string Le hachage hexadécimal (64 caractères)
     */
    public static function generate(string $string, string $salt = ''): string
    {
        return hash('sha256', $string . $salt);
    }

    /**
     * Génère un salt aléatoire de la longueur spécifiée.
     *
     * @param int $length Nombre de caractères du salt
     *
     * @return string Le salt généré aléatoirement
     */
    public static function generateSalt(int $length): string
    {
        $salt          = '';
        $charsetLength = strlen(self::SALT_CHARSET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $salt .= self::SALT_CHARSET[mt_rand(0, $charsetLength)];
        }

        return $salt;
    }

    /**
     * Génère un identifiant unique sous forme de hachage SHA-256.
     *
     * @return string Un hachage unique (64 caractères)
     */
    public static function generateUnique(): string
    {
        return self::generate(uniqid());
    }
}
