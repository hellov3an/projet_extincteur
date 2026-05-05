# 🔧 Installation - Fonctionnalité de Suivi des Révisions

## ⚙️ Étapes d'installation

### Étape 1 : Mise à jour de la base de données

Exécutez le fichier SQL mis à jour pour créer la nouvelle table :

**Option A : Depuis phpMyAdmin**
1. Ouvrez phpMyAdmin
2. Sélectionnez la base de données `gestionfeu`
3. Accédez à l'onglet "SQL"
4. Copiez-collez le contenu de la nouvelle table depuis `database/gestionfeu.sql`
5. Exécutez la requête

**Option B : Depuis MySQL CLI**
```bash
mysql -u your_user -p gestionfeu < database/gestionfeu.sql
```

**Commande SQL à exécuter :**
```sql
CREATE TABLE revisions_extincteurs (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  extincteur_id     INT UNSIGNED NOT NULL,
  date_revision     DATE         NOT NULL COMMENT 'Date de l\'intervention',
  type_maintenance  ENUM('Visite périodique','Entretien','Recharge','Remplacement','Réparation','Autre') NOT NULL DEFAULT 'Visite périodique',
  entreprise        VARCHAR(255) NULL COMMENT 'Nom de l\'entreprise/technicien',
  contact           VARCHAR(100) NULL COMMENT 'Personne de contact',
  observations      TEXT         NULL COMMENT 'Notes sur l\'intervention',
  conformite        ENUM('Conforme','Non conforme','À vérifier') NOT NULL DEFAULT 'Conforme',
  prochaine_date    DATE         NULL COMMENT 'Date de la prochaine révision recommandée',
  utilisateur_id    INT UNSIGNED NOT NULL COMMENT 'Technicien qui a enregistré la révision',
  created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (extincteur_id)  REFERENCES extincteurs(id)       ON DELETE CASCADE,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)      ON DELETE RESTRICT,
  INDEX idx_extincteur (extincteur_id),
  INDEX idx_date (date_revision DESC),
  INDEX idx_conformite (conformite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Étape 2 : Vérification des fichiers

Vérifiez que tous les fichiers suivants sont présents :

**Fichiers créés :**
- ✅ `revision_form.php`
- ✅ `revision_historique.php`
- ✅ `revisions.php`
- ✅ `REVISIONS_DOCUMENTATION.md`
- ✅ `REVISIONS_CHANGEMENTS.md`
- ✅ `INSTALLATION.md` (ce fichier)

**Fichiers modifiés :**
- ✅ `database/gestionfeu.sql`
- ✅ `includes/functions.php`
- ✅ `extincteur_voir.php`
- ✅ `index.php`

### Étape 3 : Test de fonctionnement

1. **Accédez à la page d'accueil** : `http://localhost/projet_extincteur/index.php`
2. **Cliquez sur 📊 Révisions** (nouvellement ajouté)
3. **Consultez le tableau de bord** : Vous devriez voir les statistiques et la liste des révisions
4. **Enregistrez une révision** :
   - Allez sur un extincteur (ex: EXT-RDC-001)
   - Cliquez sur "➕ Ajouter une révision"
   - Remplissez le formulaire
   - Cliquez sur "✓ Enregistrer la révision"
5. **Vérifiez l'historique** : Vous devriez voir la nouvelle révision dans l'historique

## 🔐 Vérification des permissions

Les permissions sont automatiquement gérées par le système existant :

- **Admin** : Accès complet
- **Technicien** : Peut voir et enregistrer des révisions
- **Lecteur** : Peut seulement consulter (lecture seule)

Testez avec chaque rôle pour vérifier le fonctionnement.

## 🐛 Dépannage

### Problème : La table n'est pas créée
**Solution** : Exécutez manuellement la requête SQL fournie ci-dessus.

### Problème : Le bouton "Ajouter une révision" n'apparaît pas
**Solution** : Vérifiez que vous êtes connecté en tant que technicien ou admin.

### Problème : Erreur 404 sur les pages de révisions
**Solution** : Vérifiez que les fichiers PHP sont dans le bon répertoire et ont les bonnes permissions.

### Problème : Les dates de contrôle ne se mettent pas à jour
**Solution** : Vérifiez que la table `extincteurs` a les colonnes `dernier_controle` et `prochain_controle`.

## 📊 Données de démonstration

Des données de démonstration ont été ajoutées pour tester :
- 6 révisions pour les premiers extincteurs
- Variété de types de maintenance
- Différents états de conformité

Pour les supprimer, exécutez :
```sql
DELETE FROM revisions_extincteurs WHERE extincteur_id <= 5;
```

## 🔄 Mise à jour depuis une version précédente

Si vous avez une version antérieure du projet :

1. **Sauvegardez votre base de données** :
   ```bash
   mysqldump -u your_user -p gestionfeu > backup_$(date +%Y%m%d).sql
   ```

2. **Exécutez les migrations** :
   ```sql
   -- Exécutez seulement la création de la table revisions_extincteurs
   ```

3. **Remplacez les fichiers** :
   - Copiez les nouveaux fichiers PHP
   - Mettez à jour les fichiers modifiés

4. **Testez** :
   - Vérifiez que l'interface affiche bien les révisions
   - Testez l'enregistrement et la consultation

## ✅ Checklist post-installation

- [ ] Table `revisions_extincteurs` créée
- [ ] Tous les fichiers PHP en place
- [ ] Lien 📊 Révisions visible sur l'accueil
- [ ] Possibilité d'enregistrer une révision
- [ ] Historique accessible par extincteur
- [ ] Tableau de bord des révisions fonctionne
- [ ] Filtres répondent correctement
- [ ] Permissions appliquées correctement
- [ ] Données de démonstration visibles

## 📞 Support

En cas de problème :
1. Vérifiez les permissions des fichiers
2. Consultez les logs d'erreur du serveur
3. Assurez-vous que les données de démonstration sont bien insérées
4. Vérifiez que PHP est en version 8.0+

---

**Installation complète estimée** : 5 minutes
**Complexité** : Faible
**Risque** : Très faible (pas de données existantes modifiées)
