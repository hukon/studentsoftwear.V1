<?php
require_once __DIR__ . '/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Liste des étudiants</title>
  <style>
    :root{
      --primary:#2563eb; --primary-dark:#1e40af; --bg:#f8fafc; --card:#ffffff; --muted:#6b7280;
      --ring: rgba(37,99,235,0.25);
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif;background:var(--bg);color:#111827}
    header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:linear-gradient(90deg,var(--primary),var(--primary-dark));color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.1)}
    header .title{font-weight:800;letter-spacing:.2px}
    header .user{opacity:.95}
    main{max-width:1200px;margin:24px auto;padding:0 16px 48px}
    .row{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
    .card{background:var(--card);border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.06);padding:16px}
    .classes{display:flex;gap:12px;flex-wrap:wrap}
    .pill{padding:10px 14px;border-radius:999px;background:#eef2ff;color:#1e3a8a;font-weight:600;border:1px solid #e5e7eb;cursor:pointer;transition:.2s}
    .pill:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(0,0,0,.06)}
    .btn{background:var(--primary);color:#fff;border:none;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer;transition:.2s}
    .btn.secondary{background:#1118270d;color:#111827;border:1px solid #e5e7eb}
    .btn:hover{background:var(--primary-dark)}
    .btn:focus{outline:2px solid var(--ring)}
    .tabs{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .tab{display:flex;align-items:center;gap:10px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:12px;padding:8px 12px;font-weight:700;color:#1e3a8a;cursor:pointer}
    .tab.active{background:#c7d2fe}
    .tab button{border:none;background:transparent;cursor:pointer;font-size:14px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:14px}
    .student{padding:16px;border-radius:16px;background:var(--card);box-shadow:0 6px 18px rgba(0,0,0,.06)}
    .student img{width:84px;height:84px;border-radius:50%;object-fit:cover;border:3px solid #e5e7eb}
    .muted{color:var(--muted)}
    .badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#f1f5f9;border:1px solid #e5e7eb;margin:2px 4px 0 0;font-size:12px}
    .badge.difficulty{background:#fee2e2;border-color:#fecaca;color:#991b1b}
    .toolbar{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0}
    .toolbar .btn{padding:8px 10px;font-size:14px}
    .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center;padding:16px;z-index:40}
    .modal{width:min(720px,96vw);background:var(--card);border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.2);padding:18px;max-height:90vh;overflow-y:auto}
    .modal header{background:transparent;color:#111827;box-shadow:none;padding:0;margin:0 0 10px;justify-content:space-between}
    label{font-weight:600;font-size:14px;margin-top:8px;display:inline-block}
    input[type=text],input[type=date],select,textarea{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px}
    input:focus,select:focus,textarea:focus{outline:2px solid var(--ring);border-color:#bfdbfe}
    .row.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .danger{background:#dc2626}
    .danger:hover{background:#991b1b}
    .danger:disabled{background:#9ca3af;cursor:not-allowed}
    .danger:disabled:hover{background:#9ca3af}
    .preview{display:flex;align-items:center;gap:12px;margin-top:8px}
    .preview img{width:64px;height:64px;border-radius:12px;object-fit:cover;border:2px solid #e5e7eb}
    .close{border:none;background:#1118270d;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;cursor:pointer}
    .empty{padding:24px;text-align:center;color:var(--muted)}
    .filter-panel{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:12px}
    .filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:10px}
    .search-box{width:100%;padding:10px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:15px}
    .search-box:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--ring)}
    .category-group{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff}
    .category-group legend{font-weight:700;font-size:13px;padding:0 6px;color:var(--primary)}
    .checkbox-item{display:flex;align-items:center;gap:6px;margin:4px 0}
    .checkbox-item input[type=checkbox]{width:18px;height:18px;cursor:pointer}
    .checkbox-item label{font-weight:400;cursor:pointer;margin:0}
  </style>
</head>
<body>
  <header>
    <div class="title">📚 Liste des étudiants</div>
    <div class="user"><a href="index.php" style="color:#fff;text-decoration:none">↩ retour</a></div>
  </header>
  
  <main>
    <section class="card">
      <div class="row">
        <button class="btn" id="btnNewClass">➕ Nouvelle classe</button>
        <button class="btn secondary" id="btnRefresh">⟳ Actualiser</button>
      </div>
      <div class="classes" id="classList" style="margin-top:12px;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
      <div class="tabs" id="tabs"></div>
      <div id="tabPanels" style="margin-top:12px;"></div>
    </section>
  </main>

  <div class="modal-backdrop" id="classModal">
    <div class="modal">
      <header class="row">
        <h3>➕ Créer une classe</h3>
        <button class="close" data-close="classModal">Fermer</button>
      </header>
      <div>
        <label for="className">Nom (ex: 1AM2, 3AM1)</label>
        <input type="text" id="className" placeholder="1AM2" />
        <div class="row" style="margin-top:12px">
          <button class="btn" id="saveClass">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="studentModal">
    <div class="modal">
      <header class="row">
        <h3 id="studentModalTitle">➕ Ajouter un étudiant</h3>
        <button class="close" data-close="studentModal">Fermer</button>
      </header>
      <div>
        <input type="hidden" id="studentId" />
        <div class="row two">
          <div>
            <label for="studentName">Nom complet</label>
            <input type="text" id="studentName"/>
          </div>
          <div>
            <label for="studentDob">Date de naissance</label>
            <input type="date" id="studentDob"/>
          </div>
        </div>
        
        <label for="studentBio">Bio / Notes</label>
        <textarea id="studentBio" rows="3" placeholder="Notes, comportement, etc."></textarea>
        
        <div style="margin-top:12px">
          <label>Profil général de l'élève</label>
          <div style="margin-top:8px;padding:12px;background:#f9fafb;border-radius:8px">
            <label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer">
              <input type="radio" name="studentProfile" value="Élève excellent" id="profile1"/>
              <span>Élève excellent</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer">
              <input type="radio" name="studentProfile" value="Élève bon mais peu participatif" id="profile2"/>
              <span>Élève bon mais peu participatif</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer">
              <input type="radio" name="studentProfile" value="Élève volontaire mais en difficulté" id="profile3"/>
              <span>Élève volontaire mais en difficulté</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer">
              <input type="radio" name="studentProfile" value="Élève en difficulté et démotivé" id="profile4"/>
              <span>Élève en difficulté et démotivé</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;margin:6px 0;cursor:pointer">
              <input type="radio" name="studentProfile" value="" id="profile_none" checked/>
              <span style="color:#9ca3af">— Aucun profil sélectionné —</span>
            </label>
          </div>
        </div>
        
        <div style="margin-top:12px">
          <label>Difficultés spécifiques identifiées (cochez toutes les cases pertinentes)</label>
          <div class="row two" style="margin-top:8px">
            <fieldset class="category-group">
              <legend>Catégorie 1</legend>
              <div class="checkbox-item">
                <input type="checkbox" id="cat_comprehension" value="comprehension_orale"/>
                <label for="cat_comprehension">Compréhension orale</label>
              </div>
            </fieldset>
            
            <fieldset class="category-group">
              <legend>Catégorie 2</legend>
              <div class="checkbox-item">
                <input type="checkbox" id="cat_ecriture" value="ecriture"/>
                <label for="cat_ecriture">Écriture</label>
              </div>
            </fieldset>
          </div>
          
          <div class="row two" style="margin-top:8px">
            <fieldset class="category-group">
              <legend>Catégorie 3: Point de langue</legend>
              <div class="checkbox-item">
                <input type="checkbox" id="cat_vocab" value="vocabulaire"/>
                <label for="cat_vocab">A. Vocabulaire</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" id="cat_gram" value="grammaire"/>
                <label for="cat_gram">B. Grammaire</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" id="cat_conj" value="conjugaison"/>
                <label for="cat_conj">C. Conjugaison</label>
              </div>
            </fieldset>
            
            <fieldset class="category-group">
              <legend>Catégorie 4</legend>
              <div class="checkbox-item">
                <input type="checkbox" id="cat_production" value="production_ecrite"/>
                <label for="cat_production">Production écrite</label>
              </div>
            </fieldset>
          </div>
        </div>
        
        <div style="margin-top:10px">
          <label for="studentPic">Photo (depuis l'ordinateur)</label>
          <input type="file" id="studentPic" accept="image/*"/>
          <div class="preview" id="picPreview" style="display:none">
            <img id="picImg" alt="preview"/>
            <span class="muted">Aperçu</span>
          </div>
        </div>
        
        <div class="row" style="margin-top:12px">
          <button class="btn" id="saveStudent">Enregistrer</button>
          <button class="btn danger" id="deleteStudent" style="display:none">Supprimer</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const api = 'api.php';
    const classList = document.getElementById('classList');
    const tabs = document.getElementById('tabs');
    const tabPanels = document.getElementById('tabPanels');
    const classModal = document.getElementById('classModal');
    const studentModal = document.getElementById('studentModal');

    const ENABLE_DELETE = false;

    const openModal = (el)=> el.style.display='flex';
    const closeModal = (id)=> document.getElementById(id).style.display='none';
    document.querySelectorAll('.close').forEach(b=> b.addEventListener('click', (e)=> closeModal(e.target.dataset.close)));

    document.getElementById('btnNewClass').onclick = ()=> { document.getElementById('className').value=''; openModal(classModal); };
    document.getElementById('btnRefresh').onclick = ()=> loadClasses();

    async function loadClasses(){
      const res = await fetch(`${api}?action=classes`);
      const data = await res.json();
      classList.innerHTML = '';
      data.forEach(c=>{
        const pill = document.createElement('button');
        pill.className='pill';
        pill.textContent = c.name;
        pill.onclick = ()=> openClassTab(c);
        classList.appendChild(pill);
      });
    }

    document.getElementById('saveClass').onclick = async ()=>{
      const name = document.getElementById('className').value.trim();
      if(!name) return alert('Nom de classe requis');
      const res = await fetch(api, {method:'POST', body:new URLSearchParams({action:'add_class', name})});
      const out = await res.json();
      if(out.error) return alert(out.error);
      closeModal('classModal');
      loadClasses();
    };

    const openTabs = new Map();

    async function openClassTab(c){
      if(openTabs.has(c.id)){
        activateTab(c.id);
        return;
      }
      
      const tab = document.createElement('div');
      tab.className = 'tab active';
      tab.innerHTML = `<span>${c.name}</span> <button title="Fermer">✕</button>`;
      tabs.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
      tabs.appendChild(tab);

      tab.querySelector('button').onclick = ()=>{
        const entry = openTabs.get(c.id);
        entry.panelEl.remove();
        tab.remove();
        openTabs.delete(c.id);
        const last = Array.from(tabs.querySelectorAll('.tab')).pop();
        if(last){
          const id = [...openTabs.keys()].pop();
          if(id) activateTab(id);
        }
      };

      const panel = document.createElement('div');
      panel.className='card';
      panel.innerHTML = `
        <div class="filter-panel">
          <div style="margin-bottom:10px">
            <input type="text" class="search-box" placeholder="🔍 Rechercher un étudiant par nom..." data-search />
          </div>
          
          <div class="filter-grid">
            <div>
              <label style="display:block;margin-bottom:4px;font-size:13px">Trier par</label>
              <select class="sortBy" style="width:100%">
                <option value="name_asc">Nom (A-Z)</option>
                <option value="name_desc">Nom (Z-A)</option>
                <option value="dob_asc">Date naissance (↑)</option>
                <option value="dob_desc">Date naissance (↓)</option>
              </select>
            </div>
            
            <div>
              <label style="display:block;margin-bottom:4px;font-size:13px">Profil général</label>
              <select class="profileFilter" style="width:100%">
                <option value="">Tous les profils</option>
                <option value="Élève excellent">Élève excellent</option>
                <option value="Élève bon mais peu participatif">Élève bon mais peu participatif</option>
                <option value="Élève volontaire mais en difficulté">Élève volontaire mais en difficulté</option>
                <option value="Élève en difficulté et démotivé">Élève en difficulté et démotivé</option>
              </select>
            </div>
            
            <div>
              <label style="display:block;margin-bottom:4px;font-size:13px">Difficulté spécifique</label>
              <select class="quickFilter" style="width:100%">
                <option value="">Toutes les difficultés</option>
                <option value="comprehension_orale">Compréhension orale</option>
                <option value="ecriture">Écriture</option>
                <option value="vocabulaire">Vocabulaire</option>
                <option value="grammaire">Grammaire</option>
                <option value="conjugaison">Conjugaison</option>
                <option value="production_ecrite">Production écrite</option>
              </select>
            </div>
          </div>
          
          <div class="toolbar">
            <button class="btn" data-add>➕ Ajouter un étudiant</button>
            <button class="btn secondary" data-reload>⟳ Actualiser</button>
            <button class="btn secondary" data-clear-filters>✕ Réinitialiser filtres</button>
          </div>
        </div>
        
        <div class="stats" style="margin:10px 0;color:var(--muted);font-size:14px">
          <span data-stats>Chargement...</span>
        </div>
        
        <div class="grid" data-grid></div>
      `;
      tabPanels.appendChild(panel);

      openTabs.set(c.id, {tabEl:tab, panelEl:panel, allStudents: []});
      
      panel.querySelector('[data-add]').onclick = ()=> openStudentModal(c.id);
      panel.querySelector('[data-reload]').onclick = ()=> loadStudents(c.id);
      panel.querySelector('[data-clear-filters]').onclick = ()=> {
        panel.querySelector('[data-search]').value = '';
        panel.querySelector('.sortBy').value = 'name_asc';
        panel.querySelector('.profileFilter').value = '';
        panel.querySelector('.quickFilter').value = '';
        loadStudents(c.id);
      };
      
      panel.querySelector('[data-search]').addEventListener('input', ()=> filterAndDisplayStudents(c.id));
      panel.querySelector('.sortBy').addEventListener('change', ()=> filterAndDisplayStudents(c.id));
      panel.querySelector('.profileFilter').addEventListener('change', ()=> filterAndDisplayStudents(c.id));
      panel.querySelector('.quickFilter').addEventListener('change', ()=> filterAndDisplayStudents(c.id));

      await loadStudents(c.id);
      activateTab(c.id);
    }

    function activateTab(classId){
      tabs.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
      const entry = openTabs.get(classId);
      if(entry){
        entry.tabEl.classList.add('active');
        Array.from(tabPanels.children).forEach(ch=> ch.style.display='none');
        entry.panelEl.style.display='block';
      }
    }

    async function loadStudents(classId){
      const entry = openTabs.get(classId);
      const grid = entry.panelEl.querySelector('[data-grid]');
      grid.innerHTML = '<div class="empty">Chargement...</div>';
      
      const res = await fetch(`${api}?action=students&class_id=${classId}`);
      const data = await res.json();
      
      entry.allStudents = Array.isArray(data) ? data : [];
      filterAndDisplayStudents(classId);
    }

    function filterAndDisplayStudents(classId){
      const entry = openTabs.get(classId);
      const panel = entry.panelEl;
      const grid = panel.querySelector('[data-grid]');
      const searchTerm = panel.querySelector('[data-search]').value.toLowerCase().trim();
      const sortBy = panel.querySelector('.sortBy').value;
      const profileFilter = panel.querySelector('.profileFilter').value;
      const quickFilter = panel.querySelector('.quickFilter').value;
      
      let filtered = [...entry.allStudents];
      
      if(searchTerm){
        filtered = filtered.filter(s => s.name.toLowerCase().includes(searchTerm));
      }
      
      if(profileFilter){
        filtered = filtered.filter(s => (s.category1 || '') === profileFilter);
      }
      
      if(quickFilter){
        filtered = filtered.filter(s => s[quickFilter] === 1 || s[quickFilter] === true);
      }
      
      filtered.sort((a, b) => {
        switch(sortBy){
          case 'name_asc': return a.name.localeCompare(b.name);
          case 'name_desc': return b.name.localeCompare(a.name);
          case 'dob_asc': return (a.dob || '9999').localeCompare(b.dob || '9999');
          case 'dob_desc': return (b.dob || '').localeCompare(a.dob || '');
          default: return 0;
        }
      });
      
      const statsEl = panel.querySelector('[data-stats]');
      const activeFilters = [];
      if(searchTerm) activeFilters.push(`recherche: "${searchTerm}"`);
      if(profileFilter) activeFilters.push(`profil: ${profileFilter}`);
      if(quickFilter) activeFilters.push(`difficulté: ${quickFilter}`);
      
      statsEl.innerHTML = `
        Affichage de <strong>${filtered.length}</strong> étudiant(s) sur <strong>${entry.allStudents.length}</strong>
        ${activeFilters.length ? ` (${activeFilters.join(', ')})` : ''}
      `;
      
      if(!filtered.length){
        grid.innerHTML = '<div class="empty">Aucun étudiant correspondant aux critères.</div>';
        return;
      }
      
      grid.innerHTML = '';
      filtered.forEach(s => {
        const difficulties = [];
        if(s.comprehension_orale) difficulties.push('Compréhension orale');
        if(s.ecriture) difficulties.push('Écriture');
        if(s.vocabulaire) difficulties.push('Vocabulaire');
        if(s.grammaire) difficulties.push('Grammaire');
        if(s.conjugaison) difficulties.push('Conjugaison');
        if(s.production_ecrite) difficulties.push('Production écrite');
        
        let imageUrl = '';
        if (s.pic_path) {
          imageUrl = s.pic_path;
          if (imageUrl.indexOf('?') === -1) {
            imageUrl += '?t=' + Date.now();
          }
        }
        
        const card = document.createElement('div');
        card.className='student';
        card.innerHTML = `
          <div class="row">
            <img src="${imageUrl}" 
                 crossorigin="anonymous"
                 onerror="this.style.display='none';" 
                 alt="Photo de ${s.name}"
                 style="${imageUrl ? '' : 'display:none'}"/>
            <div style="flex:1">
              <div style="font-weight:800">${s.name}</div>
              <div class="muted">${s.dob ? '📅 ' + s.dob : ''}</div>
              ${s.bio ? `<div class="muted" style="margin-top:4px;font-size:13px">${s.bio}</div>` : ''}
              ${s.category1 ? `<div style="margin-top:6px"><span class='badge' style="background:#dbeafe;color:#1e40af;font-weight:600">${s.category1}</span></div>` : ''}
              ${difficulties.length ? `
                <div style="margin-top:6px">
                  ${difficulties.map(d=>`<span class='badge difficulty'>${d}</span>`).join('')}
                </div>
              ` : ''}
            </div>
          </div>
          <div class="toolbar">
            <button class="btn" data-edit>✏️ Modifier</button>
            <button class="btn danger" data-del ${!ENABLE_DELETE ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''}>
              🗑️ Supprimer
            </button>
          </div>
        `;
        
        card.querySelector('[data-edit]').onclick = ()=> openStudentModal(classId, s);
        card.querySelector('[data-del]').onclick = ()=> deleteStudent(s.id, classId);
        grid.appendChild(card);
      });
    }

    async function deleteStudent(id, classId){
      if (!ENABLE_DELETE) {
        alert('⚠️ La suppression est désactivée.\n\nPour activer cette fonction, contactez l\'administrateur.');
        return;
      }
      
      if(!confirm('⚠️ ATTENTION: Êtes-vous sûr de vouloir supprimer cet étudiant ?')) return;
      if(!confirm('Cette action est IRRÉVERSIBLE. Confirmer la suppression ?')) return;
      
      const res = await fetch(api, {
        method:'POST', 
        body: new URLSearchParams({action:'delete_student', id})
      });
      const out = await res.json();
      if(out.error) return alert(out.error);
      
      alert('✅ Étudiant supprimé avec succès');
      closeModal('studentModal');
      await loadStudents(classId);
    }

    function openStudentModal(classId, student=null){
      document.getElementById('studentId').value = student ? student.id : '';
      document.getElementById('studentName').value = student ? student.name : '';
      document.getElementById('studentDob').value = student ? (student.dob || '') : '';
      document.getElementById('studentBio').value = student ? (student.bio || '') : '';
      document.getElementById('studentPic').value = '';

      const profileValue = student?.category1 || '';
      document.querySelectorAll('input[name="studentProfile"]').forEach(radio => {
        radio.checked = (radio.value === profileValue);
      });
      if (!profileValue) {
        document.getElementById('profile_none').checked = true;
      }

      document.getElementById('cat_comprehension').checked = student?.comprehension_orale == 1;
      document.getElementById('cat_ecriture').checked = student?.ecriture == 1;
      document.getElementById('cat_vocab').checked = student?.vocabulaire == 1;
      document.getElementById('cat_gram').checked = student?.grammaire == 1;
      document.getElementById('cat_conj').checked = student?.conjugaison == 1;
      document.getElementById('cat_production').checked = student?.production_ecrite == 1;

      document.getElementById('studentModalTitle').textContent = student ? '✏️ Modifier un étudiant' : '➕ Ajouter un étudiant';
      const delBtn = document.getElementById('deleteStudent');
      
      if (student && ENABLE_DELETE) {
        delBtn.style.display = 'inline-block';
        delBtn.disabled = false;
        delBtn.style.opacity = '1';
        delBtn.style.cursor = 'pointer';
      } else if (student && !ENABLE_DELETE) {
        delBtn.style.display = 'inline-block';
        delBtn.disabled = true;
        delBtn.style.opacity = '0.5';
        delBtn.style.cursor = 'not-allowed';
        delBtn.title = 'Suppression désactivée';
      } else {
        delBtn.style.display = 'none';
      }
      
      delBtn.onclick = ()=> deleteStudent(student.id, classId);
      document.getElementById('saveStudent').onclick = ()=> saveStudent(classId);
      openModal(studentModal);
    }

    const picInput = document.getElementById('studentPic');
    const picPreview = document.getElementById('picPreview');
    const picImg = document.getElementById('picImg');

    picInput.addEventListener('change', (e)=>{
      const f = e.target.files[0];
      if(!f){ 
        picPreview.style.display='none'; 
        return; 
      }
      const url = URL.createObjectURL(f);
      picImg.src = url;
      picPreview.style.display='flex';
    });

    async function saveStudent(classId){
      const saveBtn = document.getElementById('saveStudent');
      const oldText = saveBtn.textContent;
      saveBtn.disabled = true;
      saveBtn.textContent = "Enregistrement...";

      const id = document.getElementById('studentId').value;
      const name = document.getElementById('studentName').value.trim();
      const dob = document.getElementById('studentDob').value;
      const bio = document.getElementById('studentBio').value.trim();

      if(!name){ 
        alert('Nom requis'); 
        saveBtn.disabled = false;
        saveBtn.textContent = oldText;
        return; 
      }

      const fd = new FormData();
      fd.append('action', id ? 'update_student' : 'add_student');
      if(id) fd.append('id', id);
      fd.append('class_id', classId);
      fd.append('name', name);
      fd.append('dob', dob);
      fd.append('bio', bio);
      
      const selectedProfile = document.querySelector('input[name="studentProfile"]:checked');
      fd.append('category1', selectedProfile ? selectedProfile.value : '');
      
      fd.append('comprehension_orale', document.getElementById('cat_comprehension').checked ? 1 : 0);
      fd.append('ecriture', document.getElementById('cat_ecriture').checked ? 1 : 0);
      fd.append('vocabulaire', document.getElementById('cat_vocab').checked ? 1 : 0);
      fd.append('grammaire', document.getElementById('cat_gram').checked ? 1 : 0);
      fd.append('conjugaison', document.getElementById('cat_conj').checked ? 1 : 0);
      fd.append('production_ecrite', document.getElementById('cat_production').checked ? 1 : 0);

      const file = document.getElementById('studentPic').files[0];
      if(file) fd.append('photo', file);

      try {
        const res = await fetch(api, { method: 'POST', body: fd });
        const out = await res.json();

        if(out.error){
          alert("Erreur: " + out.error);
          return;
        }

        closeModal('studentModal');
        await loadStudents(classId);

      } catch(err){
        alert("Erreur réseau: " + err.message);
      } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = oldText;
      }
    }

    loadClasses();
  </script>
</body>
</html>