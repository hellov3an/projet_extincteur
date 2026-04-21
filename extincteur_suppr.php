<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Bloque les lecteurs (plus sécurisé qu'une simple permission)
if (estLecteur()) {
    writeLog('acces_refuse', currentUser()['email'], 'Tentative de suppression d\'extincteur avec rôle lecteur');
    flash('erreur', "Les lecteurs ne peuvent pas supprimer les extincteurs.");
    redirect(BASE_URL . '/index.php');
}

if (!peutFaire('extincteurs.supprimer')) {
    writeLog('acces_refuse', currentUser()['email'], 'Permission refusée pour suppression extincteur');
    flash('erreur', "Permission refusée.");
    redirect(BASE_URL . '/index.php');
}

$db  = getDB();
$id  = intval($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT numero_serie FROM extincteurs WHERE id = ?');
$stmt->execute([$id]);
$ext = $stmt->fetch();

if ($ext) {
    // Supprimer aussi les marqueurs sur les plans
    $db->prepare('DELETE FROM pinpoints WHERE extincteur_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM extincteurs WHERE id = ?')->execute([$id]);
    flash('succes', 'Extincteur « ' . $ext['numero_serie'] . ' » supprimé.');
} else {
    flash('erreur', 'Extincteur introuvable.');
}

redirect(BASE_URL . '/index.php');
