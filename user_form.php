<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$db      = getDB();
$id      = intval($_GET['id'] ?? 0);
$user    = null;
$titre   = $id ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur';
$erreurs = [];

if ($id) {
    $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) { flash('erreur', 'Introuvable.'); redirect(BASE_URL . '/admin.php'); }
}

$toutes_permissions = [
    'extincteurs.voir'      => ['label' => 'Voir les extincteurs',         'desc' => 'Consulter l\'inventaire'],
    'extincteurs.modifier'  => ['label' => 'Modifier les extincteurs',     'desc' => 'Ajouter et éditer'],
    'extincteurs.supprimer' => ['label' => 'Supprimer les extincteurs',    'desc' => 'Suppression définitive'],
    'plans.voir'            => ['label' => 'Voir les plans',               'desc' => 'Consulter les plans'],
    'plans.gerer'           => ['label' => 'Gérer les plans',              'desc' => 'Upload, édition, suppression'],
];

$defauts = [
    'admin'      => array_keys($toutes_permissions),
    'technicien' => ['extincteurs.voir', 'extincteurs.modifier', 'plans.voir', 'plans.gerer'],
    'lecteur'    => ['extincteurs.voir', 'plans.voir'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom   = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = $_POST['role'] ?? '';
    $mdp   = $_POST['mot_de_passe'] ?? '';
    $perms = $_POST['permissions'] ?? [];
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (!$nom)   $erreurs[] = 'Le nom est obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
    if (!in_array($role, ['admin','technicien','lecteur'])) $erreurs[] = 'Rôle invalide.';
    if (!$id && !$mdp) $erreurs[] = 'Le mot de passe est obligatoire.';
    if ($mdp && strlen($mdp) < 6) $erreurs[] = 'Mot de passe trop court (6 caractères min.).';

    if ($email) {
        $c = $db->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
        $c->execute([$email, $id]);
        if ($c->fetch()) $erreurs[] = 'Cette adresse email est déjà utilisée.';
    }

    if (empty($erreurs)) {
        $permsJson = json_encode($perms);
        if ($id) {
            if ($mdp) {
                $db->prepare('UPDATE utilisateurs SET nom=?,email=?,role=?,permissions=?,actif=?,mot_de_passe=? WHERE id=?')
                   ->execute([$nom,$email,$role,$permsJson,$actif,password_hash($mdp,PASSWORD_DEFAULT),$id]);
            } else {
                $db->prepare('UPDATE utilisateurs SET nom=?,email=?,role=?,permissions=?,actif=? WHERE id=?')
                   ->execute([$nom,$email,$role,$permsJson,$actif,$id]);
            }
        } else {
            $db->prepare('INSERT INTO utilisateurs (nom,email,mot_de_passe,role,permissions,actif) VALUES (?,?,?,?,?,?)')
               ->execute([$nom,$email,password_hash($mdp,PASSWORD_DEFAULT),$role,$permsJson,$actif]);
        }
        flash('succes', $id ? 'Utilisateur modifié.' : 'Utilisateur créé.');
        redirect(BASE_URL . '/admin.php');
    }
    $user = compact('nom','email','role','actif') + ['permissions' => json_encode($perms)];
}

$perms_actuelles = json_decode($user['permissions'] ?? '[]', true) ?? [];
$role_actuel     = $user['role'] ?? 'lecteur';

include 'views/header.php';
?>

<div class="breadcrumb">
  <a href="admin.php">Administration</a>
  <span class="breadcrumb-sep">›</span>
  <span><?= e($titre) ?></span>
</div>

<div class="page-header">
  <div>
    <h2><?= e($titre) ?></h2>
    <?php if ($id): ?><p>Compte de <?= e($user['nom']) ?></p><?php endif; ?>
  </div>
  <a href="admin.php" class="btn btn-ghost">← Retour</a>
</div>

<?php if ($erreurs): ?>
<div class="flash flash-erreur">
  <span>⚠️</span>
  <div><ul><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<form method="POST" style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

  <!-- Colonne gauche -->
  <div>

    <!-- Infos personnelles -->
    <div class="form-card">
      <div class="form-card-hd">
        <div class="card-icon" style="background:#eff6ff">👤</div>
        <h3>Informations du compte</h3>
      </div>
      <div class="form-card-bd">
        <div class="form-grid">
          <div class="form-group">
            <label>Nom complet <em>*</em></label>
            <input type="text" name="nom"
                   value="<?= e($user['nom'] ?? '') ?>"
                   placeholder="Jean Dupont" required>
          </div>
          <div class="form-group">
            <label>Adresse email <em>*</em></label>
            <input type="email" name="email"
                   value="<?= e($user['email'] ?? '') ?>"
                   placeholder="jean@exemple.fr" required>
          </div>
          <div class="form-group">
            <label>Mot de passe <?= $id ? '(vide = inchangé)' : '<em>*</em>' ?></label>
            <input type="password" name="mot_de_passe"
                   placeholder="••••••••"
                   <?= !$id ? 'required' : '' ?>>
          </div>
          <div class="form-group" style="justify-content:flex-end;padding-top:20px">
            <label class="toggle-row">
              <input type="checkbox" name="actif" value="1"
                     <?= ($user['actif'] ?? 1) ? 'checked' : '' ?>>
              Compte actif
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions -->
    <div class="form-card">
      <div class="form-card-hd">
        <div class="card-icon" style="background:#f0fdf4">🔐</div>
        <h3>Permissions</h3>
        <button type="button" class="btn btn-sm btn-ghost" style="margin-left:auto" onclick="resetPerms()">
          Réinitialiser selon le rôle
        </button>
      </div>
      <div class="form-card-bd">
        <?php if ($role_actuel === 'admin'): ?>
        <p class="text-2" style="font-size:.88rem">
          Les administrateurs ont accès à toutes les fonctionnalités, sans restriction.
        </p>
        <?php else: ?>
        <div class="perm-grid">
          <?php foreach ($toutes_permissions as $key => $info): ?>
          <label class="perm-item">
            <input type="checkbox" name="permissions[]"
                   value="<?= $key ?>"
                   class="perm-cb" data-key="<?= $key ?>"
                   <?= in_array($key, $perms_actuelles) ? 'checked' : '' ?>>
            <div class="perm-item-tx">
              <strong><?= e($info['label']) ?></strong>
              <small><?= e($key) ?></small>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="form-card-ft">
        <button type="submit" class="btn btn-primary">
          <?= $id ? '💾 Enregistrer les modifications' : '✅ Créer le compte' ?>
        </button>
        <a href="admin.php" class="btn btn-ghost">Annuler</a>
      </div>
    </div>

  </div>

  <!-- Colonne droite : rôle -->
  <div>
    <div class="form-card" style="position:sticky;top:74px">
      <div class="form-card-hd">
        <div class="card-icon" style="background:#fdf4ff">🎭</div>
        <h3>Rôle</h3>
      </div>
      <div class="form-card-bd">
        <div class="role-picker">

          <label class="role-card <?= $role_actuel === 'admin' ? 'selected' : '' ?>"
                 onclick="changerRole('admin')">
            <input type="radio" name="role" value="admin"
                   <?= $role_actuel === 'admin' ? 'checked' : '' ?>>
            <div class="role-card-ic" style="background:#fdf2f2">🔑</div>
            <div class="role-card-tx">
              <strong>Administrateur</strong>
              <small>Accès complet</small>
            </div>
          </label>

          <label class="role-card <?= $role_actuel === 'technicien' ? 'selected' : '' ?>"
                 onclick="changerRole('technicien')">
            <input type="radio" name="role" value="technicien"
                   <?= $role_actuel === 'technicien' ? 'checked' : '' ?>>
            <div class="role-card-ic" style="background:var(--blue-bg)">🔧</div>
            <div class="role-card-tx">
              <strong>Technicien</strong>
              <small>Consultation & modification</small>
            </div>
          </label>

          <label class="role-card <?= $role_actuel === 'lecteur' ? 'selected' : '' ?>"
                 onclick="changerRole('lecteur')">
            <input type="radio" name="role" value="lecteur"
                   <?= $role_actuel === 'lecteur' ? 'checked' : '' ?>>
            <div class="role-card-ic" style="background:var(--surface2)">👁️</div>
            <div class="role-card-tx">
              <strong>Lecteur</strong>
              <small>Consultation uniquement</small>
            </div>
          </label>

        </div>
      </div>
    </div>
  </div>

</form>

<script>
const DEFAUTS = <?= json_encode($defauts) ?>;
let roleActuel = '<?= $role_actuel ?>';

function changerRole(r) {
  roleActuel = r;
  document.querySelectorAll('.role-card').forEach(el => el.classList.remove('selected'));
  event.currentTarget.closest('.role-card').classList.add('selected');

  // Masquer/afficher la section permissions
  const permSection = document.querySelector('.perm-grid');
  const adminMsg    = document.querySelector('.perm-card-admin-msg');
  // Juste reset les permissions
  resetPerms();
}

function resetPerms() {
  const perms = DEFAUTS[roleActuel] || [];
  document.querySelectorAll('.perm-cb').forEach(cb => {
    cb.checked = perms.includes(cb.dataset.key);
    // Mettre à jour le style de l'item parent
    cb.closest('.perm-item').classList.toggle(
      'perm-active', cb.checked
    );
  });
}

// Sync style au changement direct
document.querySelectorAll('.perm-cb').forEach(cb => {
  cb.addEventListener('change', () => {
    cb.closest('.perm-item').style.background = '';
  });
});
</script>

<?php include 'views/footer.php'; ?>
