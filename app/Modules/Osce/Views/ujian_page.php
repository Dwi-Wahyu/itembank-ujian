<?php $os = $os ?? []; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#0EA5A5">
  <title>Ujian OSCE</title>
  <?= csrf_meta() ?>
  <link rel="manifest" href="<?= base_url('osce/manifest.json') ?>">
  <link rel="stylesheet" href="<?= base_url('osce/osce-panel.css') ?>">
<style>
  /* --- yang lama tetap --- */
  .gps-panel{background:#10b9811a;border-radius:12px;padding:12px 14px;color:#065f46;}
  .gps-panel b{color:#065f46}
  .q-title.gps{font-weight:700}
  .page.gps{min-width:42px}
  .gps-wrap{display:flex;gap:18px;align-items:flex-start;margin-top:10px}
  .gps-title{font-weight:700;margin-right:8px;min-width:220px}
  .gps-group .opt{display:flex;align-items:center;margin:6px 0}
  .gps-group .opt input{margin-right:8px}

  /* =========================
     RESPONSIVE LAYOUT GLOBAL
     ========================= */
  body {
    margin: 0;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background:#f3f4f6;
  }

  /* Topbar admin (logo + pengawas) */
  .topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:8px 16px;
    background:#0EA5A5;
    color:#fff;
    gap:12px;
  }
  .topbar .brand {
    display:flex;
    align-items:center;
    gap:8px;
    min-width:0;
  }
  .topbar .brand img {
    width:32px;
    height:32px;
    object-fit:contain;
  }
  .topbar .brand span {
    font-weight:600;
    white-space:nowrap;
  }
  .topbar .top-actions {
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
  }

  .btn.ghost {
    border:1px solid rgba(255,255,255,.7);
    color:#fff;
    background:transparent;
    padding:4px 10px;
    border-radius:999px;
    font-size:0.875rem;
  }

  /* Topbar ujian (mahasiswa + timer) */
  .exam-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    padding:10px 16px;
    background:#ffffff;
     color:#111827;   
    border-bottom:1px solid #e5e7eb;
    flex-wrap:wrap;
  }
  .exam-brand {
    font-weight:600;
    font-size:0.95rem;
  }
  .exam-user {
    font-size:0.9rem;
  }
  .exam-user small {
    font-size:0.8rem;
    color:#6b7280;
  }
  .exam-timer {
    margin-left:auto;
    font-weight:500;
    font-size:0.85rem;
    white-space:nowrap;
      color:#111827 !important;
  }
  .icon-btn {
    border:none;
    background:#fee2e2;
    color:#b91c1c;
    border-radius:999px;
    width:32px;
    height:32px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    font-size:1.1rem;
  }
.exam-topbar .icon-btn {
  color:#b91c1c;
}
  /* =========================
     LAYOUT KONTEN (SIDEBAR + SOAL)
     ========================= */
  .exam-body {
    max-width:1200px;
    margin:0 auto;
    padding:16px;
    display:grid;
    grid-template-columns:minmax(260px, 0.9fr) minmax(320px, 1.4fr);
    gap:16px;
    box-sizing:border-box;
  }
  .exam-left,
  .exam-right {
    min-width:0;
  }

  /* kartu umum */
  .card,
  .qcard {
    background:#ffffff;
    border-radius:12px;
    box-shadow:0 8px 30px rgba(15,23,42,0.05);
    padding:12px 14px;
    box-sizing:border-box;
  }
  .card + .card {
    margin-top:12px;
  }
  .card-title {
    font-weight:600;
    margin-bottom:8px;
    font-size:0.9rem;
  }
  .card-body {
    font-size:0.9rem;
    color:#111827;
  }

  /* gambar skenario responsif */
  #exMedia img {
    max-width:100%;
    height:auto;
    display:block;
    margin-bottom:8px;
    border-radius:8px;
  }

  /* =========================
     KARTU SOAL
     ========================= */
  .q-head {
    display:flex;
    align-items:flex-start;
    gap:6px;
    margin-bottom:10px;
  }
  .q-no {
    font-weight:700;
    min-width:24px;
  }
  .q-title {
    font-weight:500;
    font-size:0.95rem;
  }
  .q-body {
    margin-bottom:10px;
  }
  .q-options .opt {
    display:flex;
    align-items:flex-start;
    gap:8px;
    padding:6px 8px;
    border-radius:8px;
    border:1px solid #e5e7eb;
    margin-bottom:6px;
    font-size:0.9rem;
  }
  .q-options .opt input[type="radio"] {
    margin-top:3px;
  }

  .legend {
    margin-top:6px;
    font-size:0.8rem;
    color:#4b5563;
  }
  .legend-title {
    font-weight:600;
    margin-bottom:2px;
  }

  .q-foot {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-top:8px;
    flex-wrap:wrap;
  }
  .q-foot .total {
    font-size:0.9rem;
    font-weight:500;
  }
  .q-foot .actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }

  .btn {
    border:none;
    border-radius:8px;
    padding:6px 14px;
    font-size:0.9rem;
    cursor:pointer;
    background:#e5e7eb;
  }
  .btn.primary {
    background:#0EA5A5;
    color:#fff;
  }
  .btn.success {
    background:#22c55e;
    color:#fff;
  }
  .btn.pill {
    border-radius:999px;
  }
  .btn.danger {
    background:#ef4444;
    color:#fff;
  }

  /* =========================
     PAGER NOMOR SOAL
     ========================= */
  .pager.center {
    margin-top:12px;
    text-align:center;
    overflow-x:auto;
    white-space:nowrap;
    padding-bottom:4px;
  }
  .pager.center .page {
    display:inline-block;
    min-width:32px;
    padding:4px 6px;
    margin:0 2px 4px;
    border-radius:999px;
    border:1px solid #d1d5db;
    font-size:0.8rem;
    background:#fff;
  }
  .pager.center .page.active {
    background:#0EA5A5;
    border-color:#0EA5A5;
    color:#fff;
    font-weight:600;
  }

  /* =========================
     GLOBAL PERFORMANCE SCALE
     ========================= */
  .gps-wrap {
    display:flex;
    gap:16px;
    align-items:flex-start;
    margin-top:10px;
    flex-wrap:wrap;                 /* boleh turun ke bawah di layar kecil */
  }
  .gps-title {
    font-weight:700;
    margin-right:8px;
    min-width:auto;                 /* jangan kunci lebar besar */
  }
  #gpsGroup .opt {
    border:none;
    padding:2px 0;
  }
  .gps-group {
    display:flex;
    flex-direction:column;
  }

  /* =========================
     SHEET KONFIRMASI
     ========================= */
  .sheet {
    position:fixed;
    inset:0;
    background:rgba(15,23,42,0.5);
    display:flex;
    align-items:center;
    justify-content:center;
    opacity:0;
    pointer-events:none;
    transition:opacity .18s ease-out;
    z-index:40;
  }
  .sheet.show {
    opacity:1;
    pointer-events:auto;
  }
  .sheet-card.confirm-card {
    background:#fff;
    border-radius:16px;
    padding:16px;
    max-width:420px;
    width:90%;
    box-sizing:border-box;
    text-align:center;
  }
  .confirm-icon {
    width:32px;
    height:32px;
    border-radius:999px;
    background:#fee2e2;
    color:#b91c1c;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 8px;
    font-weight:700;
  }
  .confirm-title {
    font-weight:600;
    margin-bottom:4px;
  }
  .confirm-sub {
    font-size:0.85rem;
    color:#4b5563;
    margin-bottom:8px;
  }
  .confirm-total {
    font-size:0.9rem;
    margin-bottom:12px;
  }
  .sheet-actions.center {
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
  }

  /* =========================
     BREAKPOINT: <= 1024px (tablet)
     ========================= */
  @media (max-width: 1024px) {
    .exam-body {
      grid-template-columns: minmax(0,1fr);
    }
    .exam-right {
      order:1;
    }
    .exam-left {
      order:2;           /* skenario turun ke bawah soal */
    }
  }

  /* =========================
     BREAKPOINT: <= 768px (HP besar)
     ========================= */
  @media (max-width: 768px) {
    .topbar {
      flex-direction:column;
      align-items:flex-start;
    }
    .topbar .top-actions {
      width:100%;
      justify-content:space-between;
    }

    .exam-topbar {
      flex-direction:column;
      align-items:flex-start;
    }
    .exam-timer {
      margin-left:0;
    }

    .exam-body {
      padding:12px;
    }
    .qcard {
      padding:10px 10px;
    }
    .q-title {
      font-size:0.9rem;
    }
    .card-body {
      font-size:0.85rem;
    }

    .gps-wrap {
      flex-direction:column;
    }
    .legend {
      margin-top:4px;
    }
  }

  /* =========================
     BREAKPOINT: <= 480px (HP kecil)
     ========================= */
  @media (max-width: 480px) {
    .exam-brand {
      font-size:0.85rem;
    }
    .exam-user {
      font-size:0.85rem;
    }
    .exam-topbar {
      padding:8px 10px;
    }
    .exam-body {
      padding:10px 8px 16px;
    }
    .q-foot {
      flex-direction:column;
      align-items:flex-start;
    }
    .q-foot .actions {
      width:100%;
      justify-content:flex-start;
    }
    .btn {
      width:auto;
    }
  }
</style>

</head>
<body>
<header class="topbar">
  <div class="brand">
    <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="">
    <span>Ujian Praktek - OSCE</span>
  </div>
  <div class="top-actions">
    <div class="user"><?= esc($os['nama'] ?? 'Pengawas') ?></div>
    <a class="btn ghost" href="<?= site_url('e-osce/logout') ?>">Logout</a>
  </div>
</header>
  <div class="exam-topbar">
    <div class="exam-brand">Ujian Praktek - OSCE</div>
    <div class="exam-user"><?= esc($mhs['nama']) ?> <small>(<?= esc($mhs['nim']) ?>)</small></div>
    <div class="exam-timer">Timer: <span id="exTimer">00:00Min</span></div>
    <a class="icon-btn" href="<?= site_url('e-osce/panel') ?>" aria-label="Kembali">×</a>
  </div>

  <div class="exam-body">
   <aside class="exam-left">
  <div class="card">
  <div class="card-title">Skenario</div>
  <div class="card-body">
    <div id="exMedia" class="media-list"></div>  <!-- gambar dari ujian_praktek.file -->
    <?= $skenario ?>
  </div>
</div>

  <div class="card">
    <div class="card-title">Tugas Kandidat</div>
    <div class="card-body"><?= $tugas_k ?></div>
  </div>
</aside>


    <section class="exam-right">
      <div class="qcard">
        <div class="q-head">
          <div class="q-no" id="exNo">1.</div>
          <div class="q-title" id="exTeks">...</div>
        </div>
        <div class="q-body">
          <div id="exOptions" class="q-options"></div>
          <!-- Global Performance Scale -->
  <div class="gps-wrap">
    <div class="gps-title">Global performance Scale</div>
    <div id="gpsGroup" class="gps-group"></div>
    <div class="legend">
      <div class="legend-title">Ket:</div>
      <div class="legend-body">0: Salah<br>1: Borderline<br>2: Membenarkan benar</div>
    </div>
  </div>
          <div class="legend">
            <div class="legend-title">Ket:</div>
            <div id="exLegend" class="legend-body"></div>
          </div>
        </div>
        <div class="q-foot">
          <div class="total">Total Skor: <span id="exTotal">0</span></div>
          <div class="actions">
            <button class="btn" id="exPrev">Sebelumnya</button>
            <button class="btn primary" id="exNext">Selanjutnya</button>
          </div>
        </div>
      </div>

      <div class="pager center" id="exPager"></div>

      <form id="exForm" class="d-none" method="post" action="<?= site_url('e-osce/ujian/submit/'.$mhs['id']) ?>">
        <input type="hidden" name="<?= esc($csrf_name) ?>" value="<?= esc($csrf_tok) ?>">
      </form>
    </section>
  </div>

  <!-- POPUP KONFIRMASI SELESAI -->
<div id="confirmFinish" class="sheet">
  <div class="sheet-card confirm-card">
    <div class="confirm-icon">!</div>
    <div class="confirm-title">Konfirmasi</div>
    <div class="confirm-sub">Selesaikan penilaian untuk kandidat ini? Anda tidak bisa mengubah penilaian lagi sesudahnya.</div>
    <div class="confirm-total">Total skor: <b><span id="cfTotal">0</span></b></div>
    <div class="sheet-actions center">
      <button class="btn pill danger" id="cfCancel">Batalkan</button>
      <button class="btn pill primary" id="cfOk">Ya Sudah Selesai!</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
/* ===== from PHP ===== */
const READ_ONLY  = <?= !empty($readOnly) ? 'true' : 'false' ?>;
const nilaiInit  = <?= json_encode($nilaiInit ?? [], JSON_UNESCAPED_UNICODE) ?>;
const savedTime  = <?= json_encode($savedTime ?? '00:00:00') ?>;
const items      = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>;
const gpsInit   = <?= json_encode($gpsInit ?? null) ?>;
const GPS_OPTIONS = [
  {v:0, t:'Tidak Lulus'},
  {v:1, t:'Borderline'},
  {v:2, t:'Lulus'}
];


let gps   = (gpsInit === null ? null : Number(gpsInit)); // <— nilai GPS awal     // 0/1/2 atau null

/* <- gunakan null coalescing supaya aman */
const mediaSoal  = <?= json_encode($mediaSoal ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

const SUBMIT_URL = <?= json_encode(site_url('e-osce/ujian/submit/'.$mhs['id'])) ?>;
const CSRF_NAME  = <?= json_encode($csrf_name) ?>;

/* ===== state ===== */
let nilai = {...nilaiInit};      // preselect dari server
let page  = 1;

/* ===== helpers ===== */
const qs = (s,c=document)=>c.querySelector(s);
function decodeEntities(str){
  if (!str) return '';
  const ta = document.createElement('textarea');
  ta.innerHTML = str; let v = ta.value;
  ta.innerHTML = v; return ta.value; // handle double-escaped
}
function formatHHMMSStoMMSSMin(hms){
  const [h,m,s] = (hms||'00:00:00').split(':').map(x=>parseInt(x||0,10));
  return `${String(h*60+m).padStart(2,'0')}:${String(s).padStart(2,'0')}Min`;
}

/* ===== media skenario dari ujian_praktek.file ===== */
function renderMediaSoal(){
  const $wrap = $('#exMedia'); $wrap.empty();
  (mediaSoal || []).forEach(url => $('<img>', {src:url, alt:'Lampiran'}).appendTo($wrap));
}

/* ===== pager ===== */
function renderPager(){
  const p = qs('#exPager'); p.innerHTML = '';
  // pager untuk aspek
  items.forEach((it,i)=>{
    const b = document.createElement('button');
    b.className   = 'page' + (page===i+1 ? ' active' : '');
    b.textContent = i+1;
    b.addEventListener('click', e => { e.preventDefault(); page=i+1; renderPage(); renderPager(); });
    p.appendChild(b);
  });
  // pager untuk GPS (halaman terakhir)
  const last = document.createElement('button');
  last.className = 'page gps' + (page===items.length+1 ? ' active' : '');
  last.textContent = 'GPS';
  last.addEventListener('click', e => { e.preventDefault(); page = items.length+1; renderPage(); renderPager(); });
  p.appendChild(last);
}


/* ===== total skor ===== */
function renderTotal(){
  let t=0; for(const k in nilai) t += (nilai[k]||0);
  qs('#exTotal').textContent = t;
}

/* ===== satu halaman aspek ===== */
function renderPage(){
  // Halaman GPS?
  if (page === items.length + 1){
    qs('#exNo').textContent   = '';                      // tidak pakai nomor
    qs('#exTeks').textContent = '';                      // kosongkan
    qs('#exTeks').classList.add('gps');
    qs('#exTeks').textContent = 'Global Performance Scale';

    // Legend ala desain
    qs('#exLegend').innerHTML = `
      <div class="gps-panel">
        <div><b>Ket:</b></div>
        <div>0: Salah</div>
        <div>1: Borderline</div>
        <div>2: Menyebutkan benar</div>
      </div>
    `;

    // render 3 pilihan GPS
    const box = qs('#exOptions'); box.innerHTML = '';
    GPS_OPTIONS.forEach(o=>{
      const id  = `gps_${o.v}`;
      const lab = document.createElement('label');
      lab.className = 'opt';

      const inp = document.createElement('input');
      inp.type = 'radio'; inp.name = 'gps'; inp.id = id;
      inp.value = o.v; inp.disabled = READ_ONLY;
      if (gps !== null && Number(gps) === Number(o.v)) inp.checked = true;

      const mark = document.createElement('span'); mark.className = 'opt-mark';
      const txt  = document.createElement('span'); txt.className  = 'opt-text';
      txt.textContent = o.t;

      if (!READ_ONLY){
        inp.addEventListener('change', ()=> { gps = Number(o.v); });
      }

      lab.append(inp, mark, txt);
      box.appendChild(lab);
    });

    // tombol
    qs('#exPrev').disabled = (items.length === 0); // masih bisa kembali ke aspek
    const next = qs('#exNext');
    next.textContent = 'Selesai';
    next.classList.add('success');
    if (READ_ONLY) next.style.display = 'none';

    return;
  }

  // ======= Halaman Aspek (seperti semula) =======
  const it = items[page-1] || null;
  if(!it) return;

  qs('#exTeks').classList.remove('gps');
  qs('#exNo').textContent   = (it.no||1) + '.';
  qs('#exTeks').innerHTML   = decodeEntities(it.teks || '');
  qs('#exLegend').innerHTML = decodeEntities(it.legend || '');

  const box = qs('#exOptions'); box.innerHTML = '';
  (it.opsi||[]).forEach(o=>{
    const id   = `opt_${it.id}_${o.v}`;
    const lab  = document.createElement('label');
    lab.className = 'opt';

    const inp  = document.createElement('input');
    inp.type   = 'radio';
    inp.name   = `a_${it.id}`;
    inp.id     = id;
    inp.value  = o.v;
    inp.disabled = READ_ONLY;
    if (Number(nilai[it.id]) === Number(o.v)) inp.checked = true;

    const mark = document.createElement('span'); mark.className = 'opt-mark';
    const txt  = document.createElement('span'); txt.className  = 'opt-text';
    txt.textContent = o.v+' '+decodeEntities(o.t);

    if (!READ_ONLY){
      inp.addEventListener('change', ()=>{
        nilai[it.id] = parseInt(o.v,10);
        renderTotal();
      });
    }

    lab.append(inp, mark, txt);
    box.appendChild(lab);
  });

  qs('#exPrev').disabled = (page<=1);
  const next = qs('#exNext');
  next.textContent = (page>=items.length) ? 'GPS' : 'Selanjutnya';
  next.classList.toggle('success', page>=items.length);
  if (READ_ONLY) next.style.display = 'none';
}

/* ===== timer ===== */
if (READ_ONLY){
  // tampilkan waktu tersimpan, jangan jalan
  $('#exTimer').text(formatHHMMSStoMMSSMin(savedTime));
} else {
  // hitung MAJU dari 00:00
  $('#exTimer').text('00:00Min');
  window.__elapsedSec = 0;
  window.__osceTimer = setInterval(()=>{
    window.__elapsedSec++;
    const h = Math.floor(window.__elapsedSec / 3600);
    const m = Math.floor((window.__elapsedSec % 3600) / 60);
    const s = window.__elapsedSec % 60;
    $('#exTimer').text(`${String(h*60+m).padStart(2,'0')}:${String(s).padStart(2,'0')}Min`);
  }, 1000);
}

/* ===== modal selesai (tetap ada, tapi tak dipakai saat read-only) ===== */
function openFinish(){ $('#cfTotal').text($('#exTotal').text()); $('#confirmFinish').addClass('show'); }
function closeFinish(){ $('#confirmFinish').removeClass('show'); }

/* ===== actions ===== */
$('#exPrev').on('click', function(){ page = Math.max(1, page-1); renderPage(); renderPager(); });
$('#exNext').on('click', ()=>{
  if (READ_ONLY) return;

  // kalau kita di halaman GPS
  if (page === items.length + 1){
    if (gps === null || isNaN(gps)){
      alert('Pilih Global Performance Scale terlebih dahulu.');
      return;
    }
    openFinish(); // modal konfirmasi
    return;
  }

  // kalau masih di aspek
  if(page>=items.length){ 
    page = items.length + 1;      // pindah ke GPS
  } else {
    page++;
  }
  renderPage(); renderPager();
});

$('#cfCancel').on('click', closeFinish);
// dari PHP:




// render radio GPS
function renderGPS(){
  const $wrap = $('#gpsGroup').empty();
  const opts = [
    {v:0, t:'Tidak Lulus'},
    {v:1, t:'Borderline'},
    {v:2, t:'Lulus'},
  ];
  opts.forEach(o=>{
    const id = 'gps_'+o.v;
    const $lab = $('<label class="opt" for="'+id+'"></label>');
    const $inp = $('<input>',{
      type:'radio', id, name:'gps', value:o.v, disabled: READ_ONLY
    });
    if (gps !== null && Number(gps) === o.v) $inp.prop('checked', true);
    if (!READ_ONLY) $inp.on('change', ()=>{ gps = o.v; });
    $lab.append($inp, $('<span class="opt-text">').text(o.t));
    $wrap.append($lab);
  });
}

/* ===== submit (hanya saat bukan read-only) ===== */
async function submitExam(){
  // 1) siapkan form-data (URL-encoded)
  const fd = new URLSearchParams();

  // nilai per-aspek
  for (const k in nilai) {
    fd.append(`nilai[${k}]`, nilai[k]);
  }

  // waktu
  const elapsed = window.__elapsedSec || 0;
  const h = Math.floor(elapsed / 3600);
  const m = Math.floor((elapsed % 3600) / 60);
  const s = elapsed % 60;
  const hh = String(h).padStart(2,'0');
  const mm = String(m).padStart(2,'0');
  const ss = String(s).padStart(2,'0');
  fd.append('waktu', `${hh}:${mm}:${ss}`);
  fd.append('durasi_detik', elapsed);

  // GPS (0/1/2) — kalau ada
  if (gps !== null && !isNaN(gps)) {
    fd.append('gps', gps);
  }

  // CSRF
  const csrfSel = (window.CSS && CSS.escape)
    ? `input[name="${CSS.escape(CSRF_NAME)}"]`
    : `input[name="${CSRF_NAME}"]`;
  const csrfInput = document.querySelector(csrfSel);
  if (csrfInput) fd.append(CSRF_NAME, csrfInput.value);

  // 2) kirim
  try{
    const res = await fetch(SUBMIT_URL, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
      body: fd.toString()
    });
    const j = await res.json();

    if (j.status === 'ok') {
      if (window.__osceTimer) clearInterval(window.__osceTimer);
      alert(`Tersimpan.\nTotal skor: ${j.total}\nWaktu: ${j.waktu}`);
      window.location.href = <?= json_encode(site_url('e-osce/panel')) ?>;
    } else {
      alert(j.message || 'Gagal menyimpan');
    }
  } catch (e){
    alert('Gagal menyimpan (jaringan). Coba lagi.');
    console.error(e);
  }
}

$('#cfOk').on('click', async function(){ if (!READ_ONLY){ closeFinish(); await submitExam(); } });

/* ===== init ===== */
$(function(){
  renderMediaSoal();
  renderPage();
  renderPager();
  renderTotal();
    if (READ_ONLY) { $('#exNext, #cfOk').hide(); }
});
</script>
