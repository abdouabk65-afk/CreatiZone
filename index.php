<?php
// ─────────────────────────────────────────────
//  Autoload & Bootstrap
// ─────────────────────────────────────────────
foreach (['Comment', 'Submission', 'CommentRepository', 'CommentController'] as $class) {
    require_once __DIR__ . "/classes/{$class}.php";
}
require_once('login_signup.php')
CommentRepository::init();

// Défi de démonstration
$submission = new Submission(
    id:          1,
    title:       'Créer une API REST avec authentification JWT',
    author:      $_post['nom'],
    category:    'Défi #12',
    views:       142,
    likes:       28,
    submittedAt: new DateTime('2026-02-24')
);

$controller = new CommentController(submissionId: 1);
$controller->handle(); // gère les requêtes AJAX (add / delete / like)

$comments  = $controller->getComments();
$canDelete = true; // En prod : vérifier si c'est l'auteur ou un admin
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commentaires — <?= $submission->getTitle() ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════
   PALETTE
   Light : #E7F5DC #CFE1B9 #B6C99B #98A77C #88976C #728156
   Dark  : #0F2A1D #375534 #6B9071 #AEC3B0 #E3EED4
═══════════════════════════════════════════ */
:root {
  --c1:#E7F5DC; --c2:#CFE1B9; --c3:#B6C99B;
  --c4:#98A77C; --c5:#88976C; --c6:#728156;
  --d1:#0F2A1D; --d2:#375534; --d3:#6B9071;
  --l1:#AEC3B0; --l2:#E3EED4;
}
*{margin:0;padding:0;box-sizing:border-box}
body{
  min-height:100vh;
  background:linear-gradient(135deg,var(--c2) 0%,var(--c3) 40%,var(--d3) 100%);
  font-family:'DM Sans',sans-serif;
  display:flex;justify-content:center;align-items:flex-start;
  padding:40px 16px;
}
.container{width:100%;max-width:700px}

/* ── En-tête défi ── */
.challenge-header{
  background:var(--d1);border-radius:24px 24px 0 0;
  padding:28px 32px 20px;position:relative;overflow:hidden;
}
.challenge-header::before{
  content:'';position:absolute;top:-40px;right:-40px;
  width:160px;height:160px;
  background:radial-gradient(circle,var(--d3) 0%,transparent 70%);
  opacity:.35;
}
.challenge-tag{
  display:inline-block;background:var(--d3);color:var(--l2);
  font-size:11px;font-weight:500;letter-spacing:2px;text-transform:uppercase;
  padding:4px 12px;border-radius:20px;margin-bottom:12px;
}
.challenge-title{
  font-family:'Playfair Display',serif;font-size:26px;
  color:var(--l2);line-height:1.3;
}
.challenge-meta{
  margin-top:10px;display:flex;align-items:center;gap:16px;
  color:var(--l1);font-size:13px;flex-wrap:wrap;
}

/* ── Stats bar ── */
.stats-bar{
  background:var(--d2);padding:14px 32px;
  display:flex;gap:24px;flex-wrap:wrap;
}
.stat{color:var(--c2);font-size:13px;display:flex;align-items:center;gap:6px}
.stat strong{color:var(--l2);font-size:16px}

/* ── Section commentaires ── */
.comments-section{
  background:var(--l2);border-radius:0 0 24px 24px;padding:28px 32px;
}
.section-label{
  font-family:'Playfair Display',serif;font-size:18px;color:var(--d1);
  margin-bottom:22px;display:flex;align-items:center;gap:10px;
}
.section-label::after{content:'';flex:1;height:1px;background:var(--c3)}

/* ── Formulaire ── */
.comment-form{display:flex;gap:12px;margin-bottom:32px;align-items:flex-start}
.avatar{
  width:40px;height:40px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-weight:500;font-size:14px;color:#fff;
}
.avatar-me{background:var(--d2)}
.form-box{
  flex:1;background:#fff;border-radius:16px;
  border:2px solid var(--c2);padding:14px 16px;transition:border-color .25s;
}
.form-box:focus-within{border-color:var(--d3)}
.form-box textarea{
  width:100%;border:none;outline:none;resize:none;
  font-family:'DM Sans',sans-serif;font-size:14px;
  color:var(--d1);background:transparent;min-height:64px;line-height:1.6;
}
.form-box textarea::placeholder{color:var(--c4)}
.char-count{font-size:11px;color:var(--c4);text-align:right;margin-top:4px}
.char-count.warn{color:#c0392b}
.form-actions{
  display:flex;justify-content:flex-end;gap:8px;
  margin-top:10px;padding-top:10px;border-top:1px solid var(--c2);
}
.btn{
  padding:8px 18px;border-radius:20px;
  font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;
  cursor:pointer;border:none;transition:all .2s;
}
.btn-cancel{background:transparent;color:var(--c6);border:1.5px solid var(--c3)}
.btn-cancel:hover{background:var(--c2)}
.btn-submit{background:var(--d2);color:var(--l2)}
.btn-submit:hover{background:var(--d1);transform:translateY(-1px)}
.btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}

/* ── Toast ── */
.toast{
  position:fixed;bottom:24px;right:24px;
  background:var(--d2);color:var(--l2);
  padding:12px 20px;border-radius:12px;font-size:13px;
  transform:translateY(80px);opacity:0;
  transition:all .3s;pointer-events:none;z-index:99;
}
.toast.show{transform:translateY(0);opacity:1}

/* ── Liste commentaires ── */
.comments-list{display:flex;flex-direction:column;gap:8px}
.comment-item{
  display:flex;gap:12px;padding:16px;
  border-radius:16px;background:#fff;
  border:1.5px solid transparent;
  transition:border-color .2s,transform .2s;
  animation:fadeSlide .35s ease both;
}
.comment-item:hover{border-color:var(--c3);transform:translateX(4px)}
@keyframes fadeSlide{
  from{opacity:0;transform:translateY(10px)}
  to{opacity:1;transform:translateY(0)}
}
.comment-body{flex:1}
.comment-header{
  display:flex;align-items:baseline;gap:8px;
  margin-bottom:6px;flex-wrap:wrap;
}
.comment-author{font-weight:500;font-size:14px;color:var(--d1)}
.comment-date{font-size:12px;color:var(--c4)}
.comment-badge{
  font-size:10px;padding:2px 8px;border-radius:10px;font-weight:500;
}
.badge-owner{background:var(--c2);color:var(--d2)}
.badge-jury{background:var(--d3);color:#fff}
.comment-text{font-size:14px;color:var(--d2);line-height:1.65}
.comment-footer{display:flex;align-items:center;gap:12px;margin-top:10px;flex-wrap:wrap}
.reaction-btn{
  background:none;border:none;cursor:pointer;
  font-size:13px;color:var(--c5);display:flex;align-items:center;gap:4px;
  padding:4px 8px;border-radius:10px;transition:all .18s;
  font-family:'DM Sans',sans-serif;
}
.reaction-btn:hover{background:var(--c1);color:var(--d2)}
.reaction-btn.liked{color:var(--d2);font-weight:500}
.delete-btn{
  margin-left:auto;background:none;border:none;cursor:pointer;
  font-size:12px;color:#ccc;padding:4px 8px;border-radius:8px;
  transition:all .18s;font-family:'DM Sans',sans-serif;
}
.delete-btn:hover{color:#e57373;background:#ffeaea}

/* ── Réponse ── */
.reply-form{
  margin-top:10px;padding-top:10px;
  border-top:1px dashed var(--c2);
  display:none;align-items:center;gap:8px;
}
.reply-form.open{display:flex}
.reply-input{
  flex:1;border:1.5px solid var(--c2);border-radius:20px;
  padding:8px 14px;font-size:13px;
  font-family:'DM Sans',sans-serif;color:var(--d1);outline:none;
}
.reply-input:focus{border-color:var(--d3)}

/* ── Empty ── */
.empty-state{text-align:center;padding:40px 20px;color:var(--c4);font-size:14px}
.empty-icon{font-size:36px;margin-bottom:10px}

/* ── Spinner ── */
.spinner{
  display:inline-block;width:14px;height:14px;
  border:2px solid rgba(255,255,255,.3);border-top-color:#fff;
  border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle;
}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:520px){
  .challenge-header,.comments-section{padding:20px}
  .stats-bar{padding:12px 20px}
  .challenge-title{font-size:20px}
}
</style>
</head>
<body>

<div class="container">

  <!-- ══ EN-TÊTE ══ -->
  <div class="challenge-header">
    <div class="challenge-tag"><?= htmlspecialchars($submission->getCategory()) ?></div>
    <h1 class="challenge-title"><?= $submission->getTitle() ?></h1>
    <div class="challenge-meta">
      <span>📅 <?= $submission->getFormattedDate() ?></span>
      <span>👤 <?= $submission->getAuthor() ?></span>
    </div>
  </div>

  <div class="stats-bar">
    <div class="stat">👁 <strong><?= $submission->getViews() ?></strong> vues</div>
    <div class="stat">❤️ <strong><?= $submission->getLikes() ?></strong> likes</div>
    <div class="stat">💬 <strong id="comment-count"><?= count($comments) ?></strong> commentaire<?= count($comments) > 1 ? 's' : '' ?></div>
  </div>

  <!-- ══ COMMENTAIRES ══ -->
  <div class="comments-section">
    <div class="section-label">Commentaires</div>

    <!-- Formulaire d'ajout -->
    <div class="comment-form">
      <div class="avatar avatar-me">VO</div>
      <div class="form-box">
        <textarea id="new-comment" placeholder="Partagez votre avis sur cette participation…" maxlength="1000"></textarea>
        <div class="char-count"><span id="char-num">0</span>/1000</div>
        <div class="form-actions">
          <button class="btn btn-cancel" id="btn-cancel">Annuler</button>
          <button class="btn btn-submit" id="btn-submit">Publier →</button>
        </div>
      </div>
    </div>

    <!-- Liste -->
    <div class="comments-list" id="comments-list">
      <?php if (empty($comments)): ?>
        <div class="empty-state" id="empty-state">
          <div class="empty-icon">💬</div>
          Aucun commentaire pour l'instant. Soyez le premier !
        </div>
      <?php else: ?>
        <?php foreach ($comments as $comment): ?>
          <?php include __DIR__ . '/views/comment_card.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script>
const SUBMISSION_ID = <?= $submission->getId() ?>;

/* ── Helpers ── */
function toast(msg, duration = 2500) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), duration);
}

async function api(action, data = {}) {
  const body = new URLSearchParams({ action, ...data });
  const res  = await fetch(window.location.href, { method: 'POST', body });
  return res.json();
}

function updateCount(delta) {
  const el  = document.getElementById('comment-count');
  const n   = parseInt(el.textContent) + delta;
  el.textContent = n + (n > 1 ? ' commentaires' : ' commentaire');
}

/* ── Compteur de caractères ── */
const textarea = document.getElementById('new-comment');
const charNum  = document.getElementById('char-num');
textarea.addEventListener('input', () => {
  const len = textarea.value.length;
  charNum.textContent = len;
  charNum.parentElement.classList.toggle('warn', len > 900);
});

/* ── Annuler ── */
document.getElementById('btn-cancel').addEventListener('click', () => {
  textarea.value = '';
  charNum.textContent = '0';
});

/* ── Ajouter un commentaire ── */
document.getElementById('btn-submit').addEventListener('click', async () => {
  const content = textarea.value.trim();
  if (!content) { toast('⚠️ Écrivez quelque chose d\'abord !'); return; }

  const btn = document.getElementById('btn-submit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>';

  const res = await api('add', { content, author: 'Vous', submission_id: SUBMISSION_ID });

  btn.disabled = false;
  btn.textContent = 'Publier →';

  if (!res.success) { toast('❌ ' + (res.message || 'Erreur')); return; }

  const c = res.comment;
  const list = document.getElementById('comments-list');

  // Supprimer état vide
  const empty = document.getElementById('empty-state');
  if (empty) empty.remove();

  // Créer la carte
  list.insertAdjacentHTML('afterbegin', buildCard(c));
  textarea.value = '';
  charNum.textContent = '0';
  updateCount(1);
  toast('✅ Commentaire publié !');
});

/* ── Délégation d'événements ── */
document.getElementById('comments-list').addEventListener('click', async (e) => {
  const btn = e.target.closest('button');
  if (!btn) return;

  const item = btn.closest('.comment-item');
  const id   = item?.dataset.id;

  /* Like */
  if (btn.classList.contains('like-btn')) {
    const res = await api('like', { id });
    if (!res.success) return;
    btn.classList.toggle('liked', res.liked);
    btn.querySelector('.like-count').textContent = res.likes;
  }

  /* Supprimer */
  if (btn.classList.contains('delete-btn')) {
    if (!confirm('Supprimer ce commentaire ?')) return;
    const res = await api('delete', { id });
    if (!res.success) { toast('❌ Impossible de supprimer'); return; }
    item.style.transition = 'all .3s';
    item.style.opacity = '0';
    item.style.transform = 'translateX(-20px)';
    setTimeout(() => { item.remove(); updateCount(-1); }, 300);
    toast('🗑 Commentaire supprimé');
  }

  /* Répondre */
  if (btn.classList.contains('reply-toggle')) {
    const form = document.getElementById('reply-' + id);
    form.classList.toggle('open');
    if (form.classList.contains('open')) form.querySelector('.reply-input').focus();
  }

  /* Envoyer réponse */
  if (btn.classList.contains('reply-send')) {
    const form  = btn.closest('.reply-form');
    const input = form.querySelector('.reply-input');
    if (!input.value.trim()) return;
    // En prod : appel API avec parent_id
    toast('💬 Réponse envoyée !');
    input.value = '';
    form.classList.remove('open');
  }
});

/* ── Construire une carte HTML (coté JS pour les ajouts AJAX) ── */
function buildCard(c) {
  const badge = c.badge
    ? `<span class="comment-badge ${c.badge.class}">${c.badge.label}</span>`
    : '';
  return `
  <article class="comment-item" data-id="${c.id}">
    <div class="avatar" style="background:${c.color}">${c.initials}</div>
    <div class="comment-body">
      <header class="comment-header">
        <span class="comment-author">${c.author}</span>
        ${badge}
        <time class="comment-date">${c.date}</time>
      </header>
      <p class="comment-text">${c.content}</p>
      <footer class="comment-footer">
        <button class="reaction-btn like-btn${c.liked?' liked':''}" data-id="${c.id}">
          ❤️ <span class="like-count">${c.likes}</span>
        </button>
        <button class="reaction-btn reply-toggle" data-id="${c.id}">💬 Répondre</button>
        <button class="delete-btn" data-id="${c.id}">🗑</button>
      </footer>
      <div class="reply-form" id="reply-${c.id}">
        <input type="text" placeholder="Votre réponse…" class="reply-input">
        <button class="btn btn-submit reply-send">↩</button>
      </div>
    </div>
  </article>`;
}
</script>
<?php
require_once("C:\wamp64\www\comments-app-php\comments-app\{classes,views,assets}\connexion.php");

foreach ($comments as $comment) {
    $submissionid = $submission->getId();
    $author       = $submission->getAuthor();
    $content      = $comment->getContent();
    $id           = $comment->getId();
    $date         = $submission->getFormattedDate();

    // Préparation de la requête
    $sql = "INSERT INTO commentaire (author, date, content, submission_id) 
            VALUES (:author, :date, :content, :submissionid)";
    $stmt = $db->prepare($sql);

    // Liaison des paramètres
    $stmt->bindParam(':author', $author, PDO::PARAM_STR);
    $stmt->bindParam(':date', $date, PDO::PARAM_STR);
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt->bindParam(':submissionid', $submissionid, PDO::PARAM_INT);
} 
  // Exécution
  if ($stmt->execute()) {
        echo "<script>alert('Insert successful!');</script>";
    } else {
        echo "Erreur lors de l'insertion.";
    }

?>
</body>
</html>
