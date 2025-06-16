# Music Blog - Application Symfony

Un blog musical complet développé avec Symfony, permettant de créer et gérer des articles sur des artistes, avec intégration de l'API Spotify.

## 🎵 Fonctionnalités

- **CRUD complet** pour la gestion des articles
- **Interface d'administration** pour créer/modifier/supprimer des articles
- **Système d'authentification** avec comptes utilisateurs
- **Commentaires** sur les articles (utilisateurs connectés)
- **API REST sécurisée** pour manipuler les articles
- **Intégration Spotify API** pour récupérer les informations des artistes
- **Export PDF** des articles (génération asynchrone avec Messenger)

## 📋 Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL ou PostgreSQL
- Node.js et npm (pour les assets)
- Un compte développeur Spotify

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/beiyaC/spoticry.git
cd spoticry
```

### 2. Installer les dépendances

```bash
composer install
npm install
npm run build
```

### 3. Configuration

Copier le fichier `.env.example` en `.env` et configurer :

```bash
cp .env.example .env
```

Important: Assurez-vous de configurer le fichier config/packages/doctrine.yaml pour utiliser la bonne base de données.

Modifier les variables suivantes :
- `DATABASE_URL` : Configuration de votre base de données
- `SPOTIFY_CLIENT_ID` : Votre Client ID Spotify
- `SPOTIFY_CLIENT_SECRET` : Votre Client Secret Spotify
- `MAILER_DSN` : Configuration du serveur mail

Ensuite exécutez :

```bash
docker-compose up -d
```
Afin de lancer un container Docker pour la réception des emails (MailHog). Assurez-vous que Docker est installé et en cours d'exécution.

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Charger les fixtures

```bash
php bin/console doctrine:fixtures:load
```

Cela créera :
- Un compte super admin : `superadmin@music-blog.com` / `superadmin123`
- Un compte admin : `admin@music-blog.com` / `admin123`
- Un compte utilisateur : `user@music-blog.com` / `user123`
- Quelques articles d'exemple avec commentaires

### 6. Lancer le serveur

```bash
symfony server:start
```

Ou avec le serveur PHP :

```bash
php -S localhost:8000 -t public
```

### 7. Lancer le worker Messenger (pour les exports PDF)

Dans un terminal séparé :

```bash
php bin/console messenger:consume async -vv
```

## 🔑 Obtenir les clés Spotify API

1. Aller sur [Spotify for Developers](https://developer.spotify.com/dashboard)
2. Se connecter avec votre compte Spotify
3. Créer une nouvelle application
4. Récupérer le Client ID et Client Secret
5. Les ajouter dans votre fichier `.env`

## 📱 Utilisation

### Interface publique

- **Page d'accueil** : Liste des articles publiés
- **Article** : Lecture d'un article avec informations Spotify
- **Recherche** : Recherche d'articles par titre, contenu ou artiste

### Interface d'administration (/admin)

Accessible uniquement aux administrateurs :
- Créer/modifier/supprimer des articles
- Gérer la publication des articles
- Recherche automatique d'artistes via Spotify
- Gestion des tags

### API REST (/api)

Endpoints disponibles :

```
GET    /api/articles          # Liste des articles
GET    /api/articles/{id}     # Détails d'un article
POST   /api/articles          # Créer un article (admin)
PUT    /api/articles/{id}     # Modifier un article (admin)
DELETE /api/articles/{id}     # Supprimer un article (admin)
GET    /api/articles/search   # Rechercher des articles
```

### Export PDF

Les utilisateurs connectés peuvent exporter les articles en PDF :
1. Cliquer sur "Exporter en PDF" sur un article
2. Le PDF est généré en arrière-plan
3. Un email est envoyé avec le lien de téléchargement. Lorsque vous recevez l'email, cliquez sur le lien pour télécharger le PDF.
Vous devrez modifier l'url du lien de téléchargement en rajoutant le port `8000` après le localhost.
4. Vous pourrez également retrouver le pdf dans le dossier `var/pdf` du projet.

## 🛠️ Commandes utiles

```bash

# Créer un nouvel utilisateur admin
php bin/console app:create-admin

# Vider le cache
php bin/console cache:clear

# Voir les routes disponibles
php bin/console debug:router
```

## 📦 Structure du projet

```
music-blog/
├── src/
│   ├── Controller/          # Contrôleurs
│   ├── Entity/             # Entités Doctrine
│   ├── Form/               # Formulaires
│   ├── Message/            # Messages Messenger
│   ├── MessageHandler/     # Handlers Messenger
│   ├── Repository/         # Repositories
│   ├── Security/           # Authenticators
│   └── Service/            # Services (Spotify, PDF)
├── templates/              # Templates Twig
├── public/                 # Assets publics
├── config/                 # Configuration
└── migrations/             # Migrations Doctrine
```

## 🔒 Sécurité

- Authentification par email/mot de passe
- Rôles : ROLE_USER, ROLE_ADMIN, ROLE_SUPER_ADMIN
- Protection CSRF sur tous les formulaires
- API sécurisée (admin uniquement pour modifications)
- Validation des données côté serveur

### Erreur Spotify API

Si vous n'avez pas de clés Spotify API, vous pouvez désactiver cette fonctionnalité en mettant des valeurs vides dans le `.env` :

```
SPOTIFY_CLIENT_ID=
SPOTIFY_CLIENT_SECRET=
```
