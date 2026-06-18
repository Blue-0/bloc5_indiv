# Vide Grenier en Ligne

Ce Readme.md est à destination des futurs repreneurs du site-web Vide Grenier en Ligne.

Support utilisateur : https://docs.google.com/document/d/1kBjnFvO53A3LC2BtEJqCjcZObCmJ8Ouq7J56AhBwvXk/edit?tab=t.0

Mise en production : https://docs.google.com/document/d/1FOCHiiHU9ekAJJHU5TY6ENkT3co4d-GVS26VFQDID7k/edit?usp=sharing

## Mise en place du projet back-end

1. Créez un VirtualHost pointant vers le dossier /public du site web (Apache)
2. Importez la base de données MySQL (sql/import.sql)
3. Connectez le projet et la base de données via les fichiers de configuration
4. Lancez la commande `composer install` pour les dépendances

## Mise en place du projet front-end
1. Lancez la commande `npm install` pour installer node-sass
2. Lancez la commande `npm run watch` pour compiler les fichiers SCSS

## Routing

Le [Router](Core/Router.php) traduit les URLs. 

Les routes sont ajoutées via la méthode `add`. 

En plus des **controllers** et **actions**, vous pouvez spécifier un paramètre comme pour la route suivante:

```php
$router->add('product/{id:\d+}', ['controller' => 'Product', 'action' => 'show']);
```


## Vues

Les vues sont rendues grâce à **Twig**. 
Vous les retrouverez dans le dossier `App/Views`. 

```php
View::renderTemplate('Home/index.html', [
    'name'    => 'Toto',
    'colours' => ['rouge', 'bleu', 'vert']
]);
```


--------


## Résolution des erreurs (Twig + upload image)

### 1) Erreur Twig : `Class Twig\Loader\FilesystemLoader not found`

**Symptôme**
- Erreur fatale au rendu Twig, avec une classe introuvable.

**Cause**
- Mauvaise casse (majuscule/minuscule) dans le nom de classe du loader Twig.
- En environnement Linux/Docker, l'autoload Composer est sensible à la casse.

**Correctif**
- Correction du nom de classe dans [Core/View.php](Core/View.php) : utilisation de `\Twig\Loader\FilesystemLoader`.

### 2) Exception upload : “This file extension is not allowed…”

**Symptôme**
- À la soumission du formulaire d'ajout, `$_FILES['picture']` est vide avec `error = 4` (`UPLOAD_ERR_NO_FILE`).

**Cause**
- Un formulaire HTML imbriqué dans [App/Views/Product/Add.html](App/Views/Product/Add.html) : le clic sur “Valider” soumettait le mauvais `<form>` (sans `enctype="multipart/form-data"`).

**Correctifs**
- Front : suppression du `<form>` imbriqué autour du bouton “Valider” dans [App/Views/Product/Add.html](App/Views/Product/Add.html) (un seul formulaire, en `multipart/form-data`).
- Back (image obligatoire) : contrôle de `$_FILES['picture']['error']` dans [App/Controllers/Product.php](App/Controllers/Product.php) avant l'enregistrement.
- Back (robustesse) : gestion explicite des erreurs `UPLOAD_ERR_*` + normalisation de l'extension en minuscule dans [App/Utility/Upload.php](App/Utility/Upload.php).
## Models

Les modèles sont utilisés pour récupérer ou stocker des données dans l'application. Les modèles héritent de `Core
\Model
` et utilisent [PDO](http://php.net/manual/en/book.pdo.php) pour l'accès à la base de données. 

```php
$db = static::getDB();
```

### 3) Connexion automatique après inscription

**Problématique**
- À la création d'un compte, l'utilisateur n'était pas authentifié automatiquement et devait saisir à nouveau ses identifiants sur la page de connexion.

**Correctif**
- Modification de la méthode `registerAction()` dans [App/Controllers/User.php](App/Controllers/User.php) :
  - Validation de la création de compte à l'aide de `$this->register($f)`.
  - Authentification automatique immédiate via `$this->login($f)`.
  - Redirection vers son espace compte `/account` et arrêt propre de l'exécution avec `exit`.

### 4) Fonctionnalité "Se souvenir de moi" (Remember Me)

**Problématique**
- La case à cocher "Se souvenir de moi" sur l'écran de connexion n'était pas opérationnelle : son état n'était pas envoyé au serveur, aucun cookie n'était créé, et aucune reconnexion automatique n'était effectuée.

**Correctif**
- **Base de données** : Ajout de la colonne `remember_token` dans la table `users` (et mise à jour de [sql/import.sql](sql/import.sql)).
- **Modèle** : Ajout des méthodes `updateRememberToken()` et `getByRememberToken()` dans [App/Models/User.php](App/Models/User.php).
- **Vue (Front)** : Modification de `name="#"` par `name="remember_me"` pour l'input de la case à cocher dans [App/Views/User/login.html](App/Views/User/login.html).
- **Contrôleur (Back)** :
  - Dans la méthode `login()` de [App/Controllers/User.php](App/Controllers/User.php) : si l'option est cochée, génération d'un token sécurisé aléatoire, enregistrement en base de données et création d'un cookie HTTP-only `remember_me` expirant sous 30 jours.
  - Dans la méthode `logoutAction()` : suppression du token en base de données et destruction du cookie.
- **Bootstrapping (Auto-connexion)** : Ajout d'une vérification dans le fichier d'entrée [public/index.php](public/index.php). Si l'utilisateur n'est pas connecté en session mais possède le cookie `remember_me`, il est automatiquement connecté après vérification en base de données.

### 5) Formulaire de contact vendeur (Mailtrap)

**Problématique**
- Le bouton de contact sur la page du produit ouvrait directement la boîte de messagerie par défaut de l'utilisateur via un lien `mailto:`, sans formulaire intégré ni traitement applicatif.

**Correctif**
- **Dépendance** : Installation de `phpmailer/phpmailer` pour la gestion propre des envois d'e-mails (SMTP).
- **Configuration** : Ajout des constantes SMTP de Mailtrap dans [App/Config.php](App/Config.php).
- **Utilitaire (Envoi d'e-mail)** : Création de la classe [App/Utility/Mailer.php](App/Utility/Mailer.php) dédiée à la configuration de PHPMailer et à l'envoi de l'e-mail (encapsulation et respect de la responsabilité unique / SRP).
- **Vue (Front)** : Remplacement du lien `mailto:` dans [App/Views/Product/Show.html](App/Views/Product/Show.html) par un formulaire de contact Bootstrap demandant l'email de l'acheteur (pré-rempli s'il est connecté) et son message, avec affichage d'alertes de succès/erreur.
- **Contrôleur (Back)** :
  - Modification de `showAction()` dans [App/Controllers/Product.php](App/Controllers/Product.php) pour récupérer la soumission du formulaire, valider les entrées, récupérer l'e-mail du propriétaire du produit, composer le message et déclencher l'envoi via `Mailer::send()`.



## Refactorisation et Modernisation

Une refactorisation globale du code a été menée pour moderniser le projet et s'aligner sur les bonnes pratiques de développement PHP.

### 1) Convention de nommage (PSR-12)
Afin de faciliter la maintenance et le travail en équipe, une convention de nommage uniforme a été adoptée :
* **PascalCase** pour les classes (ex. `Router`, `Mailer`, `Upload`).
* **camelCase** pour les variables, propriétés et méthodes de classe (ex. `$routeParams`, `$formData`, `productsAction()`).
* **SCREAMING_SNAKE_CASE** pour les constantes de classe (ex. `MAX_FILE_SIZE`, `ALLOWED_EXTENSIONS`, `SALT_CHARSET`).


### 2) Amélioration de la sécurité et robustesse
* **Validation des formulaires** : Remplacement des accès directs aux superglobales par `filter_input` et renforcement des vérifications d'intégrité (ex. validation de la correspondance des mots de passe lors de l'inscription).
* **Nettoyage du code** : Suppression des méthodes mortes ou obsolètes (ex. méthode `login()` de `App\Models\User`).
* **Séparation des responsabilités** : Extraction de la logique complexe, comme la génération du corps des e-mails dans `App\Controllers\Product` via une méthode dédiée, pour rendre le code plus modulaire.
* **Centralisation des configurations** : Utilisation de constantes de classe dédiées dans `Upload` et `Hash` au lieu de valeurs codées en dur.

## Tests unitaires et d'intégration


L'application intègre une suite de tests automatisés à l'aide de **PHPUnit**.

### 1) Configuration et prérequis

Les tests sont configurés dans le fichier [phpunit.xml](phpunit.xml). Ils s'exécutent par défaut dans le conteneur Docker où la base de données MariaDB est accessible.

Pour installer PHPUnit en local ou dans le conteneur :
```bash
composer install
```

### 2) Exécution des tests

#### À l'intérieur du conteneur Docker (Recommandé)
Les tests sont automatiquement exécutés à l'initialisation du conteneur (via l'entrypoint `entrypoint.sh`). Si un test échoue, le conteneur ne démarre pas.
Vous pouvez également les lancer manuellement dans le conteneur en cours d'exécution :
```bash
docker compose exec app ./vendor/bin/phpunit
```

#### En local sur la machine hôte Windows
Grâce à un système de détection dynamique dans la configuration de `setUp()`, si l'hôte virtuel `db` n'est pas disponible (cas d'une exécution locale hors du réseau Docker), les tests d'intégration redirigent automatiquement la connexion SQL vers `127.0.0.1`.
*Note : Pour que cela fonctionne, le port de la base de données MariaDB est exposé sur `localhost:3306` via `compose.yml`.*

Pour lancer les tests en local :
```powershell
.\vendor\bin\phpunit
```

### 3) Détail des tests implémentés

La suite de tests contient **21 tests** et **47 assertions** répartis comme suit :

#### Tests Unitaires (Dossier `tests/Unit`)
Ces tests valident le code fonctionnel sans interaction avec la base de données :
- [HashTest.php](tests/Unit/HashTest.php) : Teste les utilitaires de hachage de mot de passe `Hash::generate`, la longueur et le caractère aléatoire de `generateSalt`, et la génération des tokens d'UID uniques.
- [UploadTest.php](tests/Unit/UploadTest.php) : Valide les contrôles de sécurité sur l'upload de fichiers (vérification des exceptions pour fichier absent, extension interdite comme `.pdf` ou `.exe`, et dépassement de la taille limite de 4 Mo).
- [RouterTest.php](tests/Unit/RouterTest.php) : Teste le mécanisme de routage de l'application (correspondance d'URL simple, extraction des paramètres `{controller}` et `{action}`, et routes complexes basées sur des expressions régulières comme `product/{id:\d+}`).

#### Tests d'Intégration (Dossier `tests/Integration`)
Ces tests valident les requêtes SQL et les contraintes de base de données à l'aide d'un jeu de données défini. Chaque test démarre une transaction PDO et effectue un `rollback` à la fin de son exécution pour garder la base de données propre.
- [UserIntegrationTest.php](tests/Integration/UserIntegrationTest.php) :
  - **Insertion et sélection** : Valide la création de compte utilisateur et sa récupération par e-mail en BDD avec mot de passe haché.
  - **Absence de valeur obligatoire** : Vérifie qu'une `PDOException` est levée lors d'une tentative d'insertion de compte avec un e-mail `NULL` (colonne NOT NULL).
  - **Limite de valeur renseignée** : Vérifie qu'une `PDOException` est levée en cas de dépassement de la limite de taille d'un champ (`username` supérieur à 100 caractères).
- [ArticleIntegrationTest.php](tests/Integration/ArticleIntegrationTest.php) :
  - **Création d'annonce et image** : Valide l'enregistrement d'une annonce (liée à un utilisateur), le rattachement de sa photo, et sa récupération par jointure SQL.
  - **Clé étrangère invalide** : Vérifie qu'une `PDOException` est levée si on tente d'associer un article à un utilisateur inexistant (clé étrangère `user_id`).
  - **Données obligatoires manquantes** : Vérifie le rejet de la sauvegarde en base en cas de titre (`name`) manquant.




