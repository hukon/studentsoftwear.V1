<?php
// calendar.php
require_once __DIR__ . '/config.php';
requireLogin();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Calendrier — Anniversaires & Événements</title>
<style>
  :root{
    --primary:#2563eb; --muted:#6b7280; --bg:#f6f8fb;
  }
  *{box-sizing:border-box}
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; margin:0; background:var(--bg); color:#0f172a}
  header{background:linear-gradient(90deg,var(--primary),#1e40af); color:#fff; padding:14px 16px}
  .wrap{max-width:1100px;margin:18px auto;padding:12px}
  .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .month-title{font-size:20px;font-weight:800}
  .controls{display:flex;gap:8px;align-items:center}
  .controls button, .controls .smallbtn{
    background:#fff;border:none;padding:8px 10px;border-radius:8px;cursor:pointer;
    box-shadow:0 4px 10px rgba(2,6,23,.06)
  }
  .controls .smallbtn{padding:6px 8px;font-size:14px}
  .year-month{display:flex;flex-direction:column;align-items:center}
  .month-name{font-size:18px;font-weight:700}
  .year-name{font-size:13px;color:var(--muted)}
  .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:8px;margin-top:12px}
  .weekday{font-weight:700;padding:8px 6px;text-align:center;background:#fff;border-radius:8px;box-shadow:0 6px 12px rgba(2,6,23,.04)}
  .day{
    background:#fff;padding:8px;border-radius:10px;min-height:100px;box-shadow:0 6px 12px rgba(2,6,23,.04);
    display:flex;flex-direction:column;gap:6px;
  }
  .day.empty{background:transparent;box-shadow:none;border:none;min-height:60px}
  .day .date-num{font-weight:700}
  .events{display:flex;flex-direction:column;gap:6px;overflow:hidden}
  .event{
    display:flex;gap:8px;align-items:center;padding:6px;border-radius:8px;font-size:13px;cursor:pointer;
    border-left:4px solid transparent;background:#f8fafc;color:#0f172a;
  }
  .event.birthday{ border-left-color:#f59e0b; background:linear-gradient(90deg,#fff8e6,#fff) }
  .event.holiday{  border-left-color:#10b981; background:linear-gradient(90deg,#f0fdf4,#fff) }
  .event .dot{width:34px;height:34px;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:14px}
  .event .dot img{width:100%;height:100%;object-fit:cover}
  .empty-note{color:var(--muted);font-size:13px}
  .footer-note{margin-top:12px;color:var(--muted);font-size:13px}

  /* Modal */
  .modal-backdrop{position:fixed;inset:0;background:rgba(2,6,23,.45);display:none;align-items:center;justify-content:center;padding:16px;z-index:60}
  .modal{background:#fff;border-radius:12px;padding:14px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(2,6,23,.3)}
  label{display:block;font-weight:700;margin-top:8px}
  input[type=text],input[type=date],textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eefc}
  .row{display:flex;gap:8px;align-items:center}
  .row.reverse{flex-direction:row-reverse}
  .actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
  .btn{background:var(--primary);color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
  .btn.secondary{background:#fff;color:var(--primary);border:1px solid #e6eefc}

  /* Responsive */
  @media (max-width:800px){
    .cal-grid{grid-template-columns:repeat(7, minmax(0,1fr));gap:6px}
    .day{min-height:80px;padding:8px}
  }
  @media (max-width:480px){
    .topbar{flex-direction:column;align-items:flex-start}
    .month-title{font-size:18px}
    .day{min-height:72px;padding:8px}
  }
</style>
</head>
<body>
<header>
  <div style="max-width:1100px;margin:0 auto;display:flex;justify-content:space-between;align-items:center">
    <div style="font-weight:800">Calendrier — Anniversaires & Événements</div>
    <div><a href="index.php" style="color:#fff;text-decoration:none">↩ retour</a></div>
  </div>
</header>

<div class="wrap">
  <div class="topbar">
    <div class="year-month">
      <div class="month-name" id="monthName"></div>
      <div class="year-name" id="yearName"></div>
    </div>

    <div class="controls">
      <button id="prevBtn" class="smallbtn" title="Mois précédent">&#9664;</button>
      <button id="todayBtn" class="smallbtn" title="Mois courant">Aujourd'hui</button>
      <button id="nextBtn" class="smallbtn" title="Mois suivant">&#9654;</button>
      <button id="addEventBtn" class="smallbtn" title="Ajouter événement">➕ Ajouter un événement</button>
    </div>
  </div>

  <!-- weekday names in French (Monday-first) -->
  <div class="cal-grid" id="weekdayRow" style="margin-top:12px"></div>

  <!-- calendar days -->
  <div class="cal-grid" id="calGrid" style="margin-top:8px"></div>

  <div class="footer-note">Orange = anniversaire · Vert = jour férié · Cliquez sur un jour pour ajouter un événement.</div>
</div>

<!-- Modal: Add Event -->
<div class="modal-backdrop" id="eventModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <h3 id="modalTitle">Ajouter un événement</h3>
    <div>
      <label>Titre</label>
      <input type="text" id="evTitle" placeholder="Ex: Réunion parents-professeurs" />
      <label>Date</label>
      <input type="date" id="evDate" />
      <label><input type="checkbox" id="evRecurring" /> Répéter chaque année</label>
      <label>Notes (optionnel)</label>
      <textarea id="evNotes" rows="3" placeholder="Infos supplémentaires"></textarea>
      <div class="actions">
        <button class="btn secondary" id="closeModal">Annuler</button>
        <button class="btn" id="saveEvent">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
const api = 'api.php';
const weekdayNames = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim']; // Monday-first short labels
let viewYear = new Date().getFullYear();
let viewMonth = new Date().getMonth()+1; // 1-12

// init UI
document.getElementById('prevBtn').addEventListener('click', ()=>{ viewMonth--; if(viewMonth<1){viewMonth=12; viewYear--;} render(); });
document.getElementById('nextBtn').addEventListener('click', ()=>{ viewMonth++; if(viewMonth>12){viewMonth=1; viewYear++;} render(); });
document.getElementById('todayBtn').addEventListener('click', ()=>{ const d=new Date(); viewYear=d.getFullYear(); viewMonth=d.getMonth()+1; render(); });
document.getElementById('addEventBtn').addEventListener('click', ()=> openModal()); 
document.getElementById('closeModal').addEventListener('click', ()=> closeModal());
document.getElementById('saveEvent').addEventListener('click', saveEvent);

// build weekday row
function buildWeekdays(){
  const wk = document.getElementById('weekdayRow');
  wk.innerHTML = '';
  for(let i=0;i<7;i++){
    const d = document.createElement('div');
    d.className = 'weekday';
    d.textContent = weekdayNames[i];
    wk.appendChild(d);
  }
}

// get Monday-first offset for the 1st day of month
function monthStartOffset(y,m){
  const first = new Date(y, m-1, 1);
  let w = first.getDay(); // 0=Sun ... 6=Sat
  // convert to 0=Mon ... 6=Sun
  w = (w + 6) % 7;
  return w; // 0..6
}

// helper to format month name in French
function monthNameFr(y,m){
  return new Date(y, m-1, 1).toLocaleString('fr-FR', { month: 'long' });
}

// open modal; optional date param 'YYYY-MM-DD'
function openModal(dateStr=''){
  document.getElementById('evTitle').value = '';
  document.getElementById('evDate').value = dateStr || `${viewYear}-${String(viewMonth).padStart(2,'0')}-01`;
  document.getElementById('evRecurring').checked = false;
  document.getElementById('evNotes').value = '';
  document.getElementById('eventModal').style.display = 'flex';
  document.getElementById('evTitle').focus();
}
function closeModal(){ document.getElementById('eventModal').style.display = 'none'; }

async function saveEvent(){
  const title = document.getElementById('evTitle').value.trim();
  const date = document.getElementById('evDate').value;
  const recurring = document.getElementById('evRecurring').checked ? 1 : 0;
  const notes = document.getElementById('evNotes').value.trim();
  if(!title || !date){ alert('Titre et date requis'); return; }

  const fd = new FormData();
  fd.append('action','add_holiday');
  fd.append('title', title);
  fd.append('date', date);
  fd.append('recurring', recurring);
  fd.append('notes', notes);

  try {
    const res = await fetch(api, { method:'POST', body: fd });
    const out = await res.json();
    if(out.error){ alert('Erreur: '+out.error); return; }
    closeModal();
    render(); // reload month
  } catch (err) {
    alert('Erreur réseau: '+err.message);
  }
}

// render calendar for viewYear/viewMonth
async function render(){
  buildWeekdays();
  document.getElementById('monthName').textContent = monthNameFr(viewYear, viewMonth).toUpperCase();
  document.getElementById('yearName').textContent = viewYear;

  const grid = document.getElementById('calGrid');
  grid.innerHTML = '';

  // compute start offset and days
  const offset = monthStartOffset(viewYear, viewMonth); // 0=Mon
  const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();

  // fetch events for month
  let events = [];
  try {
    const resp = await fetch(`${api}?action=calendar_events&year=${viewYear}&month=${viewMonth}`);
    const out = await resp.json();
    // out may be {error:...} if api_err; handle
    if(out.error){ console.error(out.error); alert('Erreur chargement évènements: '+out.error); return; }
    events = out;
  } catch (e){ console.error(e); alert('Erreur réseau'); return; }

  // map events by date
  const evMap = {};
  events.forEach(ev => {
    (evMap[ev.date] = evMap[ev.date]||[]).push(ev);
  });

  // add empty cells for offset (Monday-first)
  for(let i=0;i<offset;i++){
    const empty = document.createElement('div'); empty.className='day empty'; grid.appendChild(empty);
  }

  // days
  for(let d=1; d<=daysInMonth; d++){
    const dateStr = `${String(viewYear).padStart(4,'0')}-${String(viewMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const cell = document.createElement('div'); cell.className='day';
    const num = document.createElement('div'); num.className='date-num'; num.textContent = d;
    cell.appendChild(num);

    const dayEvents = evMap[dateStr] || [];
    const evWrap = document.createElement('div'); evWrap.className='events';
    if(dayEvents.length === 0){
      // small placeholder for better print layout
      // const ph = document.createElement('div'); ph.className='empty-note'; ph.textContent=''; evWrap.appendChild(ph);
    } else {
      dayEvents.forEach(ev=>{
        const evEl = document.createElement('div');
        evEl.className = 'event ' + (ev.type === 'birthday' ? 'birthday' : 'holiday');
        // dot: image for birthday if available
        const dot = document.createElement('div'); dot.className='dot';
        if(ev.type === 'birthday'){
          if(ev.pic){
            const im = document.createElement('img'); im.src = ev.pic; im.alt = '';
            dot.appendChild(im);
          } else {
            dot.textContent = '🎂';
          }
        } else {
          dot.textContent = '📅';
        }
        const txt = document.createElement('div');
        txt.innerHTML = `<div style="font-weight:700">${ev.title}</div>`;
        evEl.appendChild(dot);
        evEl.appendChild(txt);
        evEl.addEventListener('click', () => {
          if(ev.type === 'birthday'){
            alert(ev.title + "\\n" + ev.date);
          } else {
            alert(ev.title + (ev.notes ? "\\n\\n" + ev.notes : ""));
          }
        });
        evWrap.appendChild(evEl);
      });
    }

    cell.appendChild(evWrap);

    // click on day -> open add modal with date prefilled
    cell.addEventListener('click', (e)=>{
      // avoid opening modal when clicking an event (we want click on cell)
      if(e.target.closest('.event')) return;
      openModal(dateStr);
    });

    grid.appendChild(cell);
  }

  // fill trailing empty cells so grid keeps 7-column shape (optional)
  const totalCells = offset + daysInMonth;
  const trailing = (7 - (totalCells % 7)) % 7;
  for(let i=0;i<trailing;i++){
    const empty = document.createElement('div'); empty.className='day empty'; grid.appendChild(empty);
  }
}

// init
document.addEventListener('DOMContentLoaded', ()=>{
  render();
});
</script>
</body>
</html>
