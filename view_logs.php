<?php
// Affiche les logs de connexion pour debug
require_once 'config.php';
require_once 'includes/auth.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logs de Connexion — GestionFeu</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/style.css">
  <style>
    .logs-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .log-entry {
      border: 1px solid #e0e0e0;
      padding: 15px;
      margin: 10px 0;
      border-radius: 4px;
      background: #fafafa;
      font-family: monospace;
      font-size: 12px;
      overflow-x: auto;
    }
    .log-error { background: #ffebee; border-left: 4px solid #f44336; }
    .log-success { background: #e8f5e9; border-left: 4px solid #4caf50; }
    .log-warning { background: #fff3e0; border-left: 4px solid #ff9800; }
    .log-info { background: #e3f2fd; border-left: 4px solid #2196f3; }
  </style>
</head>
<body>
<div class="logs-container">
  <h1>📋 Logs de Connexion</h1>
  <p>Affichage des 100 dernières entrées de log</p>
  
  <?php
  $logs_dir = __DIR__ . '/logs';
  
  if (!is_dir($logs_dir) || !is_readable($logs_dir)) {
    echo '<div class="log-entry log-warning">Aucun fichier de log trouvé.</div>';
  } else {
    $all_logs = [];
    
    // Lire tous les fichiers .log
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
    
    // Prend les 100 dernières et affiche en ordre inverse
    $all_logs = array_slice(array_reverse($all_logs), 0, 100);
    
    if (empty($all_logs)) {
      echo '<div class="log-entry log-info">Aucune entrée de log disponible.</div>';
    } else {
      foreach ($all_logs as $log) {
        $data = json_decode($log, true);
        if (!$data) continue;
        
        // Détermine la classe CSS selon le type
        $class = 'log-info';
        if (strpos($data['type'], 'echec') !== false || strpos($data['type'], 'erreur') !== false) {
          $class = 'log-error';
        } elseif (strpos($data['type'], 'succes') !== false) {
          $class = 'log-success';
        } elseif (strpos($data['type'], 'warning') !== false) {
          $class = 'log-warning';
        }
        
        echo '<div class="log-entry ' . $class . '">';
        echo '<strong>[' . $data['timestamp'] . ']</strong> ';
        echo '<strong>' . $data['type'] . '</strong> | ';
        echo 'Email: <code>' . htmlspecialchars($data['email']) . '</code> | ';
        echo 'IP: <code>' . htmlspecialchars($data['ip']) . '</code><br>';
        echo 'Message: ' . htmlspecialchars($data['message']);
        if (isset($data['user_id'])) echo ' | User ID: ' . $data['user_id'];
        if (isset($data['role'])) echo ' | Role: ' . $data['role'];
        echo '</div>';
      }
    }
  }
  ?>
  
  <p style="margin-top: 30px; color: #666; font-size: 12px;">
    <strong>Note:</strong> Les logs sont stockés dans le dossier <code>/logs/</code> au format JSON (un log par ligne).
    Les fichiers sont nommés par date (YYYY-MM-DD.log).
  </p>
</div>
</body>
</html>
