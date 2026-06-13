<?php

namespace App\Models;

use Core\Model;

/**
 * Modèle Utilisateur
 *
 * Gère la persistance et la récupération des utilisateurs
 * dans la base de données.
 *
 * PHP version 7.0
 */
class User extends Model
{
    /**
     * Insère un nouvel utilisateur en base de données.
     *
     * @param array<string, string> $data Données de l'utilisateur (email, username, password, salt)
     *
     * @return int L'identifiant du nouvel utilisateur
     */
    public static function createUser(array $data): int
    {
        $db = static::getDB();

        $stmt = $db->prepare(
            'INSERT INTO users(username, email, password, salt) VALUES (:username, :email, :password, :salt)'
        );

        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email',    $data['email']);
        $stmt->bindParam(':password', $data['password']);
        $stmt->bindParam(':salt',     $data['salt']);

        $stmt->execute();

        return (int) $db->lastInsertId();
    }

    /**
     * Récupère un utilisateur par son adresse e-mail (pour la connexion).
     *
     * @param string $email Adresse e-mail de l'utilisateur
     *
     * @return array<string, mixed>|false Les données de l'utilisateur, ou false s'il n'existe pas
     */
    public static function getByLogin(string $email)
    {
        $db = static::getDB();

        $stmt = $db->prepare('SELECT * FROM users WHERE users.email = :email LIMIT 1');
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour (ou supprime) le jeton "Se souvenir de moi" d'un utilisateur.
     *
     * @param int         $userId Identifiant de l'utilisateur
     * @param string|null $token  Nouveau jeton, ou null pour le supprimer
     *
     * @return bool true si la mise à jour a réussi
     */
    public static function updateRememberToken(int $userId, ?string $token): bool
    {
        $db = static::getDB();

        $stmt = $db->prepare('UPDATE users SET remember_token = :token WHERE id = :id');
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id',    $userId);

        return $stmt->execute();
    }

    /**
     * Récupère un utilisateur par son jeton "Se souvenir de moi".
     *
     * @param string $token Le jeton de connexion persistant
     *
     * @return array<string, mixed>|false Les données de l'utilisateur, ou false si le jeton est invalide
     */
    public static function getByRememberToken(string $token)
    {
        $db = static::getDB();

        $stmt = $db->prepare('SELECT * FROM users WHERE remember_token = :token LIMIT 1');
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
