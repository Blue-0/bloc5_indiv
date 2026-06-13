<?php

namespace Core;

/**
 * Moteur de rendu des vues
 *
 * Fournit deux modes de rendu :
 * - PHP natif  : render()
 * - Twig       : renderTemplate()
 *
 * PHP version 7.0
 */
class View
{
    /**
     * Rend une vue PHP native.
     *
     * Les clés du tableau $args sont extraites comme variables locales
     * et disponibles directement dans le fichier de vue.
     *
     * @param string               $view Vue relative au dossier App/Views/ (ex. : 'User/login.php')
     * @param array<string, mixed> $args Données à transmettre à la vue
     *
     * @return void
     * @throws \Exception Si le fichier de vue est introuvable ou illisible
     */
    public static function render(string $view, array $args = []): void
    {
        extract($args, EXTR_SKIP);

        $file = dirname(__DIR__) . "/App/Views/{$view}";

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("Vue introuvable : $file");
        }
    }

    /**
     * Rend un template Twig.
     *
     * L'environnement Twig est instancié une seule fois (singleton statique).
     * Le mode debug est activé pour faciliter le développement.
     *
     * @param string               $template Template relatif à App/Views/ (ex. : 'User/login.html')
     * @param array<string, mixed> $args     Données à transmettre au template
     *
     * @return void
     */
    public static function renderTemplate(string $template, array $args = []): void
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/App/Views');
            $twig   = new \Twig\Environment($loader, ['debug' => true]);
            $twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        echo $twig->render($template, static::setDefaultVariables($args));
    }

    /**
     * Injecte les variables communes à toutes les vues.
     *
     * Actuellement : l'utilisateur connecté depuis la session.
     *
     * @param array<string, mixed> $args Variables déjà prévues pour la vue
     *
     * @return array<string, mixed> Variables enrichies avec les données communes
     */
    public static function setDefaultVariables(array $args = []): array
    {
        $args['user'] = $_SESSION['user'] ?? null;

        return $args;
    }
}
