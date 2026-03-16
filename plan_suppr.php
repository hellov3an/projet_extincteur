<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();
if (!peutFaire('plans.gerer')) {
    flash('erreur', 'Permission refusée.');
    redirect(BASE_URL . '/plans.php');
}

$db  = getDB();
$id  = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM plans WHERE id = ?');
$stmt->execute([$id]);
$plan = $stmt->fetch();

if ($plan) {
    // Supprimer le fichier image
    if ($plan['fichier']) {
        @unlink(UPLOAD_DIR . $plan['fichier']);
    }
    // Supprimer les pinpoints (CASCADE en SQL mais on le fait explicitement)
    $db->prepare('DELETE FROM pinpoints WHERE plan_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM plans WHERE id = ?')->execute([$id]);
    flash('succes', 'Plan supprimé.');
} else {
    flash('erreur', 'Plan introuvable.');
}

redirect(BASE_URL . '/plans.php');
