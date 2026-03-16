<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

if ($id === currentUser()['id']) {
    flash('erreur', 'Vous ne pouvez pas supprimer votre propre compte.');
    redirect(BASE_URL . '/admin.php');
}

$stmt = $db->prepare('SELECT nom FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();

if ($u) {
    $db->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$id]);
    flash('succes', 'Utilisateur « ' . $u['nom'] . ' » supprimé.');
} else {
    flash('erreur', 'Utilisateur introuvable.');
}

redirect(BASE_URL . '/admin.php');
