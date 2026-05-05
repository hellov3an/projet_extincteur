<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db   = getDB();
$id   = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM extincteurs WHERE id = ?');
$stmt->execute([$id]);
$ext  = $stmt->fetch();

if (!$ext) { flash('erreur', 'Extincteur introuvable.'); redirect(BASE_URL . '/index.php'); }

$s     = statut($ext['date_expiration']);
$titre = $ext['numero_serie'];

include 'views/header.php';
?>

<div class="breadcrumb">
  <a href="index.php">Inventaire</a>
  <span class="breadcrumb-sep">›</span>
  <span><?= e($ext['numero_serie']) ?></span>
</div>

<div class="page-header">
  <div>
    <h2><?= e($ext['numero_serie']) ?></h2>
    <p><?= e($ext['type']) ?> · <?= e($ext['zone']) ?></p>
  </div>
  <div class="page-header-actions">
    <?php if (!estLecteur() && peutFaire('extincteurs.modifier')): ?>
    <a href="extincteur_form.php?id=<?= $ext['id'] ?>" class="btn btn-primary">✏️ Modifier</a>
    <?php endif; ?>
    <?php if (!estLecteur() && peutFaire('extincteurs.supprimer')): ?>
    <a href="extincteur_suppr.php?id=<?= $ext['id'] ?>"
       class="btn btn-danger"
       onclick="return confirm('Supprimer cet extincteur ?')">Supprimer</a>
    <?php endif; ?>
    <a href="index.php" class="btn btn-ghost">← Retour</a>
  </div>
</div>

<!-- Fiche détail -->
<div class="detail-hero">
  <div class="detail-top">
    <div class="detail-top-left">
      <h2><?= e($ext['numero_serie']) ?></h2>
      <p><?= e($ext['marque'] ?: '—') ?> · Capacité : <?= $ext['capacite'] ? e($ext['capacite']) . ' kg/L' : '—' ?></p>
    </div>
    <span class="badge badge-<?= $s ?>">
      <?= statutLabel($s) ?>
    </span>
  </div>

  <div class="detail-body">
    <div class="detail-section">
      <h4>Identification</h4>
      <div class="detail-dl">
        <div class="dl-row">
          <div class="dl-key">Type</div>
          <div class="dl-val"><?= e($ext['type']) ?></div>
        </div>
        <div class="dl-row">
          <div class="dl-key">Marque</div>
          <div class="dl-val"><?= e($ext['marque']) ?: '—' ?></div>
        </div>
        <div class="dl-row">
          <div class="dl-key">Capacité</div>
          <div class="dl-val"><?= $ext['capacite'] ? e($ext['capacite']) . ' kg/L' : '—' ?></div>
        </div>
      </div>
    </div>

    <div class="detail-section">
      <h4>Localisation</h4>
      <div class="detail-dl">
        <div class="dl-row">
          <div class="dl-key">Zone</div>
          <div class="dl-val"><?= e($ext['zone']) ?></div>
        </div>
        <div class="dl-row">
          <div class="dl-key">Emplacement</div>
          <div class="dl-val"><?= e($ext['localisation']) ?: '—' ?></div>
        </div>
      </div>
    </div>

    <div class="detail-section">
      <h4>Dates</h4>
      <div class="detail-dl">
        <div class="dl-row">
          <div class="dl-key">Installation</div>
          <div class="dl-val"><?= formatDate($ext['date_installation']) ?></div>
        </div>
        <div class="dl-row">
          <div class="dl-key">Expiration</div>
          <div class="dl-val <?= $s === 'expire' ? 'text-red' : ($s === 'bientot' ? 'text-warn' : '') ?>">
            <?= formatDate($ext['date_expiration']) ?>
          </div>
        </div>
        <div class="dl-row">
          <div class="dl-key">Dernier contrôle</div>
          <div class="dl-val"><?= formatDate($ext['dernier_controle']) ?></div>
        </div>
        <div class="dl-row">
          <div class="dl-key">Prochain contrôle</div>
          <div class="dl-val"><?= formatDate($ext['prochain_controle']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($ext['notes']): ?>
  <div class="detail-notes">
    <strong>Notes :</strong> <?= nl2br(e($ext['notes'])) ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'views/footer.php'; ?>

