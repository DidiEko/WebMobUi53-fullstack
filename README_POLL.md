# Travail pratique WebMobUI — Application de sondages Laravel + Vue.js

## Description du projet

Ce projet est une application de sondages développée avec Laravel et Vue.js.

L’objectif est de permettre à une personne connectée de créer, configurer, modifier, supprimer, démarrer et partager des sondages. Les sondages sont accessibles via un lien contenant un token secret. Les utilisateurs connectés peuvent voter, et les résultats sont affichés en direct grâce à un polling régulier.

Le projet s’appuie sur la base fournie par l’enseignant, notamment l’authentification Laravel, les layouts Blade, les modèles Eloquent fournis et l’intégration Vue existante.

## Technologies utilisées

- Laravel
- Vue.js 3
- Vite
- Blade
- Sanctum
- SQLite
- Tailwind CSS

## Installation du projet

Cloner le dépôt GitHub, puis installer les dépendances PHP et JavaScript :

```bash
composer install
npm install