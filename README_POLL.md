# Travail pratique WebMobUI — Application de sondages Laravel + Vue.js

## Description

Cette application permet à une personne connectée de créer, gérer et partager des sondages.

Le projet a été réalisé avec Laravel pour le backend et Vue.js pour le frontend.  
Les données sont échangées via une API JSON versionnée.

Chaque sondage possède :
- une question ;
- plusieurs options ;
- différents paramètres de configuration ;
- un token secret permettant de partager le sondage.

Une page publique permet ensuite de voter et de consulter les résultats en direct.

---

# Stack technique

## Backend

- Laravel 12
- Sanctum
- SQLite
- API REST JSON

## Frontend

- Vue.js 3
- Composition API
- Vite
- Tailwind CSS

---

# Fonctionnalités implémentées

## Dashboard utilisateur

L’utilisateur connecté peut :

- consulter ses sondages ;
- créer un sondage ;
- modifier un brouillon ;
- supprimer un sondage ;
- démarrer un sondage ;
- copier le lien de partage.

---

## Gestion des options

Les options sont dynamiques :
- ajout d’options ;
- suppression d’options ;
- minimum de deux options.

---

## Paramètres du sondage

Chaque sondage peut être configuré avec :

- mode brouillon ;
- choix unique ou multiple ;
- résultats publics ;
- durée de disponibilité.

---

## Partage via token

Chaque sondage possède un token secret généré automatiquement.

Exemple :

```txt
/polls/vote/{token}