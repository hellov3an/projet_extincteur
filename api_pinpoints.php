<?php
// ============================================================
//  API simple pour les pinpoints (appelée en AJAX depuis plan_voir.php)
// ============================================================

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Vérif connexion
if (!currentUser()) {
    echo json_encode(['ok' => false, 'erreur' => 'Non connecté.']);
    exit;
}

// Lire le JSON envoyé
$body = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

$db = getDB();

// ── Ajouter un pinpoint ──────────────────────────────────────
if ($action === 'ajouter') {
    if (!peutFaire('extincteurs.modifier')) {
        echo json_encode(['ok' => false, 'erreur' => 'Permission refusée.']);
        exit;
    }

    $plan_id      = intval($body['plan_id'] ?? 0);
    $extincteur_id = intval($body['extincteur_id'] ?? 0);
    $pos_x        = floatval($body['pos_x'] ?? 0);
    $pos_y        = floatval($body['pos_y'] ?? 0);

    if (!$plan_id || !$extincteur_id) {
        echo json_encode(['ok' => false, 'erreur' => 'Données manquantes.']);
        exit;
    }

    // Supprimer si déjà placé (déplacer)
    $db->prepare('DELETE FROM pinpoints WHERE plan_id = ? AND extincteur_id = ?')
       ->execute([$plan_id, $extincteur_id]);

    $db->prepare('INSERT INTO pinpoints (plan_id, extincteur_id, pos_x, pos_y) VALUES (?, ?, ?, ?)')
       ->execute([$plan_id, $extincteur_id, $pos_x, $pos_y]);

    $pinpoint_id = $db->lastInsertId();

    // Récupérer les infos de l'extincteur pour la réponse
    $stmt = $db->prepare('SELECT * FROM extincteurs WHERE id = ?');
    $stmt->execute([$extincteur_id]);
    $ext = $stmt->fetch();
    $s   = statut($ext['date_expiration']);

    echo json_encode([
        'ok'           => true,
        'pinpoint_id'  => $pinpoint_id,
        'numero_serie' => $ext['numero_serie'],
        'type'         => $ext['type'],
        'marque'       => $ext['marque'] ?: '',
        'capacite'     => $ext['capacite'] ? $ext['capacite'] . ' kg/L' : '—',
        'zone'         => $ext['zone'],
        'localisation' => $ext['localisation'] ?: '—',
        'date_installation'  => $ext['date_installation']  ? date('d/m/Y', strtotime($ext['date_installation']))  : '—',
        'date_expiration'    => $ext['date_expiration']    ? date('d/m/Y', strtotime($ext['date_expiration']))    : '—',
        'dernier_controle'   => $ext['dernier_controle']   ? date('d/m/Y', strtotime($ext['dernier_controle']))   : '—',
        'prochain_controle'  => $ext['prochain_controle']  ? date('d/m/Y', strtotime($ext['prochain_controle']))  : '—',
        'notes'        => $ext['notes'] ?: '',
        'statut'       => $s,
        'statut_label' => statutLabel($s),
    ]);
    exit;
}

// ── Supprimer un pinpoint ────────────────────────────────────
if ($action === 'supprimer') {
    if (!peutFaire('extincteurs.modifier')) {
        echo json_encode(['ok' => false, 'erreur' => 'Permission refusée.']);
        exit;
    }

    $pinpoint_id = intval($body['pinpoint_id'] ?? 0);
    $db->prepare('DELETE FROM pinpoints WHERE id = ?')->execute([$pinpoint_id]);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'erreur' => 'Action inconnue.']);
