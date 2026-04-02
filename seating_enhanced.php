<?php
require_once __DIR__ . '/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Plan de Classe Amélioré - Seating Planner</title>
<style>
  :root{
    --primary:#667eea; 
    --primary-dark:#5a67d8;
    --secondary:#764ba2;
    --success:#10b981; 
    --warning:#f59e0b; 
    --danger:#ef4444;
    --muted:#6b7280;
    --bg:#f6f8fb;
    --card:#ffffff;
    --border:#e5e7eb;
    --shadow: 0 10px 30px rgba(0,0,0,0.1);
  }
  
  * { box-sizing: border-box; margin: 0; padding: 0; }
  
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--bg);
    color: #0f172a;
    line-height: 1.6;
  }
  
  /* Header */
  header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: #fff;
    padding: 24px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
  }
  
  .header-title {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .header-title h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
  }
  
  .header-subtitle {
    opacity: 0.9;
    font-size: 14px;
    margin-top: 4px;
  }
  
  /* Main Container */
  main {
    max-width: 1400px;
    margin: 24px auto;
    padding: 0 20px;
  }
  
  /* Toolbar */
  .toolbar {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 24px;
  }
  
  .toolbar-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
  }
  
  .toolbar-section {
    display: flex;
    gap: 8px;
    align-items: center;
    padding: 8px 0;
  }
  
  .divider {
    width: 1px;
    height: 30px;
    background: var(--border);
    margin: 0 8px;
  }
  
  /* Form Elements */
  select, input[type="text"], input[type="search"] {
    padding: 10px 14px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    background: var(--card);
    color: #374151;
    transition: all 0.2s;
  }
  
  select:focus, input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }
  
  /* Buttons */
  .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
  }
  
  .btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  }
  
  .btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
  }
  
  .btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid var(--border);
  }
  
  .btn-secondary:hover:not(:disabled) {
    background: #e2e8f0;
  }
  
  .btn-success {
    background: var(--success);
    color: #fff;
  }
  
  .btn-warning {
    background: var(--warning);
    color: #fff;
  }
  
  .btn-danger {
    background: var(--danger);
    color: #fff;
  }
  
  /* Statistics Panel */
  .stats-panel {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    display: none;
  }
  
  .stats-panel.active {
    display: block;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
  }
  
  .stat-card {
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid;
  }
  
  .stat-card.primary { background: #eff6ff; border-color: #3b82f6; }
  .stat-card.success { background: #f0fdf4; border-color: #10b981; }
  .stat-card.warning { background: #fffbeb; border-color: #f59e0b; }
  .stat-card.danger { background: #fef2f2; border-color: #ef4444; }
  
  .stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
  }
  
  .stat-label {
    font-size: 14px;
    color: var(--muted);
  }
  
  /* Layout */
  .layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: start;
  }
  
  /* Classroom */
  .classroom-wrapper {
    background: var(--card);
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  
  .classroom-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .classroom-header h2 {
    font-size: 20px;
    font-weight: 700;
  }
  
  .seat-counter {
    padding: 6px 12px;
    background: #f0fdf4;
    color: #15803d;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
  }
  
  .board {
    padding: 16px;
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    color: #fff;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 24px;
    font-weight: 700;
    font-size: 16px;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
  }
  
  .classroom-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
  }
  
  .table-wrapper {
    background: #f8fafc;
    border-radius: 12px;
    padding: 12px;
    border: 2px dashed #cbd5e1;
    transition: all 0.2s;
  }
  
  .table-wrapper.drag-over {
    border-color: var(--primary);
    background: #eef2ff;
    transform: scale(1.02);
  }
  
  .table-label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
    text-align: center;
    margin-bottom: 8px;
  }
  
  .seats {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  .seat {
    padding: 12px;
    border-radius: 10px;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
  }
  
  .seat.empty {
    background: #fff;
    border: 2px dashed #cbd5e1;
    color: #94a3b8;
  }
  
  .seat.empty:hover {
    border-color: var(--primary);
    background: #f8fafc;
  }
  
  .seat.occupied {
    color: #fff;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .seat.occupied:hover {
    transform: scale(1.05);
  }
  
  /* Profile Colors */
  .seat.profile-excellent { background: linear-gradient(135deg, #10b981, #059669); }
  .seat.profile-bon { background: linear-gradient(135deg, #3b82f6, #2563eb); }
  .seat.profile-volontaire { background: linear-gradient(135deg, #f59e0b, #d97706); }
  .seat.profile-difficulté { background: linear-gradient(135deg, #ef4444, #dc2626); }
  
  .seat-photo {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  }
  
  .seat-name {
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    line-height: 1.2;
  }
  
  .seat-difficulties {
    font-size: 11px;
    opacity: 0.9;
    display: flex;
    gap: 4px;
  }
  
  .seat-label {
    font-size: 11px;
    font-weight: 600;
  }
  
  /* Students Panel */
  .students-panel {
    background: var(--card);
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }
  
  .panel-header h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
  }
  
  .search-box {
    position: relative;
    margin-bottom: 16px;
  }
  
  .search-box input {
    width: 100%;
    padding: 10px 10px 10px 36px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
  }
  
  .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
  }
  
  .legend {
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 12px;
  }
  
  .legend-title {
    font-weight: 700;
    margin-bottom: 8px;
  }
  
  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
  }
  
  .legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
  }
  
  .students-list {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  .student-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
    cursor: grab;
    border: 2px solid transparent;
    transition: all 0.2s;
  }
  
  .student-card:hover {
    background: #f1f5f9;
    transform: translateX(4px);
  }
  
  .student-card:active {
    cursor: grabbing;
  }
  
  .student-card.dragging {
    opacity: 0.5;
  }
  
  .student-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e5e7eb;
  }
  
  .student-info {
    flex: 1;
  }
  
  .student-name {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 2px;
  }
  
  .student-profile {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--muted);
    margin-top: 4px;
  }
  
  .profile-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }
  
  .student-difficulties {
    font-size: 11px;
    color: var(--warning);
    margin-top: 4px;
  }
  
  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
  }
  
  .tip-box {
    margin-top: 16px;
    padding: 12px;
    background: #eff6ff;
    border-radius: 8px;
    font-size: 12px;
    color: #1e40af;
  }
  
  /* Toast Notifications */
  .toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--card);
    padding: 16px 20px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 400px;
    animation: slideIn 0.3s ease;
    z-index: 1000;
  }
  
  @keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  
  .toast.success { border-left: 4px solid var(--success); }
  .toast.error { border-left: 4px solid var(--danger); }
  .toast.info { border-left: 4px solid var(--primary); }
  
  /* Responsive */
  @media (max-width: 1200px) {
    .layout {
      grid-template-columns: 1fr;
    }
    
    .students-panel {
      position: static;
      max-height: none;
    }
    
    .classroom-grid {
      grid-template-columns: repeat(4, 1fr);
    }
  }
  
  @media (max-width: 768px) {
    .classroom-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
  
  @media (max-width: 600px) {
    .classroom-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .toolbar-row {
      flex-direction: column;
      align-items: stretch;
    }
    
    .toolbar-section {
      width: 100%;
      justify-content: center;
    }
    
    .divider {
      display: none;
    }
  }
  
  @media (max-width: 480px) {
    .classroom-grid {
      grid-template-columns: 1fr;
    }
    
    .header-title h1 {
      font-size: 20px;
    }
  }
  
  /* Print Styles */
  @media print {
    body * {
      visibility: hidden;
    }
    
    .classroom-wrapper, .classroom-wrapper * {
      visibility: visible;
    }
    
    .classroom-wrapper {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      box-shadow: none;
    }
    
    .classroom-header {
      page-break-after: avoid;
    }
    
    .table-wrapper {
      page-break-inside: avoid;
    }
  }
</style>
</head>
<body>
  <header>
    <div class="header-content">
      <div class="header-title">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <div>
          <h1>Plan de Classe Amélioré</h1>
          <div class="header-subtitle">Gérez vos sièges avec intelligence et facilité</div>
        </div>
      </div>
      <a href="index.php" class="btn btn-secondary" style="color: inherit;">↩ Retour</a>
    </div>
  </header>

  <main>
    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-row">
        <div class="toolbar-section">
          <label for="classSelect" style="font-weight: 600;">Classe:</label>
          <select id="classSelect" style="min-width: 200px;">
            <option value="">-- Sélectionner --</option>
          </select>
          <button class="btn btn-primary" id="btnLoad">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="23 4 23 10 17 10"></polyline>
              <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
            Charger
          </button>
        </div>
        
        <div class="divider"></div>
        
        <div class="toolbar-section">
          <button class="btn btn-success" id="btnSmart">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Placement Intelligent
          </button>
          
          <button class="btn btn-secondary" id="btnRandom">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="16 3 21 3 21 8"></polyline>
              <line x1="4" y1="20" x2="21" y2="3"></line>
              <polyline points="21 16 21 21 16 21"></polyline>
              <line x1="15" y1="15" x2="21" y2="21"></line>
              <line x1="4" y1="4" x2="9" y2="9"></line>
            </svg>
            Aléatoire
          </button>
          
          <button class="btn btn-danger" id="btnClear">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="1 4 1 10 7 10"></polyline>
              <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
            Réinitialiser
          </button>
        </div>
        
        <div class="divider"></div>
        
        <div class="toolbar-section">
          <button class="btn btn-secondary" id="btnStats">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="20" x2="12" y2="10"></line>
              <line x1="18" y1="20" x2="18" y2="4"></line>
              <line x1="6" y1="20" x2="6" y2="16"></line>
            </svg>
            Statistiques
          </button>
          
          <button class="btn btn-secondary" id="btnPrint">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Panel -->
    <div class="stats-panel" id="statsPanel">
      <h3 style="margin-bottom: 16px; font-size: 18px; font-weight: 700;">📊 Statistiques de Placement</h3>
      <div class="stats-grid" id="statsGrid"></div>
    </div>

    <!-- Main Layout -->
    <div class="layout">
      <!-- Classroom -->
      <div class="classroom-wrapper">
        <div class="classroom-header">
          <h2>Salle de Classe</h2>
          <div class="seat-counter" id="seatCounter">0 / 0 placés</div>
        </div>

        <div class="board">📋 TABLEAU</div>

        <div class="classroom-grid" id="classroomGrid"></div>
      </div>

      <!-- Students Panel -->
      <div class="students-panel">
        <div class="panel-header">
          <h3 id="studentsPanelTitle">Élèves Disponibles</h3>
        </div>

        <div class="search-box">
          <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <input type="search" id="searchInput" placeholder="Rechercher un élève...">
        </div>

        <div class="legend">
          <div class="legend-title">Légende des profils:</div>
          <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #10b981, #059669);"></div>
            <span>Élève excellent</span>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #3b82f6, #2563eb);"></div>
            <span>Élève bon</span>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #f59e0b, #d97706);"></div>
            <span>Élève volontaire</span>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background: linear-gradient(135deg, #ef4444, #dc2626);"></div>
            <span>Élève en difficulté</span>
          </div>
        </div>

        <div class="students-list" id="studentsList">
          <div class="empty-state">Sélectionnez une classe</div>
        </div>

        <div class="tip-box">
          💡 <strong>Astuce:</strong> Glissez-déposez les élèves sur les sièges. Cliquez sur un siège occupé pour le libérer.
        </div>
      </div>
    </div>
  </main>

  <script>
    const api = 'api.php';
    const ROWS = 5;
    const COLS = 5;
    const SEATS_PER_TABLE = 2;
    
    let currentClassId = 0;
    let allStudents = [];
    let seating = {};
    let draggedStudentId = null;
    
    const difficultyIcons = {
      'comprehension_orale': '👂',
      'ecriture': '✍️',
      'vocabulaire': '📚',
      'grammaire': '📖',
      'conjugaison': '🔤',
      'production_ecrite': '📝'
    };
    
    const profileMap = {
      'Élève excellent': 'excellent',
      'Élève bon mais peu participatif': 'bon',
      'Élève volontaire mais en difficulté': 'volontaire',
      'Élève en difficulté et démotivé': 'difficulté'
    };
    
    async function init() {
      await loadClasses();
      buildClassroom();
      setupEventListeners();
    }
    
    function setupEventListeners() {
      document.getElementById('btnLoad').onclick = loadData;
      document.getElementById('btnClear').onclick = clearSeating;
      document.getElementById('btnRandom').onclick = randomizeSeating;
      document.getElementById('btnSmart').onclick = smartSeating;
      document.getElementById('btnStats').onclick = toggleStats;
      document.getElementById('btnPrint').onclick = () => window.print();
      document.getElementById('searchInput').oninput = filterStudents;
    }
    
    async function loadClasses() {
      try {
        const res = await fetch(`${api}?action=classes`);
        const classes = await res.json();
        const select = document.getElementById('classSelect');
        select.innerHTML = '<option value="">-- Sélectionner --</option>';
        classes.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.name;
          select.appendChild(opt);
        });
      } catch(e) {
        showToast('Erreur de chargement des classes', 'error');
      }
    }
    
    function buildClassroom() {
      const grid = document.getElementById('classroomGrid');
      grid.innerHTML = '';
      
      for (let row = 1; row <= ROWS; row++) {
        for (let col = 1; col <= COLS; col++) {
          const tableDiv = document.createElement('div');
          tableDiv.className = 'table-wrapper';
          tableDiv.dataset.row = row;
          tableDiv.dataset.col = col;
          
          const label = document.createElement('div');
          label.className = 'table-label';
          label.textContent = `Rangée ${row} - Table ${col}`;
          tableDiv.appendChild(label);
          
          const seatsDiv = document.createElement('div');
          seatsDiv.className = 'seats';
          
          for (let seat = 1; seat <= SEATS_PER_TABLE; seat++) {
            const seatDiv = document.createElement('div');
            seatDiv.className = 'seat empty';
            seatDiv.dataset.row = row;
            seatDiv.dataset.col = col;
            seatDiv.dataset.seat = seat;
            
            seatDiv.innerHTML = `<div class="seat-label">Siège ${seat}</div>`;
            
            seatDiv.ondragover = (e) => {
              e.preventDefault();
              tableDiv.classList.add('drag-over');
            };
            
            seatDiv.ondragleave = () => {
              tableDiv.classList.remove('drag-over');
            };
            
            seatDiv.ondrop = async (e) => {
              e.preventDefault();
              tableDiv.classList.remove('drag-over');
              
              if (draggedStudentId) {
                await assignSeat(draggedStudentId, row, col, seat);
                draggedStudentId = null;
              }
            };
            
            seatDiv.onclick = async () => {
              const key = `${row}-${col}-${seat}`;
              if (seating[key]) {
                if (confirm('Retirer cet élève du siège ?')) {
                  await removeSeat(key);
                }
              }
            };
            
            seatsDiv.appendChild(seatDiv);
          }
          
          tableDiv.appendChild(seatsDiv);
          grid.appendChild(tableDiv);
        }
      }
    }
    
    async function loadData() {
      const classId = parseInt(document.getElementById('classSelect').value);
      if (!classId) {
        showToast('Veuillez sélectionner une classe', 'error');
        return;
      }
      
      currentClassId = classId;
      await loadStudents(classId);
      await loadSeating(classId);
      updateSeatCounter();
    }
    
    async function loadStudents(classId) {
      try {
        const res = await fetch(`${api}?action=students&class_id=${classId}`);
        const students = await res.json();
        allStudents = students.map(s => ({
          ...s,
          profile: profileMap[s.category1] || 'bon',
          difficulties: getDifficultiesArray(s)
        }));
        renderStudentsList();
      } catch(e) {
        showToast('Erreur de chargement des élèves', 'error');
      }
    }
    
    function getDifficultiesArray(student) {
      const diff = [];
      if (student.comprehension_orale) diff.push('comprehension_orale');
      if (student.ecriture) diff.push('ecriture');
      if (student.vocabulaire) diff.push('vocabulaire');
      if (student.grammaire) diff.push('grammaire');
      if (student.conjugaison) diff.push('conjugaison');
      if (student.production_ecrite) diff.push('production_ecrite');
      return diff;
    }
    
    function renderStudentsList() {
      const list = document.getElementById('studentsList');
      const searchTerm = document.getElementById('searchInput').value.toLowerCase();
      
      const seatedIds = Object.values(seating);
      const unassigned = allStudents.filter(s => !seatedIds.includes(s.id));
      const filtered = unassigned.filter(s => 
        s.name.toLowerCase().includes(searchTerm)
      );
      
      document.getElementById('studentsPanelTitle').textContent = 
        `Élèves Disponibles (${filtered.length})`;
      
      if (filtered.length === 0) {
        list.innerHTML = '<div class="empty-state">Aucun élève disponible</div>';
        return;
      }
      
      list.innerHTML = '';
      filtered.forEach(student => {
        const card = createStudentCard(student);
        list.appendChild(card);
      });
    }
    
    function createStudentCard(student) {
      const card = document.createElement('div');
      card.className = 'student-card';
      card.draggable = true;
      
      card.ondragstart = (e) => {
        draggedStudentId = student.id;
        card.classList.add('dragging');
      };
      
      card.ondragend = () => {
        card.classList.remove('dragging');
      };
      
      const photoUrl = student.pic_path || '';
      const hasPhoto = photoUrl && photoUrl.trim() !== '';
      
      card.innerHTML = `
        ${hasPhoto ? `<img src="${photoUrl}" class="student-avatar" onerror="this.style.display='none'">` : 
          '<div class="student-avatar" style="background: linear-gradient(135deg, #cbd5e1, #94a3b8); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px;">' + 
          student.name.charAt(0) + '</div>'}
        <div class="student-info">
          <div class="student-name">${student.name}</div>
          <div class="student-profile">
            <div class="profile-dot" style="background: ${getProfileColor(student.profile)}"></div>
            <span style="text-transform: capitalize;">${student.profile}</span>
          </div>
          ${student.difficulties.length > 0 ? 
            `<div class="student-difficulties">${student.difficulties.map(d => difficultyIcons[d] || '⚠️').join(' ')}</div>` 
            : ''}
        </div>
      `;
      
      return card;
    }
    
    function getProfileColor(profile) {
      const colors = {
        'excellent': '#10b981',
        'bon': '#3b82f6',
        'volontaire': '#f59e0b',
        'difficulté': '#ef4444'
      };
      return colors[profile] || '#9ca3af';
    }
    
    async function loadSeating(classId) {
      try {
        const res = await fetch(`${api}?action=get_seating&class_id=${classId}`);
        const data = await res.json();
        
        seating = {};
        data.forEach(s => {
          const key = `${s.row_num}-${s.col_num}-${s.seat_num}`;
          seating[key] = s.student_id;
        });
        
        updateSeats();
      } catch(e) {
        showToast('Erreur de chargement des sièges', 'error');
      }
    }
    
    function updateSeats() {
      document.querySelectorAll('.seat').forEach(seatDiv => {
        const row = seatDiv.dataset.row;
        const col = seatDiv.dataset.col;
        const seat = seatDiv.dataset.seat;
        const key = `${row}-${col}-${seat}`;
        const studentId = seating[key];
        
        if (studentId) {
          const student = allStudents.find(s => s.id == studentId);
          if (student) {
            seatDiv.className = `seat occupied profile-${student.profile}`;
            
            const photoUrl = student.pic_path || '';
            const hasPhoto = photoUrl && photoUrl.trim() !== '';
            
            seatDiv.innerHTML = `
              ${hasPhoto ? `<img src="${photoUrl}" class="seat-photo" onerror="this.style.display='none'">` : ''}
              <div class="seat-name">${student.name}</div>
              ${student.difficulties.length > 0 ? 
                `<div class="seat-difficulties">${student.difficulties.map(d => difficultyIcons[d] || '⚠️').join(' ')}</div>` 
                : ''}
            `;
          }
        } else {
          seatDiv.className = 'seat empty';
          seatDiv.innerHTML = `<div class="seat-label">Siège ${seat}</div>`;
        }
      });
      
      renderStudentsList();
      updateSeatCounter();
    }
    
    async function assignSeat(studentId, row, col, seat) {
      try {
        const fd = new FormData();
        fd.append('action', 'set_seating');
        fd.append('class_id', currentClassId);
        fd.append('student_id', studentId);
        fd.append('row_num', row);
        fd.append('col_num', col);
        fd.append('seat_num', seat);
        
        const res = await fetch(api, { method: 'POST', body: fd });
        const out = await res.json();
        
        if (out.error) {
          showToast(out.error, 'error');
          return;
        }
        
        const key = `${row}-${col}-${seat}`;
        seating[key] = studentId;
        updateSeats();
        
        const student = allStudents.find(s => s.id == studentId);
        showToast(`${student.name} placé(e) avec succès`, 'success');
      } catch(e) {
        showToast('Erreur lors du placement', 'error');
      }
    }
    
    async function removeSeat(key) {
      const studentId = seating[key];
      if (!studentId) return;
      
      try {
        const fd = new FormData();
        fd.append('action', 'remove_seating');
        fd.append('class_id', currentClassId);
        fd.append('student_id', studentId);
        
        const res = await fetch(api, { method: 'POST', body: fd });
        const out = await res.json();
        
        if (out.error) {
          showToast(out.error, 'error');
          return;
        }
        
        delete seating[key];
        updateSeats();
        showToast('Siège libéré', 'info');
      } catch(e) {
        showToast('Erreur lors de la suppression', 'error');
      }
    }
    
    async function clearSeating() {
      if (!currentClassId) {
        showToast('Veuillez charger une classe d\'abord', 'error');
        return;
      }
      
      if (!confirm('Vider tous les sièges pour cette classe ?')) return;
      
      try {
        const fd = new FormData();
        fd.append('action', 'clear_seating');
        fd.append('class_id', currentClassId);
        
        const res = await fetch(api, { method: 'POST', body: fd });
        const out = await res.json();
        
        if (out.error) {
          showToast(out.error, 'error');
          return;
        }
        
        seating = {};
        updateSeats();
        showToast('Tous les sièges ont été vidés', 'success');
      } catch(e) {
        showToast('Erreur lors du vidage', 'error');
      }
    }
    
    async function randomizeSeating() {
      if (!currentClassId || allStudents.length === 0) {
        showToast('Veuillez charger une classe d\'abord', 'error');
        return;
      }
      
      const shuffled = [...allStudents].sort(() => Math.random() - 0.5);
      await applySeatingBatch(shuffled);
      showToast('Placement aléatoire appliqué', 'success');
    }
    
    async function smartSeating() {
      if (!currentClassId || allStudents.length === 0) {
        showToast('Veuillez charger une classe d\'abord', 'error');
        return;
      }
      
      const excellent = allStudents.filter(s => s.profile === 'excellent');
      const bon = allStudents.filter(s => s.profile === 'bon');
      const volontaire = allStudents.filter(s => s.profile === 'volontaire');
      const difficulte = allStudents.filter(s => s.profile === 'difficulté');
      
      const distributed = [];
      const maxLength = Math.max(excellent.length, bon.length, volontaire.length, difficulte.length);
      
      for (let i = 0; i < maxLength; i++) {
        if (i < excellent.length) distributed.push(excellent[i]);
        if (i < bon.length) distributed.push(bon[i]);
        if (i < volontaire.length) distributed.push(volontaire[i]);
        if (i < difficulte.length) distributed.push(difficulte[i]);
      }
      
      await applySeatingBatch(distributed);
      showToast('Placement intelligent appliqué', 'success');
    }
    
    async function applySeatingBatch(studentsList) {
      await clearSeating();
      
      let studentIndex = 0;
      for (let row = 1; row <= ROWS; row++) {
        for (let col = 1; col <= COLS; col++) {
          for (let seat = 1; seat <= SEATS_PER_TABLE; seat++) {
            if (studentIndex < studentsList.length) {
              await assignSeat(studentsList[studentIndex].id, row, col, seat);
              studentIndex++;
            }
          }
        }
      }
    }
    
    function updateSeatCounter() {
      const seated = Object.keys(seating).length;
      const total = allStudents.length;
      document.getElementById('seatCounter').textContent = `${seated} / ${total} placés`;
    }
    
    function filterStudents() {
      renderStudentsList();
    }
    
    function toggleStats() {
      const panel = document.getElementById('statsPanel');
      panel.classList.toggle('active');
      
      if (panel.classList.contains('active')) {
        renderStats();
      }
    }
    
    function renderStats() {
      const grid = document.getElementById('statsGrid');
      const seated = Object.keys(seating).length;
      const total = allStudents.length;
      
      const byProfile = {};
      allStudents.forEach(s => {
        if (!byProfile[s.profile]) {
          byProfile[s.profile] = { total: 0, seated: 0 };
        }
        byProfile[s.profile].total++;
        
        const isSeated = Object.values(seating).includes(s.id);
        if (isSeated) {
          byProfile[s.profile].seated++;
        }
      });
      
      grid.innerHTML = `
        <div class="stat-card primary">
          <div class="stat-value" style="color: #1e40af;">${seated} / ${total}</div>
          <div class="stat-label">Élèves placés</div>
        </div>
        ${Object.keys(byProfile).map(profile => {
          const colorClass = {
            'excellent': 'success',
            'bon': 'primary',
            'volontaire': 'warning',
            'difficulté': 'danger'
          }[profile] || 'primary';
          
          return `
            <div class="stat-card ${colorClass}">
              <div class="stat-value" style="color: ${getProfileColor(profile)};">
                ${byProfile[profile].seated} / ${byProfile[profile].total}
              </div>
              <div class="stat-label" style="text-transform: capitalize;">${profile}</div>
            </div>
          `;
        }).join('')}
      `;
    }
    
    function showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          ${type === 'success' ? '<polyline points="20 6 9 17 4 12"></polyline>' : 
            type === 'error' ? '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>' :
            '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>'}
        </svg>
        <div>${message}</div>
      `;
      
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
    
    window.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>
