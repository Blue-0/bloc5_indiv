# Release note — Dernière version

Date: 2026-06-18

Résumé
- Correction et stabilisation des dépendances PHP/Composer pour l'environnement Docker (PHP 7.4).
- Verrou (`composer.lock`) régénéré pour être compatible avec la plateforme du conteneur.
- Ajustements dans `composer.json` pour forcer des résolutions compatibles avec PHP 7.4.

Fonctionnalités clés
- Authentification des utilisateurs: inscription, connexion, déconnexion.
- Option "Se souvenir de moi": jeton persistant stocké en cookie et en base.
- Espace personnel: page `account` listant les articles de l'utilisateur.
- Gestion des articles/produits: création, affichage (`/product` et `/product/{id}`), et upload d'images.
- Envoi d'e-mails: utilitaire `Mailer` pour notifications et communication.


```bash
# rebuild & start
docker compose -f compose.yml up -d --build
```


Notes techniques
- L'option "Se souvenir de moi" et le flux d'authentification n'ont pas été modifiés par cette release ; il s'agit uniquement d'ajustements de dépendances et d'infrastructure.
