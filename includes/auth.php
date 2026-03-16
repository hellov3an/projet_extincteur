<?php
// ============================================================
//  Gestion de l'authentification et des sessions
// ============================================================

require_once __DIR__ . '/db.php';

// Démarre la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si l'utilisateur est connecté, sinon redirige vers login
function requireLogin(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Vérifie si l'utilisateur est admin
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/index.php?erreur=acces_refuse');
        exit;
    }
}

// Retourne l'utilisateur connecté
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

// Vérifie une permission (les admins ont tout)
function peutFaire(string $permission): bool {
    $user = currentUser();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;

    $permissions = json_decode($user['permissions'] ?? '[]', true);
    return in_array($permission, $permissions);
}

// Connecte un utilisateur (vérifie email + mot de passe)
function connecter(string $email, string $motdepasse): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($motdepasse, $user['mot_de_passe'])) {
        // On ne stocke pas le mot de passe en session
        unset($user['mot_de_passe']);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

// Déconnecte l'utilisateur
function deconnecter(): void {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
