<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if ($id === currentUser()['id']) {
    flash('erreur', 'Vous ne pouvez pas modifier votre propre statut.');
    redirect(BASE_URL . '/admin.php');
}

$stmt = $db->prepare('SELECT actif FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();

if ($u) {
    $nouveau = $u['actif'] ? 0 : 1;
    $db->prepare('UPDATE utilisateurs SET actif = ? WHERE id = ?')->execute([$nouveau, $id]);
    flash('succes', 'Compte ' . ($nouveau ? 'activé' : 'désactivé') . '.');
}

redirect(BASE_URL . '/admin.php');
