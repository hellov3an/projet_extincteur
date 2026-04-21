<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Bloque les lecteurs (plus sécurisé qu'une simple permission)
if (estLecteur()) {
    writeLog('acces_refuse', currentUser()['email'], 'Tentative de modification d\'extincteur avec rôle lecteur');
    flash('erreur', "Les lecteurs ne peuvent pas modifier les extincteurs."); 
    redirect(BASE_URL . '/index.php');
}

if (!peutFaire('extincteurs.modifier')) {
    writeLog('acces_refuse', currentUser()['email'], 'Permission refusée pour modification extincteur');
    flash('erreur', "Permission refusée."); 
    redirect(BASE_URL . '/index.php');
}

$db      = getDB();
$id      = intval($_GET['id'] ?? 0);
$ext     = null;
$titre   = $id ? 'Modifier l\'extincteur' : 'Ajouter un extincteur';
$erreurs = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM extincteurs WHERE id = ?');
    $stmt->execute([$id]);
    $ext = $stmt->fetch();
    if (!$ext) { flash('erreur', 'Introuvable.'); redirect(BASE_URL . '/index.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'numero_serie'      => trim($_POST['numero_serie'] ?? ''),
        'type'              => $_POST['type'] ?? '',
        'marque'            => trim($_POST['marque'] ?? ''),
        'capacite'          => $_POST['capacite'] ?: null,
        'zone'              => trim($_POST['zone'] ?? ''),
        'localisation'      => trim($_POST['localisation'] ?? ''),
        'date_installation' => $_POST['date_installation'] ?: null,
        'date_expiration'   => $_POST['date_expiration'] ?: null,
        'dernier_controle'  => $_POST['dernier_controle'] ?: null,
        'prochain_controle' => $_POST['prochain_controle'] ?: null,
        'notes'             => trim($_POST['notes'] ?? ''),
    ];
    if (!$d['numero_serie']) $erreurs[] = 'Le numéro de série est obligatoire.';
    if (!$d['type'])         $erreurs[] = 'Le type est obligatoire.';
    if (!$d['zone'])         $erreurs[] = 'La zone est obligatoire.';
    if ($d['numero_serie']) {
        $c = $db->prepare('SELECT id FROM extincteurs WHERE numero_serie = ? AND id != ?');
        $c->execute([$d['numero_serie'], $id]);
        if ($c->fetch()) $erreurs[] = 'Ce numéro de série existe déjà.';
    }
    if (empty($erreurs)) {
        if ($id) {
            $d['id'] = $id;
            $db->prepare('UPDATE extincteurs SET numero_serie=:numero_serie, type=:type, marque=:marque,
                capacite=:capacite, zone=:zone, localisation=:localisation,
                date_installation=:date_installation, date_expiration=:date_expiration,
                dernier_controle=:dernier_controle, prochain_controle=:prochain_controle,
                notes=:notes WHERE id=:id')->execute($d);
        } else {
            $db->prepare('INSERT INTO extincteurs
                (numero_serie, type, marque, capacite, zone, localisation,
                 date_installation, date_expiration, dernier_controle, prochain_controle, notes)
                VALUES (:numero_serie, :type, :marque, :capacite, :zone, :localisation,
                 :date_installation, :date_expiration, :dernier_controle, :prochain_controle, :notes)')
               ->execute($d);
        }
        flash('succes', $id ? 'Extincteur modifié avec succès.' : 'Extincteur ajouté.');
        redirect(BASE_URL . '/index.php');
    }
    $ext = $d;
}

$types_dispo = ['Eau', 'CO2', 'Poudre', 'Mousse', 'Halon'];
$zones_dispo = ['RDC','Étage 1','Étage 2','Étage 3','Self','Internat','Muscu/BTS','Plateau Sciences','Plateau Sécu'];

include 'views/header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Inventaire</a>
  <span class="breadcrumb-sep">›</span>
  <span><?= e($titre) ?></span>
</div>

<div class="page-header">
  <div>
    <h2><?= e($titre) ?></h2>
    <?php if ($id): ?><p>Modification de l'extincteur #<?= $id ?></p><?php endif; ?>
  </div>
  <a href="index.php" class="btn btn-ghost">← Retour</a>
</div>

<?php if ($erreurs): ?>
<div class="flash flash-erreur">
  <span>⚠️</span>
  <div><ul><?php foreach ($erreurs as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<form method="POST">

  <!-- Identification -->
  <div class="form-card">
    <div class="form-card-hd">
      <div class="card-icon" style="background:#eff6ff">🔖</div>
      <h3>Identification</h3>
    </div>
    <div class="form-card-bd">
      <div class="form-grid">
        <div class="form-group">
          <label>Numéro de série <em>*</em></label>
          <input type="text" name="numero_serie"
                 value="<?= e($ext['numero_serie'] ?? '') ?>"
                 placeholder="EXT-RDC-001" required>
        </div>
        <div class="form-group">
          <label>Type <em>*</em></label>
          <select name="type" required>
            <option value="">— Sélectionner —</option>
            <?php foreach ($types_dispo as $t): ?>
            <option value="<?= $t ?>" <?= ($ext['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Marque</label>
          <input type="text" name="marque"
                 value="<?= e($ext['marque'] ?? '') ?>"
                 placeholder="Sicli, Gloria, Eurofeu…">
        </div>
        <div class="form-group">
          <label>Capacité (kg / L)</label>
          <input type="number" name="capacite" step="0.1" min="0"
                 value="<?= e($ext['capacite'] ?? '') ?>"
                 placeholder="5.0">
        </div>
      </div>
    </div>
  </div>

  <!-- Localisation -->
  <div class="form-card">
    <div class="form-card-hd">
      <div class="card-icon" style="background:#f0fdf4">📍</div>
      <h3>Localisation</h3>
    </div>
    <div class="form-card-bd">
      <div class="form-grid">
        <div class="form-group">
          <label>Zone <em>*</em></label>
          <select name="zone" required>
            <option value="">— Sélectionner —</option>
            <?php foreach ($zones_dispo as $z): ?>
            <option value="<?= $z ?>" <?= ($ext['zone'] ?? '') === $z ? 'selected' : '' ?>><?= $z ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Emplacement précis</label>
          <input type="text" name="localisation"
                 value="<?= e($ext['localisation'] ?? '') ?>"
                 placeholder="Entrée principale, couloir A…">
        </div>
      </div>
    </div>
  </div>

  <!-- Dates -->
  <div class="form-card">
    <div class="form-card-hd">
      <div class="card-icon" style="background:#fffbeb">📅</div>
      <h3>Dates et contrôles</h3>
    </div>
    <div class="form-card-bd">
      <div class="form-grid">
        <div class="form-group">
          <label>Date d'installation</label>
          <input type="date" name="date_installation"
                 value="<?= e($ext['date_installation'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Date d'expiration</label>
          <input type="date" name="date_expiration"
                 value="<?= e($ext['date_expiration'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Dernier contrôle</label>
          <input type="date" name="dernier_controle"
                 value="<?= e($ext['dernier_controle'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Prochain contrôle</label>
          <input type="date" name="prochain_controle"
                 value="<?= e($ext['prochain_controle'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Notes -->
  <div class="form-card">
    <div class="form-card-hd">
      <div class="card-icon" style="background:#f5f3ff">📝</div>
      <h3>Notes</h3>
    </div>
    <div class="form-card-bd">
      <div class="form-group">
        <textarea name="notes"
                  placeholder="Informations complémentaires, observations…"><?= e($ext['notes'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-card-ft">
      <button type="submit" class="btn btn-primary">
        <?= $id ? '💾 Enregistrer les modifications' : '✅ Ajouter l\'extincteur' ?>
      </button>
      <a href="index.php" class="btn btn-ghost">Annuler</a>
    </div>
  </div>

</form>

<?php include 'views/footer.php'; ?>
