<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db    = getDB();
$titre = 'Plans';
$plans = $db->query('
    SELECT p.*, COUNT(pp.id) AS nb_pins
    FROM plans p
    LEFT JOIN pinpoints pp ON pp.plan_id = p.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
')->fetchAll();

include 'views/header.php';
?>

<div class="page-header">
  <div>
    <h2>Plans du bâtiment</h2>
    <p><?= count($plans) ?> plan(s) enregistré(s)</p>
  </div>
  <?php if (peutFaire('plans.gerer')): ?>
  <a href="plan_form.php" class="btn btn-primary">+ Ajouter un plan</a>
  <?php endif; ?>
</div>

<?php if (empty($plans)): ?>
<div class="empty-state">
  <div class="ei">🗺️</div>
  <p>Aucun plan enregistré. Uploadez un plan pour commencer à localiser les extincteurs.</p>
  <?php if (peutFaire('plans.gerer')): ?>
  <a href="plan_form.php" class="btn btn-primary">Uploader le premier plan</a>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="plans-grid">
  <?php foreach ($plans as $plan): ?>
  <div class="plan-card">
    <a href="plan_voir.php?id=<?= $plan['id'] ?>">
      <div class="plan-thumb">
        <img src="<?= BASE_URL ?>/uploads/plans/<?= e($plan['fichier']) ?>"
             alt="<?= e($plan['nom']) ?>">
        <span class="plan-chip">📍 <?= $plan['nb_pins'] ?></span>
      </div>
    </a>
    <div class="plan-info">
      <strong><?= e($plan['nom']) ?></strong>
      <?php if ($plan['zone']): ?>
      <span class="pz">📍 <?= e($plan['zone']) ?></span>
      <?php endif; ?>
      <?php if ($plan['description']): ?>
      <p class="pd"><?= e($plan['description']) ?></p>
      <?php endif; ?>
    </div>
    <div class="plan-actions">
      <a href="plan_voir.php?id=<?= $plan['id'] ?>" class="btn btn-sm btn-primary">Ouvrir</a>
      <?php if (peutFaire('plans.gerer')): ?>
      <a href="plan_form.php?id=<?= $plan['id'] ?>" class="btn btn-sm">Modifier</a>
      <a href="plan_suppr.php?id=<?= $plan['id'] ?>"
         class="btn btn-sm btn-danger"
         onclick="return confirm('Supprimer ce plan et ses repères ?')">Suppr.</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'views/footer.php'; ?>
