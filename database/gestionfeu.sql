-- ============================================================
--  GestionFeu — Base de données
--  MySQL 8.0+ / MariaDB 10.6+   utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS gestionfeu
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestionfeu;

-- ── Utilisateurs ─────────────────────────────────────────────

CREATE TABLE utilisateurs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(100)  NOT NULL,
  email       VARCHAR(255)  NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  role        ENUM('admin','technicien','lecteur') NOT NULL DEFAULT 'lecteur',
  permissions JSON          NULL COMMENT 'Liste des permissions accordées',
  actif       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Extincteurs ───────────────────────────────────────────────

CREATE TABLE extincteurs (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_serie      VARCHAR(100) NOT NULL UNIQUE,
  type              ENUM('Eau','CO2','Poudre','Mousse','Halon') NOT NULL,
  marque            VARCHAR(100) NULL,
  capacite          DECIMAL(6,2) NULL COMMENT 'En kg ou litres',
  zone              VARCHAR(100) NOT NULL COMMENT 'Étage ou zone du bâtiment',
  localisation      VARCHAR(255) NULL COMMENT 'Description précise',
  date_installation DATE         NULL,
  date_expiration   DATE         NULL,
  dernier_controle  DATE         NULL,
  prochain_controle DATE         NULL,
  notes             TEXT         NULL,
  created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_zone  (zone),
  INDEX idx_exp   (date_expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Plans (images de localisation) ───────────────────────────

CREATE TABLE plans (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(100) NOT NULL,
  zone        VARCHAR(100) NULL,
  description TEXT         NULL,
  fichier     VARCHAR(255) NOT NULL COMMENT 'Nom du fichier dans uploads/plans/',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Pinpoints (position des extincteurs sur les plans) ────────

CREATE TABLE pinpoints (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_id        INT UNSIGNED NOT NULL,
  extincteur_id  INT UNSIGNED NOT NULL,
  pos_x          DECIMAL(6,3) NOT NULL COMMENT 'Position en % (0-100)',
  pos_y          DECIMAL(6,3) NOT NULL COMMENT 'Position en % (0-100)',
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY un_plan_ext (plan_id, extincteur_id),
  FOREIGN KEY (plan_id)       REFERENCES plans(id)       ON DELETE CASCADE,
  FOREIGN KEY (extincteur_id) REFERENCES extincteurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  Données de démonstration
-- ============================================================

-- Utilisateurs
-- Mots de passe hashés avec password_hash() PHP
-- admin123 / tech123 / lecteur123

INSERT INTO utilisateurs (nom, email, mot_de_passe, role, permissions) VALUES
(
  'Admin Système', 'admin@gestionfeu.fr',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin',
  '["extincteurs.voir","extincteurs.modifier","extincteurs.supprimer","plans.voir","plans.gerer"]'
),
(
  'Jean Technicien', 'tech@gestionfeu.fr',
  '$2y$10$9GkCgWfUBB8l4qFR3S8K/uJj9mWGcxXOA2OT8bQ2U4t1I3LFGdvnO',
  'technicien',
  '["extincteurs.voir","extincteurs.modifier","plans.voir","plans.gerer"]'
),
(
  'Marie Lecteur', 'lecteur@gestionfeu.fr',
  '$2y$10$j7y5RNaGDjYIqLm0f6C5yujbijyE6xmKqKn2J3p5cS7v6tLkHqAwe',
  'lecteur',
  '["extincteurs.voir","plans.voir"]'
);

-- ⚠️  Les hashes ci-dessus sont des exemples.
--     Exécutez install.php après import pour créer les bons comptes.

-- Extincteurs
INSERT INTO extincteurs (numero_serie, type, marque, capacite, zone, localisation, date_installation, date_expiration, dernier_controle, prochain_controle, notes) VALUES
('EXT-RDC-001', 'CO2',    'Sicli',  5.0, 'RDC',              'Entrée principale, couloir A',     '2021-06-15', '2026-06-15', '2024-06-15', '2025-06-15', NULL),
('EXT-RDC-002', 'Eau',    'Anaf',   9.0, 'RDC',              'Couloir B, près du secrétariat',   '2020-09-01', '2025-09-01', '2024-09-01', '2025-03-01', NULL),
('EXT-ETG1-001','Poudre', 'Gloria', 6.0, 'Étage 1',          'Palier escalier Nord',             '2022-01-10', '2027-01-10', '2024-01-10', '2025-01-10', NULL),
('EXT-ETG1-002','CO2',    'Sicli',  2.0, 'Étage 1',          'Salle informatique 101',           '2019-03-20', '2024-03-20', '2023-03-20', '2024-03-20', 'EXPIRÉ — à remplacer en priorité'),
('EXT-ETG2-001','Eau',    'Anaf',   9.0, 'Étage 2',          'Couloir central, baie vitrée',     '2022-09-05', '2027-09-05', '2024-09-05', '2025-09-05', NULL),
('EXT-ETG3-001','CO2',    'Eurofeu',5.0, 'Étage 3',          'Couloir Est, sortie de secours',   '2023-02-14', '2028-02-14', '2024-02-14', '2025-02-14', NULL),
('EXT-SELF-001','Mousse', 'Gloria', 9.0, 'Self',             'Cuisine, à côté des fourneaux',    '2021-11-30', '2026-11-30', '2024-11-30', '2025-11-30', 'Adapté feux de cuisine (classe F)'),
('EXT-INT-001', 'Eau',    'Sicli',  9.0, 'Internat',         'Couloir dortoir A, niveau 1',      '2020-06-01', '2025-06-01', '2024-06-01', '2025-06-01', NULL),
('EXT-INT-002', 'Eau',    'Sicli',  9.0, 'Internat',         'Couloir dortoir B, niveau 2',      '2020-06-01', '2025-06-01', '2024-06-01', '2025-06-01', NULL),
('EXT-MUSCU-001','CO2',   'Anaf',   5.0, 'Muscu/BTS',        'Salle muscu, mur Est',             '2022-04-18', '2027-04-18', '2024-04-18', '2025-04-18', NULL),
('EXT-PSCI-001','Poudre', 'Eurofeu',6.0, 'Plateau Sciences', 'Labo chimie, près de la hotte',   '2021-09-01', '2026-09-01', '2024-09-01', '2025-09-01', 'Vérifier accessibilité mensuelle'),
('EXT-PSECU-001','CO2',   'Sicli',  5.0, 'Plateau Sécu',     'Couloir principal plateau sécu',   '2023-01-15', '2028-01-15', '2024-01-15', '2025-01-15', NULL);
