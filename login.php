<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (currentUser()) redirect(BASE_URL . '/index.php');

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email'] ?? '');
        $mdp   = $_POST['mot_de_passe'] ?? '';
        
        // Validations de base
        if (empty($email)) {
            $erreur = 'Veuillez entrer votre email.';
            writeLog('validation_erreur', 'EMPTY', 'Email vide soumis');
        } elseif (empty($mdp)) {
            $erreur = 'Veuillez entrer votre mot de passe.';
            writeLog('validation_erreur', $email, 'Mot de passe vide soumis');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Format d\'email invalide.';
            writeLog('validation_erreur', $email, 'Format d\'email invalide');
        } else {
            // Essaie de connecter
            if (connecter($email, $mdp)) {
                redirect(BASE_URL . '/index.php');
            } else {
                $erreur = 'Email ou mot de passe incorrect.';
            }
        }
    } catch (PDOException $e) {
        writeLog('erreur_bd', $_POST['email'] ?? 'UNKNOWN', 'Erreur Base de Données: ' . $e->getMessage());
        $erreur = 'Erreur système. Veuillez réessayer plus tard.';
    } catch (Exception $e) {
        writeLog('erreur_system', $_POST['email'] ?? 'UNKNOWN', 'Erreur: ' . $e->getMessage());
        $erreur = 'Erreur système. Veuillez réessayer plus tard.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — GestionFeu</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="/public/style.css">
</head>
<body>

<div class="login-page">

  <!-- Côté gauche décoratif -->
  <div class="login-left">
    <div class="ll-logo">
      <div class="lbox">🔥</div>
      <span>GestionFeu</span>
    </div>
    <h1>Gestion des extincteurs simplifiée.</h1>
    <p>Inventoriez, localisez et suivez tous vos équipements incendie depuis une seule interface.</p>
    <div class="login-feat">
      <div class="login-feat-item"><span>📋</span> Inventaire complet avec alertes d'expiration</div>
      <div class="login-feat-item"><span>🗺️</span> Localisation sur plans interactifs</div>
      <div class="login-feat-item"><span>👥</span> Gestion des accès par rôle</div>
    </div>
  </div>

  <!-- Côté droit : formulaire -->
  <div class="login-right">
    <div class="login-box">
      <h2>Connexion</h2>
      <p class="sub">Entrez vos identifiants pour accéder à l'application.</p>

      <?php if ($erreur): ?>
      <div class="flash flash-erreur" style="margin-bottom:20px">
        <span>⚠️</span> <?= e($erreur) ?>
      </div>
      <?php endif; ?>

      <form class="login-form" method="POST">
        <div class="form-group">
          <label>Adresse email</label>
          <input type="email" name="email"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="votre@email.fr" required autofocus>
        </div>
        <div class="form-group">
          <label>Mot de passe</label>
          <input type="password" name="mot_de_passe"
                 placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg btn-block">
          Se connecter →
        </button>
      </form>
    </div>
  </div>

</div>

</body>
</html>
