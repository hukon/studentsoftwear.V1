<?php
require_once __DIR__ . '/config.php';
requireLogin();
$username = htmlspecialchars($_SESSION['username'] ?? 'Professeur');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Organizer - Dashboard</title>
  <style>
    :root{
      --bg: #f7f9fc;
      --card-bg: #ffffff;
      --muted: #6b7280;
      --accent: #2563eb;
    }

    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: var(--bg);
      color: #0f172a;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    .container{
      max-width: 1100px;
      margin: 28px auto;
      padding: 16px;
    }

    header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:20px;
    }
    .brand { display:flex; gap:12px; align-items:center; text-decoration:none; color:inherit; }
    .brand img { width:44px; height:44px; object-fit:contain; }
    .welcome { font-size:18px; font-weight:700; }
    .sub { color:var(--muted); font-size:14px; margin-top:4px; }

    .grid {
      display:grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap:18px;
    }

    /* card */
    .card {
      background: var(--card-bg);
      border-radius:12px;
      padding:24px;
      box-shadow: 0 8px 20px rgba(2,6,23,0.06);
      text-decoration:none;
      color:inherit;
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:12px;
      transition: transform .18s ease, box-shadow .18s ease;
      min-height:150px;
    }
    .card:hover, .card:focus{
      transform: translateY(-6px);
      box-shadow: 0 14px 40px rgba(2,6,23,0.10);
      outline: none;
    }
    .card img { width:64px; height:64px; object-fit:contain; }
    .card h3 { margin:0; font-size:1.05rem; text-align:center; }
    .card p { margin:0; color:var(--muted); font-size:0.95rem; text-align:center; }

    /* Special card styles */
    .card.evaluation { 
      background: linear-gradient(180deg,#fff 0%, #eef2ff 100%);
      color: #1e3a8a;
    }
    
    .card.evaluation:hover { box-shadow: 0 14px 40px rgba(102, 126, 234, 0.3); }

    /* Logout special */
    .card.logout { background: linear-gradient(180deg,#fff 0%, #fff); border:1px solid #ffe6e6; }
    .card.logout h3 { color:#b91c1c; }

    /* responsive */
    @media (max-width:1000px){
      .grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width:560px){
      .container { padding:12px; margin:12px; }
      header { flex-direction:column; align-items:flex-start; gap:8px; }
      .grid { grid-template-columns: 1fr; gap:12px; }
      .card { padding:18px; min-height:120px; }
      .card img { width:56px; height:56px; }
    }

    /* focus styles for keyboard accessibility */
    .card:focus { box-shadow: 0 10px 30px rgba(37,99,235,0.18); transform:translateY(-6px); }

    /* small helper */
    .center { text-align:center; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <a class="brand" href="index.php" aria-label="Accueil">
        <!-- optional logo -->
        <img src="assets/img/logo.png" alt="Logo" onerror="this.style.display='none'">
        <div>
          <div class="welcome">Bienvenue, <?php echo $username; ?> 👋</div>
          <div class="sub">Gérez vos classes, élèves et plans de salle</div>
        </div>
      </a>

      <div style="display:flex;gap:10px;align-items:center">
        <a href="calendar.php" class="card" style="padding:10px 14px;min-height:auto;box-shadow:none;">
          <img src="assets/img/calendar.png" alt="Calendrier" style="width:28px;height:28px">
        </a>
        <a href="logout.php" class="card logout" style="padding:10px 14px;min-height:auto;box-shadow:none;">
          <img src="assets/img/logout.png" alt="Se déconnecter" style="width:28px;height:28px">
        </a>
      </div>
    </header>
    

    <main>
      <div class="grid" role="list" aria-label="Tableau de bord">
        <a href="students.php" class="card" role="listitem" aria-label="Gérer Classes et Étudiants">
          <img src="assets/img/students.png" alt="Étudiants">
          <h3>Classes & Étudiants</h3>
          <p>Ajouter, modifier ou supprimer les classes et élèves.</p>
        </a>

        <a href="evaluation_menu.php" class="card" role="listitem" aria-label="Système d'évaluation">
          <img src="assets/img/evaluation.png" alt="Évaluations">
          <h3>Évaluations</h3>
          <p>Évaluer et suivre les compétences des élèves.</p>
        </a>

        <a href="seating_enhanced.php" class="card" role="listitem" aria-label="Plan de classe">
          <img src="assets/img/seating.png" alt="Plan de classe">
          <h3>Plan de Classe</h3>
          <p>Organisez les sièges par glisser-déposer.</p>
        </a>

        <a href="calendar.php" class="card" role="listitem" aria-label="Calendrier scolaire">
          <img src="assets/img/calendar.png" alt="Calendrier">
          <h3>Calendrier Scolaire</h3>
          <p>Anniversaires, jours fériés et événements.</p>
        </a>

        <a href="export.php" class="card" role="listitem" aria-label="Rapports" tabindex="0">
          <img src="assets/img/rapport.png" alt="Rapports">
          <h3>Rapports & Export</h3>
          <p>Exporter les listes, impressions et bilans.</p>
        </a>

        <a href="schedule.php" class="card" role="listitem" aria-label="Emploi du temps">
          <img src="assets/img/timetable.png" alt="Emploi du temps">
          <h3>Emploi du Temps</h3>
          <p>Emploi du Temps</p>
        </a>

        <a href="logout.php" class="card logout" role="listitem" aria-label="Se déconnecter">
          <img src="assets/img/logout.png" alt="Déconnexion">
          <h3>Se Déconnecter</h3>
          <p>Fermer la session en toute sécurité.</p>
        </a>
      </div>
    </main>
  </div>
</body>
</html>