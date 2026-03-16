<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$db    = getDB();
$titre = 'Administration';
$users = $db->query('SELECT id, nom, email, role, permissions, actif, created_at FROM utilisateurs ORDER BY role, nom')->fetchAll();

$total    = count($users);
$admins   = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$techns   = count(array_filter($users, fn($u) => $u['role'] === 'technicien'));
$lecteurs = count(array_filter($users, fn($u) => $u['role'] === 'lecteur'));
$actifs   = count(array_filter($users, fn($u) => $u['actif']));

// Couleur d'avatar par initiale
function avatarColor(string $nom): string {
    $colors = ['#e63329','#2563eb','#16a34a','#d97706','#9333ea','#0891b2','#db2777'];
    return $colors[ord($nom[0]) % count($colors)];
}

include 'views/header.php';
?>

<div class="page-header">
  <div>
    <h2>Gestion des utilisateurs</h2>
    <p><?= $total ?> compte(s) · <?= $actifs ?> actif(s)</p>
  </div>
  <a href="user_form.php" class="btn btn-primary">+ Ajouter un utilisateur</a>
</div>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon si-blue">👥</div>
    <div class="stat-val"><?= $total ?></div>
    <div class="stat-lbl">Total</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-green">✅</div>
    <div class="stat-val"><?= $actifs ?></div>
    <div class="stat-lbl">Actifs</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-red">🔑</div>
    <div class="stat-val"><?= $admins ?></div>
    <div class="stat-lbl">Admins</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon si-amber">🔧</div>
    <div class="stat-val"><?= $techns ?></div>
    <div class="stat-lbl">Techniciens</div>
  </div>
</div>

<div class="table-card">
  <table>
    <thead>
      <tr>
        <th>Utilisateur</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Permissions</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u):
      $perms  = json_decode($u['permissions'] ?? '[]', true) ?? [];
      $initiale = mb_strtoupper(mb_substr($u['nom'], 0, 1));
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="user-avatar" style="background:<?= avatarColor($u['nom']) ?>">
            <?= e($initiale) ?>
          </div>
          <div>
            <div style="font-weight:600;font-size:.88rem"><?= e($u['nom']) ?></div>
            <?php if ($u['id'] == currentUser()['id']): ?>
            <div style="font-size:.74rem;color:var(--text-3)">Vous</div>
            <?php endif; ?>
          </div>
        </div>
      </td>
      <td class="text-2" style="font-size:.85rem"><?= e($u['email']) ?></td>
      <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst(e($u['role'])) ?></span></td>
      <td>
        <?php if ($u['role'] === 'admin'): ?>
        <span class="text-3" style="font-size:.8rem">Accès total</span>
        <?php else: ?>
        <span style="font-size:.78rem;color:var(--text-2)">
          <?= count($perms) ?> permission(s)
        </span>
        <?php endif; ?>
      </td>
      <td>
        <span class="badge <?= $u['actif'] ? 'badge-valide' : 'badge-expire' ?>">
          <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
        </span>
      </td>
      <td>
        <div class="td-actions">
          <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-sm">Modifier</a>
          <?php if ($u['id'] != currentUser()['id']): ?>
          <a href="user_toggle.php?id=<?= $u['id'] ?>"
             class="btn btn-sm btn-ghost"
             onclick="return confirm('<?= $u['actif'] ? 'Désactiver' : 'Activer' ?> ce compte ?')">
            <?= $u['actif'] ? 'Désactiver' : 'Activer' ?>
          </a>
          <a href="user_suppr.php?id=<?= $u['id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('Supprimer <?= e($u['nom']) ?> ?')">Suppr.</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include 'views/footer.php'; ?>
