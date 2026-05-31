# Vide Grenier en Ligne

Ce Readme.md est à destination des futurs repreneurs du site-web Vide Grenier en Ligne.

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
