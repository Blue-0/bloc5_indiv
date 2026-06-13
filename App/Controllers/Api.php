<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Models\Cities;

/**
 * Contrôleur API (endpoints JSON)
 *
 * Expose des endpoints JSON consommés par le front-end JavaScript.
 *
 * PHP version 7.0
 */
class Api extends \Core\Controller
{
    /**
     * Retourne la liste des articles au format JSON.
     *
     * Paramètre GET attendu :
     * - sort (string) : critère de tri ('views' | 'date' | '')
     *
     * @return void
     */
    public function productsAction(): void
    {
        $sort     = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $articles = Articles::getAll($sort);

        header('Content-Type: application/json');
        echo json_encode($articles);
    }

    /**
     * Recherche des villes par préfixe et retourne les résultats au format JSON.
     *
     * Paramètre GET attendu :
     * - query (string) : préfixe de la ville recherchée
     *
     * @return void
     */
    public function citiesAction(): void
    {
        $query  = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $cities = Cities::search($query);

        header('Content-Type: application/json');
        echo json_encode($cities);
    }
}
