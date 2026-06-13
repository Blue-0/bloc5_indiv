<?php

namespace App\Models;

use Core\Model;

/**
 * Modèle Ville
 *
 * Fournit une recherche autocomplete sur les villes françaises.
 *
 * PHP version 7.0
 */
class Cities extends Model
{
    /**
     * Recherche les villes dont le nom commence par la chaîne fournie.
     *
     * @param string $prefix Début du nom de la ville à rechercher
     *
     * @return array<int, string> Liste des noms de villes correspondants
     */
    public static function search(string $prefix): array
    {
        $db = static::getDB();

        $stmt  = $db->prepare('SELECT ville_id FROM villes_france WHERE ville_nom_reel LIKE :query');
        $query = $prefix . '%';

        $stmt->bindParam(':query', $query);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
}
