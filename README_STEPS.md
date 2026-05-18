## Installation et lancement du projet

### Prérequis

Le projet nécessite :

- PHP >= 8.0
- Composer
- Node.js et npm
- SQLite, MySQL ou PostgreSQL

---

## Installation

### 1. Installer les dépendances PHP

```bash
composer install
````

### 2. Installer les dépendances JavaScript

```bash
npm install
```

---

## Configuration Laravel

### 3. Copier le fichier d’environnement

```bash
cp .env.example .env
```

### 4. Générer la clé Laravel

```bash
php artisan key:generate
```

### 5. Créer le lien de stockage

```bash
php artisan storage:link
```

---

## Base de données

### 6. Exécuter les migrations

```bash
php artisan migrate
```

### 7. (Optionnel) Ajouter des données de test

```bash
php artisan db:seed
```

---

## Lancement du projet

Le projet nécessite deux terminaux ouverts simultanément.

---

### Terminal 1 — Lancer Laravel

```bash
php artisan serve
```

Laravel sera accessible à l’adresse :

```txt
http://127.0.0.1:8000
```

---

### Terminal 2 — Lancer Vite

```bash
npm run dev
```

Ce terminal compile et recharge automatiquement les fichiers Vue.js et CSS.

---

## Utilisation

### Authentification

Créer un compte ou se connecter depuis l’interface Laravel existante.

---

### Dashboard des sondages

Accéder au dashboard :

```txt
/polls/dashboard
```

Depuis cette page, l’utilisateur peut :

* créer un sondage
* modifier un sondage
* supprimer un sondage
* démarrer un sondage
* récupérer le lien de partage

---

### Page de vote

Chaque sondage possède un lien contenant un token unique :

```txt
/polls/vote/{token}
```

Cette page permet :

* de consulter le sondage
* de voter
* de voir les résultats en temps réel si ceux-ci sont publics

```
```
