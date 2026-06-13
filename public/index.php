<?php

/**
 * Front Controller
 *
 * Point d'entrée unique de l'application. Initialise la session,
 * configure la gestion des erreurs, gère la reconnexion automatique
 * via cookie et dispatche la requête via le Router.
 *
 * PHP version 8.0
 */

session_start();

// Chargement de l'autoloader Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// Interception de toutes les erreurs et exceptions
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');


// Auto-connexion via le cookie "Se souvenir de moi"
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
    try {
        $user = \App\Models\User::getByRememberToken($_COOKIE['remember_me']);
        if ($user) {
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
            ];
        }
    } catch (\Exception $e) {
        // En cas d'erreur de base de données, on ignore silencieusement
        // pour ne pas bloquer l'application
    }
}


// Déclaration de la table de routage
$router = new Core\Router();

$router->add('',                    ['controller' => 'Home',    'action' => 'index']);
$router->add('login',               ['controller' => 'User',    'action' => 'login']);
$router->add('register',            ['controller' => 'User',    'action' => 'register']);
$router->add('logout',              ['controller' => 'User',    'action' => 'logout',  'private' => true]);
$router->add('account',             ['controller' => 'User',    'action' => 'account', 'private' => true]);
$router->add('product',             ['controller' => 'Product', 'action' => 'index',   'private' => true]);
$router->add('product/{id:\d+}',    ['controller' => 'Product', 'action' => 'show']);
$router->add('{controller}/{action}');


// Dispatch de la requête
try {
    $router->dispatch($_SERVER['QUERY_STRING']);
} catch (\Exception $e) {
    if ($e->getMessage() === 'You must be logged in') {
        header('Location: /login');
    }
}
