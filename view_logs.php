<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$titre = 'Logs';
$logs_dir = __DIR__ . '/logs';

// Récupérer tous les logs
$all_logs = [];
$all_logs_data = [];

if (is_dir($logs_dir) && is_readable($logs_dir)) {
    $files = array_reverse(scandir($logs_dir));
    foreach ($files as $file) {
        if (substr($file, -4) === '.log') {
            $content = file_get_contents($logs_dir . '/' . $file);
            $lines = array_filter(explode("\n", $content));
            foreach ($lines as $line) {
                $all_logs[] = $line;
            }
        }
    }
}

// Inverser et limiter à 500 pour les calculs
$all_logs = array_slice(array_reverse($all_logs), 0, 500);

// Analyser les données
$stats = ['total' => 0, 'succes' => 0, 'echec' => 0, 'erreur' => 0, 'validation' => 0, 'acces_refuse' => 0];
$types = [];

foreach ($all_logs as $log) {
    $data = json_decode($log, true);
    if (!$data) continue;
    
    $stats['total']++;
    $all_logs_data[] = $data;
    
    if (strpos($data['type'], 'succes') !== false) {
        $stats['succes']++;
    } elseif (strpos($data['type'], 'echec') !== false) {
        $stats['echec']++;
    } elseif (strpos($data['type'], 'erreur') !== false) {
        $stats['erreur']++;
    } elseif (strpos($data['type'], 'validation') !== false) {
        $stats['validation']++;
    } elseif (strpos($data['type'], 'acces_refuse') !== false) {
        $stats['acces_refuse']++;
    }
    
    $types[$data['type']] = ($types[$data['type']] ?? 0) + 1;
}

// Filtre
$filter_type = $_GET['type'] ?? '';

if ($filter_type) {
    $all_logs_data = array_filter($all_logs_data, function($d) use ($filter_type) {
        return $d['type'] === $filter_type;
    });
}

// Affichage: 100 dernières
$all_logs_data = array_slice($all_logs_data, 0, 100);

// Pourcentages
$pct_succes = $stats['total'] > 0 ? round(($stats['succes'] / $stats['total']) * 100) : 0;
$pct_echec = $stats['total'] > 0 ? round((($stats['echec'] + $stats['erreur'] + $stats['validation'] + $stats['acces_refuse']) / $stats['total']) * 100) : 0;

include 'views/header.php';
?>

<div class="page-header">
  <div>
    <h2>📋 Logs de Connexion</h2>
    <p>Surveillance des tentatives de connexion et des erreurs</p>
  </div>
</div>

<!-- Statistiques -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon si-blue">📊</div>
    <div class="stat-val"><?= $stats['total'] ?></div>
    <div class="stat-lbl">Événements totals</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-green">✅</div>
    <div class="stat-val"><?= $pct_succes ?>%</div>
    <div class="stat-lbl">Succès (<?= $stats['succes'] ?>)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-red">❌</div>
    <div class="stat-val"><?= $pct_echec ?>%</div>
    <div class="stat-lbl">Erreurs (<?= $stats['echec'] + $stats['erreur'] + $stats['validation'] + $stats['acces_refuse'] ?>)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-amber">🚫</div>
    <div class="stat-val"><?= $stats['acces_refuse'] ?></div>
    <div class="stat-lbl">Accès refusés</div>
  </div>
</div>

<!-- Filtres -->
<div class="toolbar">
  <form method="GET" style="display:contents">
    <select name="type">
      <option value="">Tous les types</option>
      <?php foreach (array_keys($types) as $t): ?>
      <option value="<?= e($t) ?>" <?= $filter_type === $t ? 'selected' : '' ?>>
        <?= e($t) ?> (<?= $types[$t] ?>)
      </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrer</button>
    <?php if ($filter_type): ?>
    <a href="view_logs.php" class="btn btn-ghost">✕ Réinitialiser</a>
    <?php endif; ?>
    <div class="toolbar-sep"></div>
    <span class="text-2" style="font-size:.85rem"><?= count($all_logs_data) ?> résultat(s)</span>
  </form>
</div>

<!-- Tableau des logs -->
<?php if (empty($all_logs_data)): ?>
<div class="empty-state">
  <div class="ei">🔍</div>
  <p>Aucun log trouvé.</p>
</div>
<?php else: ?>
<div class="table-card">
  <table>
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>Type</th>
        <th>Email</th>
        <th>IP</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($all_logs_data as $data): 
      $class_type = 'log-info';
      if (strpos($data['type'], 'echec') !== false || strpos($data['type'], 'erreur') !== false) {
        $class_type = 'log-error';
      } elseif (strpos($data['type'], 'succes') !== false) {
        $class_type = 'log-success';
      } elseif (strpos($data['type'], 'acces') !== false) {
        $class_type = 'log-warning';
      } elseif (strpos($data['type'], 'validation') !== false) {
        $class_type = 'log-warning';
      }
    ?>
    <tr class="<?= $class_type ?>">
      <td class="text-2" style="font-size: 0.9rem;"><?= htmlspecialchars($data['timestamp']) ?></td>
      <td>
        <span class="badge" style="
          <?php if ($class_type === 'log-success'): ?>background:#e8f5e9;color:#2e7d32;
          <?php elseif ($class_type === 'log-error'): ?>background:#ffebee;color:#c62828;
          <?php elseif ($class_type === 'log-warning'): ?>background:#fff3e0;color:#e65100;
          <?php else: ?>background:#e3f2fd;color:#1565c0;<?php endif; ?>
        "><?= htmlspecialchars($data['type']) ?></span>
      </td>
      <td><code style="font-size: 0.85rem;"><?= htmlspecialchars($data['email']) ?></code></td>
      <td class="text-2" style="font-size: 0.9rem;"><?= htmlspecialchars($data['ip']) ?></td>
      <td class="text-2">
        <?= htmlspecialchars($data['message']) ?>
        <?php if (isset($data['role'])): ?>
          <br><small style="color: #999;">Role: <?= htmlspecialchars($data['role']) ?></small>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 6px; font-size: 0.9rem; color: #666;">
  <strong>📝 Informations:</strong> Les logs sont analysés à partir du dossier <code>/logs/</code> (format JSON, un log par ligne).
  Les fichiers sont nommés par date (YYYY-MM-DD.log). Affichage des 100 dernières entrées filtrées.
</div>

<?php include 'views/footer.php'; ?>
