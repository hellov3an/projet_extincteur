<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titre ?? 'GestionFeu') ?> — GestionFeu</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/style.css">
</head>
<body>

<nav class="navbar">
  <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
    <div class="brand-icon">🔥</div>
    GestionFeu
  </a>

  <div class="nav-sep"></div>

  <div class="navbar-links">
    <?php $p = basename($_SERVER['PHP_SELF']); ?>
    <a href="<?= BASE_URL ?>/index.php"
       class="<?= in_array($p, ['index.php','extincteur_voir.php','extincteur_form.php']) ? 'active' : '' ?>">
      <span>📋</span> Inventaire
    </a>
    <a href="<?= BASE_URL ?>/plans.php"
       class="<?= in_array($p, ['plans.php','plan_voir.php','plan_form.php']) ? 'active' : '' ?>">
      <span>🗺️</span> Plans
    </a>
    <?php if (currentUser()['role'] === 'admin'): ?>
    <a href="<?= BASE_URL ?>/admin.php"
       class="<?= in_array($p, ['admin.php','user_form.php']) ? 'active' : '' ?>">
      <span>⚙️</span> Admin
    </a>
    <?php endif; ?>
  </div>

  <div class="navbar-user">
    <div class="nav-user-info">
      <div class="nav-user-name"><?= e(currentUser()['nom']) ?></div>
      <div class="nav-user-role"><?= ucfirst(e(currentUser()['role'])) ?></div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-ghost">Déconnexion</a>
  </div>
</nav>

<div class="main">
<?php afficherFlash(); ?>
