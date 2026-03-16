<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db   = getDB();
$id   = intval($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM plans WHERE id = ?');
$stmt->execute([$id]);
$plan = $stmt->fetch();
if (!$plan) { flash('erreur', 'Plan introuvable.'); redirect(BASE_URL . '/plans.php'); }

$stmt = $db->prepare('
    SELECT pp.*, e.id AS ext_id, e.numero_serie, e.type, e.marque, e.capacite,
           e.zone, e.localisation, e.date_installation, e.date_expiration,
           e.dernier_controle, e.prochain_controle, e.notes
    FROM pinpoints pp
    JOIN extincteurs e ON e.id = pp.extincteur_id
    WHERE pp.plan_id = ?
');
$stmt->execute([$id]);
$pinpoints = $stmt->fetchAll();
$places    = array_column($pinpoints, 'extincteur_id');

$extincteurs = $db->query('SELECT id, numero_serie, type, zone, date_expiration FROM extincteurs ORDER BY zone, numero_serie')->fetchAll();

// Données JSON pour le JS
$pinpoints_js = [];
foreach ($pinpoints as $pp) {
    $s = statut($pp['date_expiration']);
    $pinpoints_js[$pp['id']] = [
        'id'                => (int)$pp['id'],
        'ext_id'            => (int)$pp['ext_id'],
        'numero_serie'      => $pp['numero_serie'],
        'type'              => $pp['type'],
        'marque'            => $pp['marque'] ?: '—',
        'capacite'          => $pp['capacite'] ? $pp['capacite'] . ' kg/L' : '—',
        'zone'              => $pp['zone'],
        'localisation'      => $pp['localisation'] ?: '—',
        'date_installation' => formatDate($pp['date_installation']),
        'date_expiration'   => formatDate($pp['date_expiration']),
        'dernier_controle'  => formatDate($pp['dernier_controle']),
        'prochain_controle' => formatDate($pp['prochain_controle']),
        'notes'             => $pp['notes'] ?: '',
        'statut'            => $s,
        'statut_label'      => statutLabel($s),
    ];
}

$titre = $plan['nom'];
include 'views/header.php';
?>

<div class="breadcrumb">
  <a href="plans.php">Plans</a>
  <span class="breadcrumb-sep">›</span>
  <span><?= e($plan['nom']) ?></span>
</div>

<div class="page-header">
  <div>
    <h2><?= e($plan['nom']) ?></h2>
    <p><?= count($pinpoints) ?> extincteur(s) localisé(s)<?= $plan['zone'] ? ' · ' . e($plan['zone']) : '' ?></p>
  </div>
  <div class="page-header-actions">
    <?php if (peutFaire('plans.gerer')): ?>
    <a href="plan_form.php?id=<?= $plan['id'] ?>" class="btn">Modifier le plan</a>
    <?php endif; ?>
    <a href="plans.php" class="btn btn-ghost">← Plans</a>
  </div>
</div>

<div class="viewer-layout">

  <!-- ── Colonne principale ────────────────────────────────── -->
  <div>

    <!-- Barre d'outils -->
    <div class="viewer-wrap" style="margin-bottom:14px">
      <div class="viewer-toolbar">
        <?php if (peutFaire('extincteurs.modifier')): ?>
        <button id="btn-vue"   class="btn btn-sm active" onclick="setMode('vue')">👁 Vue</button>
        <button id="btn-place" class="btn btn-sm"        onclick="setMode('placer')">📍 Placer</button>
        <div style="width:1px;height:20px;background:var(--border);margin:0 6px;flex-shrink:0"></div>
        <?php endif; ?>
        <button class="btn btn-sm" onclick="zoomIn()"    title="Zoom +">＋</button>
        <button class="btn btn-sm" onclick="zoomOut()"   title="Zoom −">－</button>
        <button class="btn btn-sm btn-ghost" onclick="resetZoom()">⊡ Réinitialiser</button>
        <span id="zoom-label" style="font-size:.8rem;color:var(--text-3);min-width:38px">100%</span>
        <?php if (peutFaire('extincteurs.modifier')): ?>
        <span id="viewer-hint" class="viewer-hint" style="display:none">
          ← Sélectionnez un extincteur puis cliquez sur le plan
        </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Viewport (zone avec overflow:hidden) -->
    <div class="viewer-wrap">
      <div id="plan-viewport">
        <!-- Canvas transformé (zoom + pan) -->
        <div id="plan-canvas">
          <img id="plan-img"
               src="<?= BASE_URL ?>/uploads/plans/<?= e($plan['fichier']) ?>"
               alt="<?= e($plan['nom']) ?>"
               draggable="false">

          <?php foreach ($pinpoints as $pp):
            $s = statut($pp['date_expiration']);
          ?>
          <div class="pin pin-<?= $s ?>"
               data-id="<?= $pp['id'] ?>"
               data-ext-id="<?= $pp['ext_id'] ?>"
               style="left:<?= $pp['pos_x'] ?>%;top:<?= $pp['pos_y'] ?>%"
               onmouseenter="showTooltip(event, <?= $pp['id'] ?>)"
               onmouseleave="hideTooltip()"
               onclick="afficherFiche(<?= $pp['id'] ?>)">
            <div class="pin-marker"><div class="pin-inner"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Fiche détail (sous le plan, au clic) ─────────────── -->
    <div id="fiche-detail" style="display:none; margin-top:16px">
      <div class="fiche-card">
        <div class="fiche-header">
          <div class="fiche-header-left">
            <div id="fiche-badge" class="badge" style="margin-bottom:8px"></div>
            <div id="fiche-title" class="fiche-title"></div>
            <div id="fiche-sub"   class="fiche-sub"></div>
          </div>
          <div class="fiche-header-right">
            <a id="fiche-lien" href="#" class="btn btn-sm">Voir la fiche complète →</a>
            <button class="btn btn-sm btn-ghost" onclick="fermerFiche()" style="margin-left:4px">✕</button>
          </div>
        </div>
        <div class="fiche-body">
          <div class="fiche-section">
            <div class="fiche-section-title">Identification</div>
            <div class="fiche-row"><span class="fiche-key">Type</span>        <span id="f-type"></span></div>
            <div class="fiche-row"><span class="fiche-key">Marque</span>      <span id="f-marque"></span></div>
            <div class="fiche-row"><span class="fiche-key">Capacité</span>    <span id="f-capacite"></span></div>
          </div>
          <div class="fiche-section">
            <div class="fiche-section-title">Localisation</div>
            <div class="fiche-row"><span class="fiche-key">Zone</span>        <span id="f-zone"></span></div>
            <div class="fiche-row"><span class="fiche-key">Emplacement</span> <span id="f-localisation"></span></div>
          </div>
          <div class="fiche-section">
            <div class="fiche-section-title">Dates</div>
            <div class="fiche-row"><span class="fiche-key">Installation</span>     <span id="f-installation"></span></div>
            <div class="fiche-row"><span class="fiche-key">Expiration</span>       <span id="f-expiration" class="fiche-exp"></span></div>
            <div class="fiche-row"><span class="fiche-key">Dernier contrôle</span> <span id="f-dernier"></span></div>
            <div class="fiche-row"><span class="fiche-key">Prochain contrôle</span><span id="f-prochain"></span></div>
          </div>
          <div class="fiche-section" id="f-notes-wrap" style="display:none">
            <div class="fiche-section-title">Notes</div>
            <div id="f-notes" class="fiche-notes"></div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /colonne principale -->

  <!-- ── Sidebar liste extincteurs ─────────────────────────── -->
  <?php if (peutFaire('extincteurs.modifier')): ?>
  <div class="plan-sidebar">
    <div class="plan-sidebar-hd">
      <h3>Extincteurs</h3>
      <input type="text" id="search-ext" class="sidebar-search"
             placeholder="Filtrer…" oninput="filtrerListe(this.value)">
    </div>
    <ul class="ext-list" id="ext-list">
      <?php foreach ($extincteurs as $ext):
        $placed = in_array($ext['id'], $places);
        $s      = statut($ext['date_expiration']);
      ?>
      <li class="ext-item <?= $placed ? 'is-placed' : '' ?>"
          data-id="<?= $ext['id'] ?>"
          data-search="<?= strtolower($ext['numero_serie'] . ' ' . $ext['type'] . ' ' . $ext['zone']) ?>"
          onclick="<?= $placed ? 'void(0)' : 'selectionnerExt(' . $ext['id'] . ')' ?>">
        <div class="ext-dot <?= $s ?>"></div>
        <div class="ext-tx">
          <strong><?= e($ext['numero_serie']) ?></strong>
          <small><?= e($ext['type']) ?> · <?= e($ext['zone']) ?></small>
        </div>
        <?php if ($placed): ?>
        <span class="badge badge-place" style="font-size:.68rem">Placé</span>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

</div><!-- /viewer-layout -->

<!-- ── Tooltip flottant (rendu hors du plan, au niveau du body) -->
<div id="float-tooltip">
  <div id="ft-serie" class="ft-title"></div>
  <div id="ft-type"  class="ft-line"></div>
  <div id="ft-loc"   class="ft-line ft-muted"></div>
  <div id="ft-exp"   class="ft-line"></div>
  <?php if (peutFaire('extincteurs.modifier')): ?>
  <button id="ft-suppr" class="btn btn-sm btn-danger"
          style="margin-top:8px;width:100%;justify-content:center">
    Retirer du plan
  </button>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<style>
/* ── Viewport & canvas ─────────────────────────────────────── */
#plan-viewport {
  overflow: hidden;
  position: relative;
  background: repeating-conic-gradient(#efefef 0% 25%, white 0% 50%) 0 0 / 22px 22px;
  cursor: grab;
  user-select: none;
  /* hauteur max fixe pour que l'overflow soit actif */
  max-height: 68vh;
}
#plan-viewport.is-grabbing { cursor: grabbing; }
#plan-viewport.is-crosshair { cursor: crosshair; }

#plan-canvas {
  position: relative;
  display: inline-block;
  transform-origin: 0 0;
  /* transition légère seulement pour les boutons +/- */
  will-change: transform;
}
#plan-canvas.smooth { transition: transform .2s ease; }

#plan-img {
  display: block;
  width: 100%;
  max-height: 68vh;
  object-fit: contain;
  pointer-events: none;
  user-select: none;
}

/* ── Pins — sans tooltip CSS ───────────────────────────────── */
.pin {
  position: absolute;
  transform: translate(-50%, -100%);
  cursor: pointer;
  z-index: 10;
  transition: transform .12s ease;
}
.pin:hover { transform: translate(-50%, -100%) scale(1.25); z-index: 20; }
.pin.is-active .pin-marker { outline: 3px solid #111; outline-offset: 2px; }

.pin-marker {
  width: 28px; height: 28px;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 3px 10px rgba(0,0,0,.22);
  border: 2px solid white;
}
.pin-inner { width: 9px; height: 9px; background: white; border-radius: 50%; transform: rotate(45deg); }

.pin-valide  .pin-marker { background: var(--green); }
.pin-bientot .pin-marker { background: var(--amber); }
.pin-expire  .pin-marker { background: var(--red);   }
.pin-inconnu .pin-marker { background: var(--text-3); }

/* ── Tooltip flottant (fixed, hors du flux) ────────────────── */
#float-tooltip {
  display: none;
  position: fixed;
  z-index: 9999;
  background: white;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 14px;
  min-width: 180px;
  box-shadow: var(--shadow-md);
  pointer-events: none; /* ne bloque pas les events sous lui */
  font-size: .82rem;
}
#float-tooltip.has-action { pointer-events: auto; } /* quand bouton visible */
.ft-title { font-weight: 700; font-family: 'Sora', sans-serif; font-size: .88rem; margin-bottom: 4px; color: var(--text); }
.ft-line  { color: var(--text-2); margin-bottom: 2px; }
.ft-muted { color: var(--text-3); font-size: .78rem; }

/* ── Fiche détail ──────────────────────────────────────────── */
.fiche-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  animation: ficheIn .2s ease;
}
@keyframes ficheIn {
  from { opacity:0; transform:translateY(8px); }
  to   { opacity:1; transform:translateY(0); }
}
.fiche-header {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 16px;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border);
  background: var(--surface2); flex-wrap: wrap;
}
.fiche-header-right { display: flex; align-items: center; flex-shrink: 0; }
.fiche-title { font-family: 'Sora', sans-serif; font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em; }
.fiche-sub   { font-size: .85rem; color: var(--text-2); margin-top: 3px; }
.fiche-body  {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
}
.fiche-section { padding: 15px 20px; border-right: 1px solid var(--border); }
.fiche-section:last-child { border-right: none; }
.fiche-section-title {
  font-size: .7rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .08em; color: var(--text-3); margin-bottom: 10px;
}
.fiche-row   { display: flex; flex-direction: column; gap: 1px; margin-bottom: 8px; }
.fiche-key   { font-size: .75rem; color: var(--text-3); }
.fiche-row span:last-child { font-size: .87rem; font-weight: 600; color: var(--text); }
.fiche-exp.expire  { color: var(--red)   !important; }
.fiche-exp.bientot { color: var(--amber) !important; }
.fiche-notes { font-size: .87rem; color: var(--text-2); line-height: 1.6; }
</style>

<!-- ═══════════════════════════════════════════════════════════ -->
<script>
const PLAN_ID   = <?= $id ?>;
const BASE_URL  = '<?= BASE_URL ?>';
const PINPOINTS = <?= json_encode($pinpoints_js) ?>;
let mode      = 'vue';
let extSelId  = null;
let placesIds = <?= json_encode($places) ?>;

/* ════════════════════════════════════════════════════════════
   ZOOM & PAN
   ════════════════════════════════════════════════════════════ */
const viewport = document.getElementById('plan-viewport');
const canvas   = document.getElementById('plan-canvas');

let scale  = 1;
let tx     = 0;   // translation X (px)
let ty     = 0;   // translation Y (px)
let isPanning = false;
let panStart  = { x:0, y:0 };

const MIN_SCALE = 0.3;
const MAX_SCALE = 5;
const STEP      = 0.25;

function applyTransform(smooth) {
  canvas.classList.toggle('smooth', !!smooth);
  canvas.style.transform = `translate(${tx}px,${ty}px) scale(${scale})`;
  document.getElementById('zoom-label').textContent = Math.round(scale * 100) + '%';
}

function clampTranslation() {
  // Empêche de partir trop loin hors du viewport
  const vw = viewport.clientWidth;
  const vh = viewport.clientHeight;
  const cw = canvas.clientWidth  * scale;
  const ch = canvas.clientHeight * scale;
  const marginX = Math.max(vw * 0.1, 60);
  const marginY = Math.max(vh * 0.1, 60);
  tx = Math.min(tx,  marginX);
  ty = Math.min(ty,  marginY);
  tx = Math.max(tx, vw - cw - marginX);
  ty = Math.max(ty, vh - ch - marginY);
}

function zoomIn()    { scale = Math.min(MAX_SCALE, scale + STEP); clampTranslation(); applyTransform(true); }
function zoomOut()   { scale = Math.max(MIN_SCALE, scale - STEP); clampTranslation(); applyTransform(true); }
function resetZoom() { scale = 1; tx = 0; ty = 0; applyTransform(true); }

// Zoom à la molette (centré sur le curseur)
viewport.addEventListener('wheel', function(e) {
  e.preventDefault();
  const rect   = viewport.getBoundingClientRect();
  const mouseX = e.clientX - rect.left;
  const mouseY = e.clientY - rect.top;

  const delta = e.deltaY < 0 ? 0.12 : -0.12;
  const newScale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, scale + delta));
  if (newScale === scale) return;

  // Ajuster la translation pour zoomer sur le curseur
  const ratio = newScale / scale;
  tx = mouseX - ratio * (mouseX - tx);
  ty = mouseY - ratio * (mouseY - ty);
  scale = newScale;
  clampTranslation();
  applyTransform(false);
}, { passive: false });

// Zoom pinch (mobile)
let pinchDist0 = null;
let pinchScale0 = 1;
viewport.addEventListener('touchstart', function(e) {
  if (e.touches.length === 2) {
    pinchDist0  = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    );
    pinchScale0 = scale;
  }
}, { passive: true });
viewport.addEventListener('touchmove', function(e) {
  if (e.touches.length === 2 && pinchDist0) {
    e.preventDefault();
    const dist = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    );
    scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, pinchScale0 * dist / pinchDist0));
    clampTranslation();
    applyTransform(false);
  }
}, { passive: false });

// Pan à la souris (mode vue seulement)
viewport.addEventListener('mousedown', function(e) {
  if (mode === 'placer') return;
  if (e.target.closest('.pin')) return;
  isPanning = true;
  panStart  = { x: e.clientX - tx, y: e.clientY - ty };
  viewport.classList.add('is-grabbing');
});
window.addEventListener('mousemove', function(e) {
  if (!isPanning) return;
  tx = e.clientX - panStart.x;
  ty = e.clientY - panStart.y;
  clampTranslation();
  applyTransform(false);
});
window.addEventListener('mouseup', function() {
  isPanning = false;
  if (mode !== 'placer') viewport.classList.remove('is-grabbing');
});

/* ════════════════════════════════════════════════════════════
   TOOLTIP FLOTTANT
   ════════════════════════════════════════════════════════════ */
const floatTip  = document.getElementById('float-tooltip');
let   tipPinId  = null;
let   tipHideTimer = null;

function showTooltip(e, pinId) {
  clearTimeout(tipHideTimer);
  const d = PINPOINTS[pinId];
  if (!d) return;
  tipPinId = pinId;

  document.getElementById('ft-serie').textContent = d.numero_serie;
  document.getElementById('ft-type').textContent  = d.type + (d.marque !== '—' ? ' · ' + d.marque : '');
  document.getElementById('ft-loc').textContent   = d.localisation !== '—' ? d.localisation : '';
  document.getElementById('ft-exp').textContent   = 'Exp. ' + d.date_expiration;

  const supprBtn = document.getElementById('ft-suppr');
  if (supprBtn) {
    supprBtn.onclick = function(ev) {
      ev.stopPropagation();
      hideTooltip();
      supprimerPin(pinId, d.ext_id);
    };
    floatTip.classList.add('has-action');
  }

  floatTip.style.display = 'block';
  positionTooltip(e);
}

function positionTooltip(e) {
  const margin = 12;
  const tw = floatTip.offsetWidth  || 190;
  const th = floatTip.offsetHeight || 80;
  let left = e.clientX + margin;
  let top  = e.clientY - th / 2;

  // Ne pas dépasser le bord droit de l'écran
  if (left + tw > window.innerWidth - margin) left = e.clientX - tw - margin;
  // Ne pas dépasser en bas
  if (top + th > window.innerHeight - margin) top = window.innerHeight - th - margin;
  if (top < margin) top = margin;

  floatTip.style.left = left + 'px';
  floatTip.style.top  = top  + 'px';
}

function hideTooltip() {
  tipHideTimer = setTimeout(() => {
    floatTip.style.display = 'none';
    floatTip.classList.remove('has-action');
    tipPinId = null;
  }, 120);
}

// Garder le tooltip visible quand la souris y entre
floatTip.addEventListener('mouseenter', () => clearTimeout(tipHideTimer));
floatTip.addEventListener('mouseleave', hideTooltip);

/* ════════════════════════════════════════════════════════════
   MODE VUE / PLACER
   ════════════════════════════════════════════════════════════ */
function setMode(m) {
  mode = m;
  document.getElementById('btn-vue')?.classList.toggle('active', m === 'vue');
  document.getElementById('btn-place')?.classList.toggle('active', m === 'placer');
  const hint = document.getElementById('viewer-hint');
  if (hint) hint.style.display = m === 'placer' ? 'inline' : 'none';
  viewport.classList.toggle('is-crosshair', m === 'placer');
  if (m === 'placer') { fermerFiche(); hideTooltip(); }
  if (m === 'vue')    deselectionner();
}

/* ════════════════════════════════════════════════════════════
   FICHE DÉTAIL
   ════════════════════════════════════════════════════════════ */
function afficherFiche(pinId) {
  if (mode === 'placer') return;
  const d = PINPOINTS[pinId];
  if (!d) return;

  document.querySelectorAll('.pin').forEach(p => p.classList.remove('is-active'));
  document.querySelector(`.pin[data-id="${pinId}"]`)?.classList.add('is-active');

  const badge = document.getElementById('fiche-badge');
  badge.className   = `badge badge-${d.statut}`;
  badge.textContent = d.statut_label;

  document.getElementById('fiche-title').textContent = d.numero_serie;
  document.getElementById('fiche-sub').textContent   = d.type + (d.marque !== '—' ? ' · ' + d.marque : '');
  document.getElementById('fiche-lien').href          = BASE_URL + '/extincteur_voir.php?id=' + d.ext_id;

  document.getElementById('f-type').textContent        = d.type;
  document.getElementById('f-marque').textContent      = d.marque;
  document.getElementById('f-capacite').textContent    = d.capacite;
  document.getElementById('f-zone').textContent        = d.zone;
  document.getElementById('f-localisation').textContent = d.localisation;
  document.getElementById('f-installation').textContent = d.date_installation;

  const expEl = document.getElementById('f-expiration');
  expEl.textContent = d.date_expiration;
  expEl.className   = 'fiche-exp ' + d.statut;

  document.getElementById('f-dernier').textContent  = d.dernier_controle;
  document.getElementById('f-prochain').textContent = d.prochain_controle;

  const nw = document.getElementById('f-notes-wrap');
  if (d.notes) { document.getElementById('f-notes').textContent = d.notes; nw.style.display = ''; }
  else           nw.style.display = 'none';

  const fiche = document.getElementById('fiche-detail');
  fiche.style.display = '';
  const card = fiche.querySelector('.fiche-card');
  card.style.animation = 'none'; card.offsetHeight; card.style.animation = '';

  fiche.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function fermerFiche() {
  document.getElementById('fiche-detail').style.display = 'none';
  document.querySelectorAll('.pin').forEach(p => p.classList.remove('is-active'));
}

/* ════════════════════════════════════════════════════════════
   PLACEMENT DES PINS
   ════════════════════════════════════════════════════════════ */
function selectionnerExt(id) {
  if (mode !== 'placer') return;
  extSelId = id;
  document.querySelectorAll('.ext-item').forEach(el => el.classList.remove('is-selected'));
  document.querySelector(`#ext-list [data-id="${id}"]`).classList.add('is-selected');
}

function deselectionner() {
  extSelId = null;
  document.querySelectorAll('.ext-item').forEach(el => el.classList.remove('is-selected'));
}

viewport.addEventListener('click', function(e) {
  const pinClicked = e.target.closest('.pin');
  if (mode === 'vue') {
    if (!pinClicked) fermerFiche();
    return;
  }
  if (!extSelId || pinClicked) return;

  // Calculer la position en % dans le canvas (en tenant compte du zoom/pan)
  const rect   = canvas.getBoundingClientRect();
  const posX   = ((e.clientX - rect.left) / rect.width  * 100).toFixed(2);
  const posY   = ((e.clientY - rect.top)  / rect.height * 100).toFixed(2);
  placerPin(extSelId, posX, posY);
});

/* ════════════════════════════════════════════════════════════
   AJAX : placer / supprimer
   ════════════════════════════════════════════════════════════ */
async function placerPin(extId, posX, posY) {
  const res  = await fetch(BASE_URL + '/api_pinpoints.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action:'ajouter', plan_id:PLAN_ID, extincteur_id:extId, pos_x:posX, pos_y:posY})
  });
  const data = await res.json();
  if (!data.ok) { alert(data.erreur); return; }

  const newPinId = data.pinpoint_id;
  PINPOINTS[newPinId] = {
    id: newPinId, ext_id: extId,
    numero_serie:      data.numero_serie,
    type:              data.type              || '—',
    marque:            data.marque            || '—',
    capacite:          data.capacite          || '—',
    zone:              data.zone              || '—',
    localisation:      data.localisation      || '—',
    date_installation: data.date_installation || '—',
    date_expiration:   data.date_expiration   || '—',
    dernier_controle:  data.dernier_controle  || '—',
    prochain_controle: data.prochain_controle || '—',
    notes: data.notes || '',
    statut: data.statut, statut_label: data.statut_label || '',
  };

  const div = document.createElement('div');
  div.className      = `pin pin-${data.statut}`;
  div.dataset.id     = newPinId;
  div.dataset.extId  = extId;
  div.style.left     = posX + '%';
  div.style.top      = posY + '%';
  div.setAttribute('onmouseenter', `showTooltip(event, ${newPinId})`);
  div.setAttribute('onmouseleave', 'hideTooltip()');
  div.setAttribute('onclick',      `afficherFiche(${newPinId})`);
  div.innerHTML = `<div class="pin-marker"><div class="pin-inner"></div></div>`;
  canvas.appendChild(div);

  placesIds.push(extId);
  const li = document.querySelector(`#ext-list [data-id="${extId}"]`);
  if (li) {
    li.classList.add('is-placed'); li.onclick = null;
    const b = document.createElement('span');
    b.className = 'badge badge-place'; b.style.fontSize = '.68rem'; b.textContent = 'Placé';
    li.appendChild(b);
  }
  deselectionner();
}

async function supprimerPin(pinId, extId) {
  if (!confirm('Retirer cet extincteur du plan ?')) return;
  const res  = await fetch(BASE_URL + '/api_pinpoints.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({action:'supprimer', pinpoint_id:pinId})
  });
  const data = await res.json();
  if (!data.ok) { alert(data.erreur); return; }

  document.querySelector(`.pin[data-id="${pinId}"]`)?.remove();
  delete PINPOINTS[pinId];
  fermerFiche();

  placesIds = placesIds.filter(i => i !== extId);
  const li = document.querySelector(`#ext-list [data-id="${extId}"]`);
  if (li) {
    li.classList.remove('is-placed');
    li.querySelector('.badge-place')?.remove();
    li.onclick = () => selectionnerExt(extId);
  }
}

/* ════════════════════════════════════════════════════════════
   FILTRE LISTE
   ════════════════════════════════════════════════════════════ */
function filtrerListe(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.ext-item').forEach(li => {
    li.style.display = li.dataset.search.includes(q) ? '' : 'none';
  });
}

// Init
applyTransform(false);
</script>

<?php include 'views/footer.php'; ?>
