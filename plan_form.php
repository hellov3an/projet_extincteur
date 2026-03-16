<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
if (!peutFaire('plans.gerer')) { flash('erreur', 'Permission refusée.'); redirect(BASE_URL . '/plans.php'); }

$db      = getDB();
$id      = intval($_GET['id'] ?? 0);
$plan    = null;
$titre   = $id ? 'Modifier le plan' : 'Ajouter un plan';
$erreurs = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM plans WHERE id = ?');
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    if (!$plan) { flash('erreur', 'Plan introuvable.'); redirect(BASE_URL . '/plans.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom'] ?? '');
    $zone        = trim($_POST['zone'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$nom) $erreurs[] = 'Le nom est obligatoire.';

    $nouveau_fichier = null;
    if (!empty($_FILES['image']['name'])) {
        $nouveau_fichier = uploadImage($_FILES['image']);
        if (!$nouveau_fichier) $erreurs[] = 'Image invalide (JPG/PNG/WebP, max 10 Mo).';
    } elseif (!$id) {
        $erreurs[] = 'Une image est obligatoire.';
    }

    if (empty($erreurs)) {
        if ($id) {
            $fichier = $nouveau_fichier ?? $plan['fichier'];
            if ($nouveau_fichier && $plan['fichier']) @unlink(UPLOAD_DIR . $plan['fichier']);
            $db->prepare('UPDATE plans SET nom=?, zone=?, description=?, fichier=? WHERE id=?')
               ->execute([$nom, $zone, $description, $fichier, $id]);
        } else {
            $db->prepare('INSERT INTO plans (nom, zone, description, fichier) VALUES (?, ?, ?, ?)')
               ->execute([$nom, $zone, $description, $nouveau_fichier]);
        }
        flash('succes', $id ? 'Plan mis à jour.' : 'Plan ajouté.');
        redirect(BASE_URL . '/plans.php');
    }
}

$zones_dispo = ['RDC','Étage 1','Étage 2','Étage 3','Self','Internat','Muscu/BTS','Plateau Sciences','Plateau Sécu'];

include 'views/header.php';
?>

<div class="breadcrumb">
  <a href="plans.php">Plans</a>
  <span class="breadcrumb-sep">›</span>
  <span><?= e($titre) ?></span>
</div>

<div class="page-header">
  <div>
    <h2><?= e($titre) ?></h2>
    <?php if ($id): ?><p>Modification du plan "<?= e($plan['nom']) ?>"</p><?php endif; ?>
  </div>
  <a href="plans.php" class="btn btn-ghost">← Retour</a>
</div>

<?php if ($erreurs): ?>
<div class="flash flash-erreur">
  <span>⚠️</span>
  <div><ul><?php foreach ($erreurs as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="max-width:640px">

  <div class="form-card">
    <div class="form-card-hd">
      <div class="card-icon" style="background:#f0fdf4">🖼️</div>
      <h3>Image du plan</h3>
    </div>
    <div class="form-card-bd">
      <?php if ($id && $plan['fichier']): ?>
      <div>
        <p class="text-2" style="font-size:.82rem;margin-bottom:8px">Image actuelle :</p>
        <img src="<?= BASE_URL ?>/uploads/plans/<?= e($plan['fichier']) ?>"
             style="max-height:180px; border-radius:var(--radius); border:1px solid var(--border);">
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label><?= $id ? 'Remplacer l\'image (optionnel)' : 'Image du plan' ?> <?= !$id ? '<em>*</em>' : '' ?></label>
        <input type="file" name="image" accept="image/*" <?= !$id ? 'required' : '' ?>>
        <small>JPG, PNG, WebP, GIF — max 10 Mo</small>
      </div>
    </div>
  </div>

  <div class="form-card">
    <div class="form-card-hd">
      <div class="card-icon" style="background:#eff6ff">📋</div>
      <h3>Informations</h3>
    </div>
    <div class="form-card-bd">
      <div class="form-grid">
        <div class="form-group">
          <label>Nom du plan <em>*</em></label>
          <input type="text" name="nom"
                 value="<?= e($_POST['nom'] ?? $plan['nom'] ?? '') ?>"
                 placeholder="Plan RDC, Étage 1…" required>
        </div>
        <div class="form-group">
          <label>Zone associée</label>
          <select name="zone">
            <option value="">Non spécifié</option>
            <?php foreach ($zones_dispo as $z): ?>
            <?php $sel = ($_POST['zone'] ?? $plan['zone'] ?? '') === $z ? 'selected' : ''; ?>
            <option value="<?= $z ?>" <?= $sel ?>><?= $z ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description"
                  placeholder="Description du plan, informations utiles…"><?= e($_POST['description'] ?? $plan['description'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-card-ft">
      <button type="submit" class="btn btn-primary"><?= $id ? '💾 Enregistrer' : '⬆️ Uploader le plan' ?></button>
      <a href="plans.php" class="btn btn-ghost">Annuler</a>
    </div>
  </div>

</form>

<?php include 'views/footer.php'; ?>
