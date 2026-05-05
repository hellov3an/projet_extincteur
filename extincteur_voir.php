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

<!-- Section des révisions récentes -->
<?php
$stmt = $db->prepare('
    SELECT r.*, u.nom as technicien_nom
    FROM revisions_extincteurs r
    LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
    WHERE r.extincteur_id = ?
    ORDER BY r.date_revision DESC
    LIMIT 3
');
$stmt->execute([$id]);
$revisions_recentes = $stmt->fetchAll();
?>

<div class="revisions-section" style="margin-top: 2rem;">
    <div class="detail-hero">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>📋 Historique des révisions</h3>
            <?php if (!estLecteur() && peutFaire('extincteurs.modifier')): ?>
            <a href="revision_form.php?id=<?= $ext['id'] ?>" class="btn btn-sm btn-primary">➕ Ajouter une révision</a>
            <?php endif; ?>
        </div>

        <?php if (empty($revisions_recentes)): ?>
        <div class="alert-box" style="background: #e3f2fd; padding: 1rem; border-radius: 0.5rem; color: #1565c0;">
            <strong>Aucune révision enregistrée.</strong> Cliquez ci-dessus pour enregistrer la première révision.
        </div>
        <?php else: ?>
        <div class="revisions-list">
            <?php foreach ($revisions_recentes as $rev): ?>
            <div class="revision-item" style="padding: 1rem; border: 1px solid #ddd; border-radius: 0.5rem; margin-bottom: 0.75rem; background: #fafafa;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;">
                            <strong><?= formatDate($rev['date_revision']) ?></strong> — 
                            <span style="background: #e3f2fd; color: #1565c0; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.85rem;">
                                <?= typeMaintenanceLabel($rev['type_maintenance']) ?>
                            </span>
                        </div>
                        <div style="color: #666; font-size: 0.95rem; margin-bottom: 0.5rem;">
                            <strong><?= e($rev['entreprise']) ?></strong>
                            <?php if ($rev['contact']): ?>
                            — <?= e($rev['contact']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($rev['observations']): ?>
                        <div style="color: #555; font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <?= htmlspecialchars(substr($rev['observations'], 0, 150)) ?>
                            <?php if (strlen($rev['observations']) > 150): ?>...<?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <span class="conformite-badge-inline <?= conformiteClass($rev['conformite']) ?>" 
                              style="padding: 0.5rem 0.75rem; border-radius: 0.25rem; font-weight: 600;">
                            <?= conformiteLabel($rev['conformite']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 1rem; text-align: center;">
            <a href="revision_historique.php?id=<?= $ext['id'] ?>" class="btn btn-primary">
                📊 Voir l'historique complet
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/footer.php'; ?>

