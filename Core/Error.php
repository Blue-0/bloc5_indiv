<?php

namespace Core;

/**
 * Gestionnaire global d'erreurs et d'exceptions
 *
 * Intercepte toutes les erreurs PHP et les exceptions non capturées,
 * et les traite uniformément : affichage en mode développement,
 * journalisation en mode production.
 *
 * PHP version 7.0
 */
class Error
{
    /**
     * Gestionnaire d'erreurs PHP.
     *
     * Convertit toutes les erreurs PHP en exceptions \ErrorException
     * afin de les traiter de manière uniforme.
     * Respecte l'opérateur de suppression d'erreurs (@).
     *
     * @param int    $level   Niveau d'erreur (E_WARNING, E_NOTICE, etc.)
     * @param string $message Message d'erreur
     * @param string $file    Fichier dans lequel l'erreur s'est produite
     * @param int    $line    Numéro de ligne de l'erreur
     *
     * @return void
     * @throws \ErrorException
     */
    public static function errorHandler(int $level, string $message, string $file, int $line): void
    {
        // L'opérateur @ met error_reporting à 0 — on le respecte
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Gestionnaire d'exceptions non capturées.
     *
     * En mode développement (SHOW_ERRORS = true) : affiche le détail complet.
     * En mode production : journalise l'exception et affiche une page d'erreur Twig.
     *
     * @param \Exception $exception L'exception non capturée
     *
     * @return void
     */
    public static function exceptionHandler(\Exception $exception): void
    {
        // Seuls les codes 404 sont conservés ; tout autre code devient 500
        $code = $exception->getCode() === 404 ? 404 : 500;
        http_response_code($code);

        if (\App\Config::SHOW_ERRORS) {
            echo '<h1>Erreur fatale</h1>';
            echo '<p>Exception : ' . get_class($exception) . '</p>';
            echo '<p>Message : ' . $exception->getMessage() . '</p>';
            echo '<p>Stack trace :<pre>' . $exception->getTraceAsString() . '</pre></p>';
            echo '<p>Lancée dans ' . $exception->getFile() . ' à la ligne ' . $exception->getLine() . '</p>';
        } else {
            $logFile = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '.txt';
            ini_set('error_log', $logFile);

            $message  = "Exception : '" . get_class($exception) . "'";
            $message .= " — message : '" . $exception->getMessage() . "'";
            $message .= "\nStack trace : " . $exception->getTraceAsString();
            $message .= "\nLancée dans '" . $exception->getFile() . "' à la ligne " . $exception->getLine();

            error_log($message);

            View::renderTemplate("{$code}.html");
        }
    }
}
