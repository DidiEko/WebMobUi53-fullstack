## Fonctionnalités implémentées

L’application permet à un utilisateur authentifié de gérer des sondages complets via une interface Vue.js connectée à une API JSON Laravel.

---

### Dashboard des sondages

- Affichage de la liste des sondages de l’utilisateur connecté
- Rafraîchissement automatique des données via polling
- Navigation intégrée dans le layout principal Laravel

---

### Gestion des sondages

L’utilisateur connecté peut :

- créer un sondage
- modifier un sondage
- supprimer un sondage
- démarrer un sondage immédiatement ou plus tard

---

### Gestion des options

- Ajout dynamique d’options de réponse
- Modification des options existantes
- Suppression d’options
- Validation d’un minimum de deux options

---

### Paramètres des sondages

Chaque sondage peut être configuré avec :

- mode brouillon
- choix simple ou choix multiple
- résultats publics ou privés
- durée de disponibilité optionnelle

---

### Partage via token

Chaque sondage possède un `secret_token` unique permettant :

- de générer un lien de partage
- d’accéder à une page publique de consultation et de vote

Exemple :

```txt
/polls/vote/{token}
````

---

### Vote

Une personne authentifiée peut voter via le lien partagé.

Le système gère :

* les votes à choix unique
* les votes à choix multiple
* l’unicité du vote par utilisateur
* la vérification que les options appartiennent bien au bon sondage

---

### Gestion des droits et états

L’interface adapte automatiquement l’affichage selon :

* le statut brouillon ou actif du sondage
* la date de fin du sondage
* les droits de l’utilisateur
* la visibilité publique des résultats

---

### Résultats en temps réel

Les résultats sont affichés dynamiquement grâce à un polling régulier vers l’API.

L’interface affiche :

* le nombre total de votes
* le nombre de votes par option
* le pourcentage par option
* une visualisation graphique simple via des barres de progression

---

### Architecture technique

Le projet utilise :

* Laravel 12
* Vue.js 3
* Sanctum SPA Authentication
* API JSON versionnée (`/api/v1`)
* Composables Vue (`useFetchApi`, `usePolling`)
* Plusieurs applications Vue intégrées dans des vues Blade

---

### Structure frontend

Le frontend est organisé autour de :

* composants Vue réutilisables
* composables pour la logique métier
* communication frontend/backend via `fetch`
* layouts Blade intégrant les applications Vue via Vite

---

### Sécurité

Le projet utilise :

* authentification Laravel existante
* protection CSRF via Sanctum
* vérifications d’accès côté API
* validation Laravel sur toutes les données utilisateur

```
```
