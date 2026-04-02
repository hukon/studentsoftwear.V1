<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seating Planner</title>
<style>
  :root{--primary:#2563eb;--muted:#6b7280}
  body{font-family:system-ui,Arial;margin:0;background:#f6f8fb;color:#111}
  header{background:linear-gradient(90deg,var(--primary),#1e40af);color:#fff;padding:14px 18px}
  main{max-width:1100px;margin:18px auto;padding:0 16px}
  .toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  select,button{padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb}
  .layout{display:flex;gap:18px;margin-top:18px;align-items:flex-start}
  .classroom{display:flex;gap:12px}
  .col{display:flex;flex-direction:column;gap:12px}
  .table{display:flex;gap:8px;align-items:center;padding:8px;border-radius:8px;background:#fff;box-shadow:0 6px 18px rgba(2,6,23,.06);width:220px}
  .seat{width:92px;height:64px;border-radius:8px;background:#f8fafc;border:1px dashed #e5e7eb;display:flex;align-items:center;justify-content:center;flex-direction:column;cursor:pointer}
  .seat.occupied{background:#ecfdf5;border-color:#bbf7d0}
  .students{flex:1;min-width:260px;background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,.06)}
  .student-card{padding:8px;margin:6px 0;border-radius:8px;background:#f1f5f9;border:1px solid #e6eefc;cursor:grab;display:flex;gap:10px;align-items:center}
  .student-card img{width:42px;height:42px;border-radius:8px;object-fit:cover}
  .legend{font-size:13px;color:var(--muted);margin-top:8px}
  .small{font-size:13px;color:#374151}
  @media (max-width:900px){
    .layout{flex-direction:column}
    .classroom{flex-wrap:wrap}
  }
  @media print {
  body * {
    visibility: hidden;
  }
  #seatingArea, #seatingArea * {
    visibility: visible;
  }
  #seatingArea {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
  }
  .table {
    border: 2px solid #000 !important;
    height: 100px;
    page-break-inside: avoid;
    background: #fff !important;
  }
  .seat {
    border: 1px solid #000 !important;
    background: #fff !important;
    color: #000 !important;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .seat img {
    max-width: 40px;
    max-height: 40px;
    display: block;
    margin: 0 auto 2px auto;
  }
  .classroom {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  width: 100%;
}

.row-label {
  text-align: center;
  font-weight: bold;
  margin: 6px 0;
  color: #1e40af;
}

@media (max-width: 900px) {
  .classroom {
    grid-template-columns: 1fr; /* stack rows on small screens */
  }
}
}



</style>
</head>
<body>
  <header>
    <div style="display:flex;justify-content:space-between;align-items:center;max-width:1100px;margin:0 auto">
      <div><strong>Seating Planner</strong></div>
      <div><a href="index.php" style="color:#fff;text-decoration:none">↩ retour</a></div>
    </div>
  </header>

  <main>
    <div class="toolbar">
      <label for="classSelect">Choisir classe:</label>
      <select id="classSelect" onchange="loadData()"></select>
      <button id="btnLoad">Charger</button>
      <button id="btnClear">Vider sièges</button>
      <button onclick="printSeating()">🖨️ Print Seating Chart</button>
      <div class="legend">Drag → Drop students onto seats. Drag a seated student back to the list to unassign.</div>
    </div>

    <div class="layout">
      <div id="classroomWrap" class="classroom card" style="padding:6px;background:transparent;border:none">
        <!-- columns rendered by JS -->
      </div>

      <div class="students" id="studentsPanel">
        <h3>Students</h3>
        <div id="studentList"></div>
        <div class="small">Drag students to seats. Click seat to remove.</div>
      </div>
    </div>
  </main>

<script>
const api = 'api.php';
const COLS = 4;
const ROWS = 6;
const SEATS_PER_TABLE = 2;
let currentClassId = 0;

// helpers
function el(tag, cls = ''){ const d = document.createElement(tag); if(cls) d.className = cls; return d; }

async function loadClasses(){
  const res = await fetch(api + '?action=classes');
  const arr = await res.json();
  const sel = document.getElementById('classSelect');
  sel.innerHTML = '<option value="">-- Choisir --</option>';
  arr.forEach(c => {
    const opt = document.createElement('option'); opt.value = c.id; opt.textContent = c.name;
    sel.appendChild(opt);
  });
}

function buildClassroom(){
  const wrap = document.getElementById('classroomWrap');
  wrap.innerHTML = '';
  for(let col=1; col<=COLS; col++){
    const colDiv = el('div','col');

    // Add a label for the column (Row number for clarity)
    const label = el('div','row-label');
    label.textContent = 'Row ' + col;
    colDiv.appendChild(label);

    for(let row=1; row<=ROWS; row++){
      const table = el('div','table');
      for(let seat=1; seat<=SEATS_PER_TABLE; seat++){
        const seatDiv = el('div','seat');
        seatDiv.dataset.row = row;
        seatDiv.dataset.col = col;
        seatDiv.dataset.seat = seat;
        seatDiv.innerHTML = `<div class="small">R${row} C${col} S${seat}</div>`;
        seatDiv.ondragover = e => e.preventDefault();
        seatDiv.ondrop = async (e) => {
          e.preventDefault();
          const studentId = e.dataTransfer.getData('student');
          if(!studentId) return;
          await assignSeat(currentClassId, studentId, row, col, seat);
        };
        seatDiv.onclick = async () => {
          const sId = seatDiv.dataset.studentId;
          if(sId){
            if(!confirm('Retirer cet élève du siège ?')) return;
            await removeSeat(currentClassId, sId);
          }
        };
        table.appendChild(seatDiv);
      }
      colDiv.appendChild(table);
    }
    wrap.appendChild(colDiv);
  }
}

// load students pool
async function loadStudents(classId){
  const res = await fetch(api + '?action=students&class_id=' + classId);
  const arr = await res.json();
  const list = document.getElementById('studentList');
  list.innerHTML = '';
  arr.forEach(s => {
    const card = el('div','student-card');
    card.draggable = true;
    card.dataset.studentId = s.id;
    card.ondragstart = (e) => e.dataTransfer.setData('student', s.id);
    const img = el('img'); img.src = s.pic_path ? (s.pic_path.startsWith('http') ? s.pic_path : ('uploads/' + s.pic_path.split('/').pop())) : '';
    img.onerror = () => img.style.display='none';
    const name = el('div'); name.innerHTML = `<strong>${s.name}</strong><div class="small">${s.dob ?? ''}</div>`;
    card.appendChild(img);
    card.appendChild(name);
    list.appendChild(card);
  });
}

// load seating and populate classroom seats
async function loadSeating(classId){
  // clear seat studentId
  document.querySelectorAll('.seat').forEach(sd => { sd.classList.remove('occupied'); sd.dataset.studentId=''; sd.innerHTML = `<div class="small">R${sd.dataset.row} C${sd.dataset.col} S${sd.dataset.seat}</div>`; });

  const res = await fetch(api + '?action=get_seating&class_id=' + classId);
  const arr = await res.json();
  arr.forEach(s => {
    // find seat
    const sel = `.seat[data-row="${s.row_num}"][data-col="${s.col_num}"][data-seat="${s.seat_num}"]`;
    const seatDiv = document.querySelector(sel);
    if(seatDiv){
      seatDiv.classList.add('occupied');
      seatDiv.dataset.studentId = s.student_id;
      const imgHtml = s.pic_url ? `<img src="${s.pic_url}" style="width:36px;height:36px;border-radius:6px;object-fit:cover;margin-bottom:4px"/>` : '';
      seatDiv.innerHTML = `${imgHtml}<div style="font-weight:700">${s.name}</div>`;
      // allow seat to be draggable: drag the occupant back to list to unassign
      seatDiv.draggable = true;
      seatDiv.ondragstart = (e) => {
        // carry student id so dropping on student list will unassign
        e.dataTransfer.setData('student_assigned', s.student_id);
      };
    }
  });
}

// assign seat API call
async function assignSeat(classId, studentId, row, col, seat){
  const fd = new FormData();
  fd.append('action','set_seating');
  fd.append('class_id', classId);
  fd.append('student_id', studentId);
  fd.append('row_num', row);
  fd.append('col_num', col);
  fd.append('seat_num', seat);
  const res = await fetch(api, { method:'POST', body: fd });
  const out = await res.json();
  if(out.error){ alert(out.error); return; }
  await loadSeating(classId);
  // also reload student pool so the assigned student is still visible (you may optionally hide assigned students)
  await loadStudents(classId);
}

// remove/unassign by student id
async function removeSeat(classId, studentId){
  const fd = new FormData();
  fd.append('action','remove_seating');
  fd.append('class_id', classId);
  fd.append('student_id', studentId);
  const res = await fetch(api, { method:'POST', body: fd });
  const out = await res.json();
  if(out.error){ alert(out.error); return; }
  await loadSeating(classId);
  await loadStudents(classId);
}

// allow dropping seats back to student list to unassign
const studentListEl = document.getElementById('studentList');
studentListEl.ondragover = e => e.preventDefault();
studentListEl.ondrop = async (e) => {
  e.preventDefault();
  const assigned = e.dataTransfer.getData('student_assigned');
  if(assigned){
    // unassign by id
    if(!confirm('Retirer cet élève du siège ?')) return;
    await removeSeat(currentClassId, assigned);
  } else {
    // dropping a raw student card (no-op)
  }
};

// clicking Load
document.getElementById('btnLoad').onclick = async () => {
  const cid = parseInt(document.getElementById('classSelect').value || 0);
  if(!cid){ alert('Sélectionnez une classe'); return; }
  currentClassId = cid;
  await loadStudents(cid);
  await loadSeating(cid);
};

// clear seating
document.getElementById('btnClear').onclick = async () => {
  if(!currentClassId){ alert('Ouvrez une classe d\'abord'); return; }
  if(!confirm('Vider tous les sièges pour cette classe ?')) return;
  const fd = new FormData(); fd.append('action','clear_seating'); fd.append('class_id', currentClassId);
  const res = await fetch(api, { method:'POST', body: fd });
  const out = await res.json();
  if(out.error){ alert(out.error); return; }
  await loadSeating(currentClassId);
};

window.addEventListener('DOMContentLoaded', async () => {
  buildClassroom();
  await loadClasses();
});

async function loadData(){
  const classId = document.getElementById('classSelect').value;
  if(!classId) return;
  currentClassId = classId;
  await loadStudents(classId);
  await loadSeating(classId);
}

function printSeating(){
  window.print();
}


</script>
</body>
</html>
