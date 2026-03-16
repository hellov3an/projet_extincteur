# 🔥 GestionFeu

Application web PHP pour gérer les extincteurs d'un bâtiment.

---

## Ce que fait l'application

- **Inventaire** : liste tous les extincteurs avec filtres, et signale ceux qui expirent
- **Plans** : upload des plans du bâtiment, placement des extincteurs dessus par clic
- **Admin** : gestion des utilisateurs avec rôles (admin / technicien / lecteur) et permissions

---

## Ce qu'il faut pour l'installer

- Un serveur PHP 8.1+ (XAMPP, WAMP, Laragon…)
- MySQL ou MariaDB
- Aucune dépendance, pas de Composer, pas de npm

---

## Installation

### 1. Copier les fichiers

Mettre le dossier `gestionfeu/` dans le dossier de votre serveur :
- XAMPP → `C:/xampp/htdocs/gestionfeu/`
- WAMP  → `C:/wamp64/www/gestionfeu/`
- Laragon → `C:/laragon/www/gestionfeu/`

### 2. Créer la base de données

Dans phpMyAdmin, créer une base de données nommée `gestionfeu`,
puis importer le fichier `database/gestionfeu.sql`.

### 3. Configurer la connexion

Ouvrir `config.php` et modifier si besoin :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestionfeu');
define('DB_USER', 'root');
define('DB_PASS', '');          // mot de passe MySQL
define('BASE_URL', '/gestionfeu'); // adapter si besoin
```

### 4. Lancer l'installation automatique (optionnel)

Aller sur : `http://localhost/gestionfeu/install.php`

Ce script crée les tables et les comptes de démo automatiquement.
**Supprimer `install.php` après.**

### 5. Se connecter

Aller sur : `http://localhost/gestionfeu/login.php`

---

## Comptes de démo

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@gestionfeu.fr | admin123 | Administrateur |
| tech@gestionfeu.fr | tech123 | Technicien |
| lecteur@gestionfeu.fr | lecteur123 | Lecteur |

---

## Structure des fichiers

```
gestionfeu/
│
├── config.php              ← Configuration (BDD, URLs)
├── login.php               ← Page de connexion
├── logout.php              ← Déconnexion
├── install.php             ← Script d'installation (à supprimer après)
│
├── index.php               ← Liste des extincteurs
├── extincteur_form.php     ← Ajouter / modifier un extincteur
├── extincteur_voir.php     ← Fiche détail
├── extincteur_suppr.php    ← Supprimer
│
├── plans.php               ← Liste des plans
├── plan_form.php           ← Uploader / modifier un plan
├── plan_voir.php           ← Vue interactive du plan avec marqueurs
├── plan_suppr.php          ← Supprimer un plan
├── api_pinpoints.php       ← API AJAX pour placer/retirer les marqueurs
│
├── admin.php               ← Panel admin — liste des utilisateurs
├── user_form.php           ← Ajouter / modifier un utilisateur
├── user_toggle.php         ← Activer / désactiver un compte
├── user_suppr.php          ← Supprimer un utilisateur
│
├── includes/
│   ├── db.php              ← Connexion PDO
│   ├── auth.php            ← Session, login, permissions
│   └── functions.php       ← Fonctions utilitaires
│
├── views/
│   ├── header.php          ← En-tête commun (navbar)
│   └── footer.php          ← Pied de page commun
│
├── public/
│   └── style.css           ← Toute la mise en page
│
├── uploads/
│   └── plans/              ← Images uploadées (créé automatiquement)
│
└── database/
    └── gestionfeu.sql      ← Fichier SQL à importer dans phpMyAdmin
```

---

## Rôles et permissions

| Permission | Admin | Technicien | Lecteur |
|---|:---:|:---:|:---:|
| Voir les extincteurs | ✅ | ✅ | ✅ |
| Modifier les extincteurs | ✅ | ✅ | ❌ |
| Supprimer les extincteurs | ✅ | ❌ | ❌ |
| Voir les plans | ✅ | ✅ | ✅ |
| Gérer les plans | ✅ | ✅ | ❌ |

Les admins ont toujours tout. Pour les techniciens et lecteurs, les permissions peuvent être personnalisées depuis le panel admin.

---

## Technos utilisées

- **PHP 8.1+** avec PDO pour la base de données
- **MySQL** pour stocker les données
- **HTML / CSS / JavaScript** (sans framework, tout à la main)
- Aucune dépendance externe

---

## Licence

MIT — Libre d'utilisation et de modification.
