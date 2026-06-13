<?php

namespace App\Models;

use Core\Model;
use DateTime;

/**
 * Modèle Article
 *
 * Gère la persistance et la récupération des annonces (articles)
 * dans la base de données.
 *
 * PHP version 7.0
 */
class Articles extends Model
{
    /**
     * Retourne tous les articles, avec un tri optionnel.
     *
     * @param string $filter Critère de tri : 'views' (vues), 'date' (date de publication) ou '' (aucun tri)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(string $filter): array
    {
        $db    = static::getDB();
        $query = 'SELECT * FROM articles';

        switch ($filter) {
            case 'views':
                $query .= ' ORDER BY articles.views DESC';
                break;
            case 'date':
                $query .= ' ORDER BY articles.published_date DESC';
                break;
        }

        $stmt = $db->query($query);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne un article par son identifiant, avec les données de son vendeur.
     *
     * @param int $id Identifiant de l'article
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getOne(int $id): array
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT * FROM articles
            INNER JOIN users ON articles.user_id = users.id
            WHERE articles.id = ?
            LIMIT 1
        ');

        $stmt->execute([$id]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Incrémente le compteur de vues d'un article.
     *
     * @param int $id Identifiant de l'article
     *
     * @return void
     */
    public static function addOneView(int $id): void
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            UPDATE articles
            SET articles.views = articles.views + 1
            WHERE articles.id = ?
        ');

        $stmt->execute([$id]);
    }

    /**
     * Retourne tous les articles d'un utilisateur donné.
     *
     * @param int $userId Identifiant de l'utilisateur
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getByUser(int $userId): array
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT *, articles.id as id FROM articles
            LEFT JOIN users ON articles.user_id = users.id
            WHERE articles.user_id = ?
        ');

        $stmt->execute([$userId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retourne les 10 derniers articles publiés (suggestions).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getSuggest(): array
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT *, articles.id as id FROM articles
            INNER JOIN users ON articles.user_id = users.id
            ORDER BY published_date DESC
            LIMIT 10
        ');

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Insère un nouvel article en base de données.
     *
     * @param array<string, mixed> $data Données de l'article (name, description, user_id)
     *
     * @return int L'identifiant du nouvel article
     */
    public static function save(array $data): int
    {
        $db = static::getDB();

        $stmt = $db->prepare(
            'INSERT INTO articles(name, description, user_id, published_date) VALUES (:name, :description, :user_id, :published_date)'
        );

        $publishedDate = (new DateTime())->format('Y-m-d');

        $stmt->bindParam(':name',           $data['name']);
        $stmt->bindParam(':description',    $data['description']);
        $stmt->bindParam(':user_id',        $data['user_id']);
        $stmt->bindParam(':published_date', $publishedDate);

        $stmt->execute();

        return (int) $db->lastInsertId();
    }

    /**
     * Associe une image à un article existant.
     *
     * @param int    $articleId   Identifiant de l'article
     * @param string $pictureName Nom du fichier image (stocké dans /public/storage/)
     *
     * @return void
     */
    public static function attachPicture(int $articleId, string $pictureName): void
    {
        $db = static::getDB();

        $stmt = $db->prepare('UPDATE articles SET picture = :picture WHERE articles.id = :articleId');

        $stmt->bindParam(':picture',   $pictureName);
        $stmt->bindParam(':articleId', $articleId);

        $stmt->execute();
    }
}
