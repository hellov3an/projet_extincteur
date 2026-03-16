<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db    = getDB();
$titre = 'Inventaire';

// Filtres
$search = trim($_GET['search'] ?? '');
$type   = $_GET['type']  ?? '';
$zone   = $_GET['zone']  ?? '';

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(numero_serie LIKE ? OR marque LIKE ? OR localisation LIKE ?)';
    $like     = "%$search%";
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($type) { $where[] = 'type = ?'; $params[] = $type; }
if ($zone) { $where[] = 'zone = ?'; $params[] = $zone; }

$sql = 'SELECT * FROM extincteurs';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY zone, numero_serie';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$extincteurs = $stmt->fetchAll();

// Stats
$stats = $db->query('
    SELECT
        COUNT(*) AS total,
        SUM(date_expiration < CURDATE()) AS expires,
        SUM(date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS bientot,
        COUNT(DISTINCT zone) AS zones
    FROM extincteurs
')->fetch();
$valides = $stats['total'] - $stats['expires'] - $stats['bientot'];

$types = $db->query('SELECT DISTINCT type FROM extincteurs ORDER BY type')->fetchAll(PDO::FETCH_COLUMN);
$zones = $db->query('SELECT DISTINCT zone FROM extincteurs ORDER BY zone')->fetchAll(PDO::FETCH_COLUMN);

include 'views/header.php';
?>

<div class="page-header">
  <div>
    <h2>Inventaire des extincteurs</h2>
    <p><?= $stats['total'] ?> appareil(s) · <?= $stats['zones'] ?> zone(s)</p>
  </div>
  <div class="page-header-actions">
    <?php if (peutFaire('extincteurs.modifier')): ?>
    <a href="extincteur_form.php" class="btn btn-primary">+ Ajouter un extincteur</a>
    <?php endif; ?>
  </div>
</div>

<!-- Statistiques -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon si-blue">📦</div>
    <div class="stat-val"><?= $stats['total'] ?></div>
    <div class="stat-lbl">Total</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-green">✅</div>
    <div class="stat-val"><?= $valides ?></div>
    <div class="stat-lbl">Valides</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-amber">⏳</div>
    <div class="stat-val"><?= $stats['bientot'] ?></div>
    <div class="stat-lbl">Bientôt expirés</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-red">🚨</div>
    <div class="stat-val"><?= $stats['expires'] ?></div>
    <div class="stat-lbl">Expirés</div>
  </div>
</div>

<!-- Barre de filtres -->
<div class="toolbar">
  <form method="GET" style="display:contents">
    <input type="text" name="search"
           placeholder="🔍  Numéro, marque, localisation…"
           value="<?= e($search) ?>">
    <select name="type">
      <option value="">Tous les types</option>
      <?php foreach ($types as $t): ?>
      <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="zone">
      <option value="">Toutes les zones</option>
      <?php foreach ($zones as $z): ?>
      <option value="<?= e($z) ?>" <?= $zone === $z ? 'selected' : '' ?>><?= e($z) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrer</button>
    <?php if ($search || $type || $zone): ?>
    <a href="index.php" class="btn btn-ghost">✕ Réinitialiser</a>
    <?php endif; ?>
    <div class="toolbar-sep"></div>
    <span class="text-2" style="font-size:.85rem"><?= count($extincteurs) ?> résultat(s)</span>
  </form>
</div>

<!-- Tableau -->
<?php if (empty($extincteurs)): ?>
<div class="empty-state">
  <div class="ei">🔍</div>
  <p>Aucun extincteur trouvé<?= $search ? ' pour « ' . e($search) . ' »' : '' ?>.</p>
  <?php if (!$search && !$type && !$zone && peutFaire('extincteurs.modifier')): ?>
  <a href="extincteur_form.php" class="btn btn-primary">Ajouter le premier extincteur</a>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="table-card">
  <table>
    <thead>
      <tr>
        <th>N° Série</th>
        <th>Type</th>
        <th>Marque</th>
        <th>Zone</th>
        <th>Localisation</th>
        <th>Expiration</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($extincteurs as $ext):
      $s = statut($ext['date_expiration']);
    ?>
    <tr>
      <td><span class="td-code"><?= e($ext['numero_serie']) ?></span></td>
      <td><span class="badge badge-type"><?= e($ext['type']) ?></span></td>
      <td><?= e($ext['marque']) ?: '<span class="text-3">—</span>' ?></td>
      <td><?= e($ext['zone']) ?></td>
      <td class="text-2"><?= e($ext['localisation']) ?: '—' ?></td>
      <td class="<?= $s === 'expire' ? 'text-red' : ($s === 'bientot' ? 'text-warn' : '') ?>">
        <?= formatDate($ext['date_expiration']) ?>
      </td>
      <td><span class="badge badge-<?= $s ?>"><?= statutLabel($s) ?></span></td>
      <td>
        <div class="td-actions">
          <a href="extincteur_voir.php?id=<?= $ext['id'] ?>" class="btn btn-sm">Voir</a>
          <?php if (peutFaire('extincteurs.modifier')): ?>
          <a href="extincteur_form.php?id=<?= $ext['id'] ?>" class="btn btn-sm btn-ghost">Modifier</a>
          <?php endif; ?>
          <?php if (peutFaire('extincteurs.supprimer')): ?>
          <a href="extincteur_suppr.php?id=<?= $ext['id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('Supprimer cet extincteur ?')">Suppr.</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php include 'views/footer.php'; ?>
