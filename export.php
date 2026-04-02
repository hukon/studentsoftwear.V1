<?php
// reports_dashboard.php
// UI dashboard to pick a class and export a nicely formatted PDF
require_once __DIR__ . '/config.php';
requireLogin();

$error = '';
$classes = [];

$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    $error = "Impossible de se connecter à la base de données: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
} else {
    $mysqli->set_charset('utf8mb4');
    $sql = "SELECT id, name FROM classes ORDER BY name";
    if ($res = $mysqli->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $classes[] = $row;
        }
        $res->free();
    } else {
        $error = "Erreur lors de la récupération des classes: " . $mysqli->error;
    }
    $mysqli->close();
}

// If you want this page to live in a subfolder, the export_pdf.php path below should be adjusted.
$export_endpoint = 'export_pdf.php';
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rapports & Export — Tableau de bord</title>
  <style>
    :root{--bg:#f7f9fb;--card:#ffffff;--accent:#2e86de;--muted:#6b7280}
    *{box-sizing:border-box;font-family:Inter,system-ui,Segoe UI,Arial,sans-serif}
    body{margin:0;background:var(--bg);color:#111}
    .container{max-width:1000px;margin:28px auto;padding:22px}
    header{display:flex;align-items:center;gap:16px}
    .logo{width:64px;height:64px;border-radius:8px;background:linear-gradient(135deg,var(--accent),#6cc1ff);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
    h1{margin:0;font-size:20px}
    p.lead{margin:4px 0 18px;color:var(--muted)}

    .card{background:var(--card);border-radius:10px;padding:16px;box-shadow:0 6px 18px rgba(15,23,42,0.05)}
    .controls{display:flex;gap:12px;align-items:center;margin-bottom:14px}
    select.search, input.search{flex:1;padding:10px;border-radius:8px;border:1px solid #e6e9ef}
    button.btn{background:var(--accent);color:#fff;padding:10px 14px;border-radius:8px;border:0;cursor:pointer}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:12px}
    .class-card{background:linear-gradient(180deg,rgba(46,134,222,0.06),transparent);padding:12px;border-radius:8px;border:1px solid rgba(46,134,222,0.08);cursor:pointer;transition:transform .12s,box-shadow .12s}
    .class-card:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(46,134,222,0.06)}
    .class-name{font-weight:600}
    .class-meta{font-size:13px;color:var(--muted);margin-top:6px}
    .selected{outline:3px solid rgba(46,134,222,0.18);box-shadow:0 6px 22px rgba(46,134,222,0.06)}

    .footer{margin-top:18px;color:var(--muted);font-size:13px}
    .hint{color:#0b5fff;text-decoration:underline;cursor:pointer}

    @media (max-width:560px){.controls{flex-direction:column;align-items:stretch}.controls>button{width:100%}}
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div class="logo">EDU</div>
      <div>
        <h1>Rapports & Export</h1>
        <p class="lead">Exporter la liste des élèves par classe en PDF — sélectionnez une classe puis cliquez sur <strong>Exporter PDF</strong>.</p>
        <a href="evaluation_dashboard.php" class="btn btn-primary">
    Tableau de bord des évaluations
</a>
      </div>
    </header>

    <div style="height:12px"></div>

    <div class="card">
      <?php if ($error): ?>
        <div style="padding:12px;background:#fff4f4;border-radius:8px;border:1px solid #ffd6d6;color:#a33">Erreur: <?php echo htmlspecialchars($error); ?></div>
        <div style="margin-top:10px;color:var(--muted)">Vérifiez DB_USER / DB_PASS dans le fichier <code>reports_dashboard.php</code> puis rechargez la page.</div>
      <?php else: ?>

        <div class="controls">
          <select id="classSelect" class="search" aria-label="Choisir une classe">
            <option value="">— Choisir une classe —</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?php echo intval($c['id']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endforeach; ?>
          </select>

          <input id="filterInput" class="search" placeholder="Rechercher une classe par nom..." />

          <select id="difficultyInput" class="search" aria-label="Filtrer par difficulté">
            <option value="">— Filtrer par difficulté —</option>
            <option value="lecture">Lecture</option>
            <option value="écriture">Écriture</option>
            <option value="langue">Langue</option>
          </select>

          <button id="exportBtn" class="btn" disabled>Exporter PDF</button>
        </div>

        <div class="grid" id="classesGrid">
          <?php foreach ($classes as $c): ?>
            <div class="class-card" data-id="<?php echo intval($c['id']); ?>">
              <div class="class-name"><?php echo htmlspecialchars($c['name']); ?></div>
              <div class="class-meta">Exporter la liste des élèves en PDF</div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="footer">
          <small>Astuce: vous pouvez cliquer sur une carte de classe ou utiliser la liste déroulante. Le PDF s'ouvrira dans un nouvel onglet.</small>
        </div>

      <?php endif; ?>
    </div>

  </div>

<script>
(function(){
  const select = document.getElementById('classSelect');
  const grid = document.getElementById('classesGrid');
  const btn = document.getElementById('exportBtn');
  const filter = document.getElementById('filterInput');
  const difficulty = document.getElementById('difficultyInput');
  const exportEndpoint = '<?php echo addslashes($export_endpoint); ?>';

  function setSelected(id){
    // update select
    select.value = id || '';
    // toggle cards
    document.querySelectorAll('.class-card').forEach(card => {
      card.classList.toggle('selected', card.getAttribute('data-id') === String(id));
    });
    btn.disabled = !id;
  }

  // click on card
  grid.addEventListener('click', function(e){
    const card = e.target.closest('.class-card');
    if (!card) return;
    const id = card.getAttribute('data-id');
    setSelected(id);
  });

  // change select
  select.addEventListener('change', function(){
    setSelected(this.value);
  });

  // export button
  btn.addEventListener('click', function(){
    const id = select.value;
    if (!id) return alert('Veuillez choisir une classe.');
    // open export_pdf.php in new tab — it must exist and accept class_id
    let url = exportEndpoint + '?class_id=' + encodeURIComponent(id);
    if (difficulty.value.trim()) {
      url += '&difficulty_filter=' + encodeURIComponent(difficulty.value.trim());
    }
    window.open(url, '_blank');
  });

  // live filter for cards and select
  filter.addEventListener('input', function(){
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('.class-card').forEach(card => {
      const name = card.querySelector('.class-name').textContent.toLowerCase();
      const visible = name.indexOf(q) !== -1;
      card.style.display = visible ? '' : 'none';
    });

    // also filter select options (simple approach)
    for (let i=0;i<select.options.length;i++){
      const opt = select.options[i];
      if (!opt.value) continue; // keep the placeholder
      const visible = opt.text.toLowerCase().indexOf(q) !== -1;
      opt.style.display = visible ? '' : 'none';
    }
  });

  // keyboard accessibility: press Enter in filter to focus first visible card
  filter.addEventListener('keydown', function(e){
    if (e.key === 'Enter'){
      const first = document.querySelector('.class-card:not([style*="display: none"])');
      if (first) first.click();
    }
  });

})();
</script>
</body>
</html>