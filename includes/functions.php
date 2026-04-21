<?php
// ============================================================
//  Fonctions utilitaires
// ============================================================

// Échappe une valeur pour l'affichage HTML (évite les XSS)
function e(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

// Calcule le statut d'un extincteur selon sa date d'expiration
function statut(string|null $date_expiration): string {
    if (!$date_expiration) return 'inconnu';
    $exp  = strtotime($date_expiration);
    $now  = time();
    $diff = ($exp - $now) / 86400; // jours restants
    if ($diff < 0)  return 'expire';
    if ($diff < 30) return 'bientot';
    return 'valide';
}

// Retourne le libellé du statut
function statutLabel(string $statut): string {
    return match($statut) {
        'valide'  => 'Valide',
        'bientot' => 'Bientôt',
        'expire'  => 'Expiré',
        default   => 'Inconnu',
    };
}

// Retourne la classe CSS du statut
function statutClass(string $statut): string {
    return match($statut) {
        'valide'  => 'status-valide',
        'bientot' => 'status-bientot',
        'expire'  => 'status-expire',
        default   => 'status-inconnu',
    };
}

// Formate une date "2025-06-15" → "15/06/2025"
function formatDate(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

// Stocke un message flash en session
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Affiche et supprime le message flash
function afficherFlash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $class = $f['type'] === 'succes' ? 'flash-succes' : 'flash-erreur';
        echo '<div class="flash ' . $class . '">' . e($f['message']) . '</div>';
    }
}

// Redirige vers une URL
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Upload d'une image de plan, retourne le nom du fichier ou false
function uploadImage(array $file): string|false {
    $types_autorises = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if ($file['error'] !== UPLOAD_ERR_OK)          return false;
    if (!in_array($file['type'], $types_autorises)) return false;
    if ($file['size'] > UPLOAD_MAX)                 return false;

    // Créer le dossier automatiquement s'il n'existe pas (Windows + Linux)
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) return false;
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nom  = uniqid('plan_') . '.' . $ext;
    $dest = UPLOAD_DIR . $nom;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return $nom;
}

// Enregistre un log de connexion
function writeLog(string $type, string $email, string $message, ?array $extra = null): void {
    $logs_dir = __DIR__ . '/../logs';
    
    // Crée le dossier logs s'il n'existe pas
    if (!is_dir($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    
    // Nomme le fichier log par date (format: YYYY-MM-DD.log)
    $log_file = $logs_dir . '/' . date('Y-m-d') . '.log';
    
    // Construit le message de log
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 0, 100);
    
    $log_data = [
        'timestamp'  => $timestamp,
        'type'       => $type,
        'email'      => $email,
        'ip'         => $ip,
        'user_agent' => $user_agent,
        'message'    => $message,
    ];
    
    // Ajoute les données supplémentaires si fournies
    if ($extra) {
        $log_data = array_merge($log_data, $extra);
    }
    
    // Convertit en JSON et ajoute au fichier
    $log_line = json_encode($log_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}
