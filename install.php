<?php
// ============================================================
//  GestionFeu — Script d'installation
//  Accéder une seule fois via : http://localhost/gestionfeu/install.php
//  SUPPRIMER CE FICHIER après installation !
// ============================================================

require_once 'config.php';

$dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('<p style="color:red">Connexion impossible : ' . $e->getMessage() . '</p>');
}

$ok = [];
$err = [];

function run(PDO $pdo, string $sql, string $label, array &$ok, array &$err): void {
    try {
        $pdo->exec($sql);
        $ok[] = $label;
    } catch (PDOException $e) {
        $err[] = $label . ' — ' . $e->getMessage();
    }
}

// Créer la base si elle n'existe pas
run($pdo, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", 'Création base de données', $ok, $err);
$pdo->exec("USE `" . DB_NAME . "`");

// Table utilisateurs
run($pdo, "
CREATE TABLE IF NOT EXISTS utilisateurs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100)  NOT NULL,
  email        VARCHAR(255)  NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255)  NOT NULL,
  role         ENUM('admin','technicien','lecteur') NOT NULL DEFAULT 'lecteur',
  permissions  JSON          NULL,
  actif        TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'Table utilisateurs', $ok, $err);

// Table extincteurs
run($pdo, "
CREATE TABLE IF NOT EXISTS extincteurs (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_serie      VARCHAR(100) NOT NULL UNIQUE,
  type              ENUM('Eau','CO2','Poudre','Mousse','Halon') NOT NULL,
  marque            VARCHAR(100) NULL,
  capacite          DECIMAL(6,2) NULL,
  zone              VARCHAR(100) NOT NULL,
  localisation      VARCHAR(255) NULL,
  date_installation DATE         NULL,
  date_expiration   DATE         NULL,
  dernier_controle  DATE         NULL,
  prochain_controle DATE         NULL,
  notes             TEXT         NULL,
  created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'Table extincteurs', $ok, $err);

// Table plans
run($pdo, "
CREATE TABLE IF NOT EXISTS plans (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(100) NOT NULL,
  zone        VARCHAR(100) NULL,
  description TEXT         NULL,
  fichier     VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'Table plans', $ok, $err);

// Table pinpoints
run($pdo, "
CREATE TABLE IF NOT EXISTS pinpoints (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_id       INT UNSIGNED NOT NULL,
  extincteur_id INT UNSIGNED NOT NULL,
  pos_x         DECIMAL(6,3) NOT NULL,
  pos_y         DECIMAL(6,3) NOT NULL,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY un_plan_ext (plan_id, extincteur_id),
  FOREIGN KEY (plan_id)       REFERENCES plans(id)       ON DELETE CASCADE,
  FOREIGN KEY (extincteur_id) REFERENCES extincteurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'Table pinpoints', $ok, $err);

// Comptes de démo
$comptes = [
    ['Admin Système',  'admin@gestionfeu.fr',    'admin123',   'admin',      '["extincteurs.voir","extincteurs.modifier","extincteurs.supprimer","plans.voir","plans.gerer"]'],
    ['Jean Technicien','tech@gestionfeu.fr',      'tech123',    'technicien', '["extincteurs.voir","extincteurs.modifier","plans.voir","plans.gerer"]'],
    ['Marie Lecteur',  'lecteur@gestionfeu.fr',   'lecteur123', 'lecteur',    '["extincteurs.voir","plans.voir"]'],
];

foreach ($comptes as [$nom, $email, $mdp, $role, $perms]) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO utilisateurs (nom, email, mot_de_passe, role, permissions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $email, password_hash($mdp, PASSWORD_DEFAULT), $role, $perms]);
        $ok[] = "Compte : $email ($role)";
    } catch (PDOException $e) {
        $err[] = "Compte $email — " . $e->getMessage();
    }
}

// Extincteurs de démo
$extincteurs = [
    ['EXT-RDC-001','CO2','Sicli',5,'RDC','Entrée principale','2021-06-15','2026-06-15','2024-06-15','2025-06-15',null],
    ['EXT-RDC-002','Eau','Anaf',9,'RDC','Couloir B, secrétariat','2020-09-01','2025-09-01','2024-09-01','2025-03-01',null],
    ['EXT-ETG1-001','Poudre','Gloria',6,'Étage 1','Palier escalier Nord','2022-01-10','2027-01-10','2024-01-10','2025-01-10',null],
    ['EXT-ETG1-002','CO2','Sicli',2,'Étage 1','Salle informatique 101','2019-03-20','2024-03-20','2023-03-20','2024-03-20','EXPIRÉ — à remplacer'],
    ['EXT-ETG2-001','Eau','Anaf',9,'Étage 2','Couloir central','2022-09-05','2027-09-05','2024-09-05','2025-09-05',null],
    ['EXT-SELF-001','Mousse','Gloria',9,'Self','Cuisine, fourneaux','2021-11-30','2026-11-30','2024-11-30','2025-11-30','Classe F'],
    ['EXT-INT-001','Eau','Sicli',9,'Internat','Dortoir A niveau 1','2020-06-01','2025-06-01','2024-06-01','2025-06-01',null],
    ['EXT-PSCI-001','Poudre','Eurofeu',6,'Plateau Sciences','Labo chimie','2021-09-01','2026-09-01','2024-09-01','2025-09-01','Vérifier mensuel'],
];

$sql = "INSERT IGNORE INTO extincteurs (numero_serie,type,marque,capacite,zone,localisation,date_installation,date_expiration,dernier_controle,prochain_controle,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $pdo->prepare($sql);
foreach ($extincteurs as $e) {
    try { $stmt->execute($e); $ok[] = "Extincteur : {$e[0]}"; }
    catch (PDOException $ex) { $err[] = "Extincteur {$e[0]} — " . $ex->getMessage(); }
}

// Dossier uploads
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    $ok[] = 'Dossier uploads/plans/ créé';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation GestionFeu</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0d1117; color: #e6edf3; padding: 40px; }
        h1   { color: #fa5252; margin-bottom: 24px; }
        h2   { color: #8b949e; font-size: 1rem; margin: 20px 0 8px; }
        .ok  { color: #3fb950; margin: 4px 0; }
        .err { color: #f85149; margin: 4px 0; }
        .box { background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 20px; max-width: 600px; }
        a    { color: #fa5252; }
    </style>
</head>
<body>
<div class="box">
    <h1>🔥 Installation GestionFeu</h1>

    <h2>✅ Succès (<?= count($ok) ?>)</h2>
    <?php foreach ($ok as $msg): ?><p class="ok">✓ <?= htmlspecialchars($msg) ?></p><?php endforeach; ?>

    <?php if ($err): ?>
    <h2>❌ Erreurs (<?= count($err) ?>)</h2>
    <?php foreach ($err as $msg): ?><p class="err">✗ <?= htmlspecialchars($msg) ?></p><?php endforeach; ?>
    <?php endif; ?>

    <hr style="border-color:#30363d; margin: 20px 0">

    <p><strong>Comptes créés :</strong></p>
    <ul style="line-height:2; color:#8b949e">
        <li>admin@gestionfeu.fr → <code>admin123</code> (Admin)</li>
        <li>tech@gestionfeu.fr → <code>tech123</code> (Technicien)</li>
        <li>lecteur@gestionfeu.fr → <code>lecteur123</code> (Lecteur)</li>
    </ul>

    <p style="margin-top:20px; color:#f85149">
        ⚠️ <strong>Supprimez ce fichier install.php maintenant !</strong>
    </p>
    <a href="login.php">→ Aller à la connexion</a>
</div>
</body>
</html>
