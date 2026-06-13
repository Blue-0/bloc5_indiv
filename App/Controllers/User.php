<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Utility\Hash;
use Core\View;
use Exception;

/**
 * Contrôleur utilisateur
 *
 * Gère l'inscription, la connexion, la déconnexion
 * et la page de compte personnel.
 *
 * PHP version 7.0
 */
class User extends \Core\Controller
{
    /**
     * Affiche et traite le formulaire de connexion.
     *
     * @return void
     */
    public function loginAction(): void
    {
        if (isset($_POST['submit'])) {
            $formData = $_POST;

            if ($this->login($formData)) {
                header('Location: /account');
                exit;
            }
        }

        View::renderTemplate('User/login.html');
    }

    /**
     * Affiche et traite le formulaire d'inscription.
     *
     * @return void
     */
    public function registerAction(): void
    {
        if (isset($_POST['submit'])) {
            $formData = $_POST;

            if ($formData['password'] !== $formData['password-check']) {
                View::renderTemplate('User/register.html', [
                    'error' => 'Les mots de passe ne correspondent pas.'
                ]);
                return;
            }

            $userId = $this->register($formData);
            if ($userId && $this->login($formData)) {
                header('Location: /account');
                exit;
            }
        }

        View::renderTemplate('User/register.html');
    }

    /**
     * Affiche la page du compte personnel de l'utilisateur connecté.
     *
     * @return void
     */
    public function accountAction(): void
    {
        $articles = Articles::getByUser($_SESSION['user']['id']);

        View::renderTemplate('User/account.html', [
            'articles' => $articles
        ]);
    }

    /**
     * Déconnecte l'utilisateur : supprime le cookie, invalide le jeton
     * en base de données et détruit la session.
     *
     * @return void
     */
    public function logoutAction(): void
    {
        if (isset($_SESSION['user']['id'])) {
            \App\Models\User::updateRememberToken($_SESSION['user']['id'], null);
        }

        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /');
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Inscrit un nouvel utilisateur en base de données.
     *
     * Génère un salt aléatoire et hache le mot de passe avant l'insertion.
     *
     * @param array<string, string> $data Données du formulaire (email, username, password)
     *
     * @return int|false L'identifiant du nouvel utilisateur, ou false en cas d'erreur
     */
    private function register(array $data)
    {
        try {
            $salt   = Hash::generateSalt(32);
            $userId = \App\Models\User::createUser([
                'email'    => $data['email'],
                'username' => $data['username'],
                'password' => Hash::generate($data['password'], $salt),
                'salt'     => $salt,
            ]);

            return (int) $userId;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Authentifie un utilisateur et initialise sa session.
     *
     * Si l'option "Se souvenir de moi" est cochée, un jeton de connexion
     * persistant est généré et stocké dans un cookie sécurisé (30 jours).
     *
     * @param array<string, string> $data Données du formulaire (email, password, remember_me?)
     *
     * @return bool true si la connexion réussit, false sinon
     */
    private function login(array $data): bool
    {
        try {
            if (!isset($data['email'])) {
                return false;
            }

            $user = \App\Models\User::getByLogin($data['email']);
            if (!$user) {
                return false;
            }

            if (Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return false;
            }

            // Gestion de l'option "Se souvenir de moi"
            if (isset($data['remember_me'])) {
                $token = bin2hex(random_bytes(32));
                \App\Models\User::updateRememberToken($user['id'], $token);
                // Cookie valide 30 jours, accessible uniquement via HTTP (httpOnly)
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }

            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
            ];

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
