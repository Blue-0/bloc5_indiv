<?php

namespace Core;

/**
 * Router
 *
 * Gère la table de routage et le dispatch des requêtes HTTP
 * vers le contrôleur et l'action appropriés.
 *
 * PHP version 7.0
 */
class Router
{
    /**
     * Table de routage : tableau associatif [regex => paramètres]
     *
     * @var array<string, array<string, string>>
     */
    protected array $routes = [];

    /**
     * Paramètres extraits de la route correspondante
     *
     * @var array<string, string>
     */
    protected array $params = [];

    /**
     * Ajoute une route à la table de routage.
     *
     * Les variables de route peuvent être définies entre accolades :
     * - Simple     : {controller}
     * - Avec regex : {id:\d+}
     *
     * @param string               $route  URL de la route (ex. : 'product/{id:\d+}')
     * @param array<string, string> $params Paramètres associés (controller, action, etc.)
     *
     * @return void
     */
    public function add(string $route, array $params = []): void
    {
        // Échappe les slashes
        $route = preg_replace('/\//', '\\/', $route);

        // Convertit les variables simples, ex. {controller} → (?P<controller>[a-z-]+)
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

        // Convertit les variables avec regex, ex. {id:\d+} → (?P<id>\d+)
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        // Ajoute les délimiteurs de début/fin et le flag insensible à la casse
        $route = '/^' . $route . '$/i';

        $this->routes[$route] = $params;
    }

    /**
     * Retourne toutes les routes enregistrées dans la table de routage.
     *
     * @return array<string, array<string, string>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Tente de faire correspondre l'URL à une route de la table.
     *
     * Si une correspondance est trouvée, les paramètres nommés
     * sont extraits et stockés dans la propriété $params.
     *
     * @param string $url L'URL à analyser (sans la query string)
     *
     * @return bool true si une route correspond, false sinon
     */
    public function match(string $url): bool
    {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                // Extrait uniquement les groupes de capture nommés
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                $this->params = $params;
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne les paramètres de la dernière route correspondante.
     *
     * @return array<string, string>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Dispatche la requête : instancie le contrôleur et appelle l'action.
     *
     * @param string $url L'URL de la requête (valeur de $_SERVER['QUERY_STRING'])
     *
     * @return void
     * @throws \Exception Si aucune route ne correspond, si le contrôleur est introuvable,
     *                    ou si l'action est suffixée par "Action" (appel direct interdit).
     */
    public function dispatch(string $url): void
    {
        $url = $this->removeQueryStringVariables($url);

        if ($this->match($url)) {
            $controllerName = $this->convertToStudlyCaps($this->params['controller']);
            $controllerClass = $this->getNamespace() . $controllerName;

            if (!class_exists($controllerClass)) {
                throw new \Exception("Contrôleur introuvable : $controllerClass");
            }

            // Vérifie l'accès aux routes privées
            if (isset($this->params['private']) && !isset($_SESSION['user']['id'])) {
                throw new \Exception('You must be logged in');
            }

            $controllerObject = new $controllerClass($this->params);

            $action = $this->convertToCamelCase($this->params['action']);

            if (preg_match('/action$/i', $action) !== 0) {
                throw new \Exception(
                    "La méthode '$action' du contrôleur '$controllerClass' ne peut pas être " .
                    "appelée directement — retirez le suffixe 'Action' de l'URL."
                );
            }

            $controllerObject->$action();
        } else {
            throw new \Exception('Aucune route ne correspond.', 404);
        }
    }

    /**
     * Convertit une chaîne avec des tirets en StudlyCaps (PascalCase).
     *
     * Exemple : 'post-authors' → 'PostAuthors'
     *
     * @param string $string La chaîne à convertir
     *
     * @return string
     */
    protected function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    /**
     * Convertit une chaîne avec des tirets en camelCase.
     *
     * Exemple : 'add-new' → 'addNew'
     *
     * @param string $string La chaîne à convertir
     *
     * @return string
     */
    protected function convertToCamelCase(string $string): string
    {
        return lcfirst($this->convertToStudlyCaps($string));
    }

    /**
     * Supprime les paramètres de query string de l'URL.
     *
     * Le fichier .htaccess transforme le premier '?' en '&' avant de passer
     * l'URL à $_SERVER['QUERY_STRING']. Cette méthode isole la partie "route"
     * de la partie "paramètres".
     *
     * Exemples :
     *   URL                           QUERY_STRING       Route retournée
     *   -------------------------------------------------------------------
     *   localhost                     ''                 ''
     *   localhost/?page=1             page=1             ''
     *   localhost/posts?page=1        posts&page=1       posts
     *   localhost/posts/index?page=1  posts/index&page=1 posts/index
     *
     * @param string $url L'URL complète issue de QUERY_STRING
     *
     * @return string L'URL sans les paramètres de query string
     */
    protected function removeQueryStringVariables(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $parts = explode('&', $url, 2);

        return strpos($parts[0], '=') === false ? $parts[0] : '';
    }

    /**
     * Retourne le namespace complet du contrôleur à instancier.
     *
     * Si un namespace personnalisé est défini dans les paramètres de route,
     * il est ajouté après le namespace de base.
     *
     * @return string Le namespace (ex. : 'App\Controllers\' ou 'App\Controllers\Admin\')
     */
    protected function getNamespace(): string
    {
        $namespace = 'App\\Controllers\\';

        if (array_key_exists('namespace', $this->params)) {
            $namespace .= $this->params['namespace'] . '\\';
        }

        return $namespace;
    }
}
