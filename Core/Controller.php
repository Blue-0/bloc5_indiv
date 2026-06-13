<?php

namespace Core;

/**
 * Contrôleur de base (abstrait)
 *
 * Tous les contrôleurs de l'application héritent de cette classe.
 * Elle fournit le mécanisme de filtres before/after et le dispatch
 * des actions via la méthode magique __call.
 *
 * PHP version 7.0
 */
abstract class Controller
{
    /**
     * Paramètres extraits de la route correspondante
     *
     * @var array<string, string>
     */
    protected array $routeParams = [];

    /**
     * Constructeur : reçoit les paramètres de route depuis le Router.
     *
     * @param array<string, string> $routeParams Paramètres de la route
     */
    public function __construct(array $routeParams)
    {
        $this->routeParams = $routeParams;
    }

    /**
     * Méthode appelée lorsqu'une méthode inaccessible est invoquée.
     *
     * Permet d'appeler les actions via leur nom sans le suffixe 'Action'
     * (ex. : $controller->index() appelle indexAction()).
     * Les filtres before() et after() sont exécutés autour de l'action.
     *
     * @param string  $name Nom de la méthode appelée (sans suffixe 'Action')
     * @param mixed[] $args Arguments transmis à la méthode
     *
     * @return void
     * @throws \Exception Si la méthode '{$name}Action' n'existe pas dans le contrôleur
     */
    public function __call(string $name, array $args): void
    {
        $method = $name . 'Action';

        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            }
        } else {
            throw new \Exception("Méthode '$method' introuvable dans le contrôleur " . get_class($this));
        }
    }

    /**
     * Filtre pré-action : exécuté avant chaque action.
     *
     * Peut être surchargé dans les contrôleurs enfants.
     * Retourner false annule l'exécution de l'action et du filtre after().
     *
     * @return void|false
     */
    protected function before()
    {
    }

    /**
     * Filtre post-action : exécuté après chaque action.
     *
     * Peut être surchargé dans les contrôleurs enfants.
     *
     * @return void
     */
    protected function after(): void
    {
    }
}
