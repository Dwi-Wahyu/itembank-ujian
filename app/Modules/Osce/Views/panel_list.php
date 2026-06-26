<?php $os = $os ?? []; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#0EA5A5">
  <title>Panel Pengawas OSCE</title>
  <link rel="manifest" href="<?= base_url('osce/manifest.json') ?>">
  <link rel="icon" href="<?= base_url('assets/icons/osce-192.png') ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="apple-touch-icon" href="<?= base_url('assets/icons/osce-192.png') ?>">
  <?= csrf_meta() ?>
  <link rel="stylesheet" href="<?= base_url('osce/osce-panel.css') ?>">
  <style>/* fallback minimal if CSS not loaded */html{background:#f6f8fa}
    .c-gps { width: 130px; text-align:center; }

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

<main class="container">
  <div class="title-wrap">
    <h1 class="page-title"><?= esc($os['osce_nama'] ?? 'OSCE') ?></h1>
    <div class="sub"><?= esc(($os['osce_kode'] ?? '-') . ' • ' . ($os['station_nama'] ?? 'Station')) ?></div>
  </div>

  <div class="toolbar">
    <div class="search">
      <svg viewBox="0 0 24 24" width="18" height="18"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input id="q" type="search" placeholder="Cari Mahasiswa, masukkan nama / NIM">
    </div>
    <div class="filters">
      <button id="btnFilter" class="btn light">Filter by</button>
      <select id="sort" class="select">
        <option value="nama_asc">Sort by</option>
        <option value="nama_asc">Nama A-Z</option>
        <option value="nama_desc">Nama Z-A</option>
        <option value="nim_asc">NIM ↑</option>
        <option value="nim_desc">NIM ↓</option>
      </select>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
    <thead>
  <tr>
    <th class="c-check"><input type="checkbox" id="chkAll"></th>
    <th class="c-no">No</th>
    <th class="c-nim">NIM</th>
    <th class="c-nama">Nama</th>
    <th class="c-status">Status</th>
    <th class="c-nilai">Nilai</th>
    <th class="c-gps">GPS</th> <!-- ⬅️ baru -->
    <th class="c-aksi">Aksi</th>
  </tr>
</thead>

     <tbody id="rows">
  <tr><td colspan="8" class="empty">Memuat data…</td></tr>
</tbody>

    </table>
  </div>

  <div class="footerbar">
    <div class="left">
      <span id="summary">Menampilkan 0 dari 0 data</span>
    </div>
    <div class="pager">
      <button class="btn ghost" id="prev">‹</button>
      <div id="pages" class="pages"></div>
      <button class="btn ghost" id="next">›</button>
    </div>
    <div class="right">
      <select id="per" class="select">
        <option>5</option><option>10</option><option>25</option><option>50</option>
      </select>
      <span class="muted">kolom</span>
    </div>
  </div>
</main>
<!-- MODAL INFORMASI MULAI UJIAN -->
<div id="infoModal" class="modal">
  <div class="modal-dialog">
    <div class="modal-head">
      <div class="title">INFORMASI</div>
      <button class="icon-btn" id="mClose" aria-label="Tutup">×</button>
    </div>
    <div class="modal-body">
      <div class="ident">
        <div class="ident-name" id="mNama">-</div>
        <div class="ident-nim" id="mNim">-</div>
      </div>

      <div class="box">
        <div class="box-title">Informasi Ujian</div>
        <div class="kv">
          <div class="k">Nama Ujian</div><div class="v" id="mUjian">-</div>
          <div class="k">Departemen</div><div class="v" id="mDep">-</div>
          <div class="k">Blok</div><div class="v" id="mBlok">-</div>
          <div class="k">Tanggal</div><div class="v" id="mTanggal">-</div>
          <div class="k">Waktu</div><div class="v" id="mWaktu">-</div>
        </div>
      </div>

      <div class="box">
        <div class="box-title">Tugas Penguji</div>
        <div id="mTugasP" class="content-list"></div>
      </div>

      <div class="box">
        <div class="box-title">Tugas Kandidat</div>
        <div id="mTugasK" class="content-list"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn primary" id="mStartBtn">Mulai Ujian</button>
    </div>
  </div>
</div>

<script>
const API = '<?= site_url('e-osce/api/peserta') ?>';
const START_URL = '<?= site_url('e-osce/ujian/start') ?>';
const DETAIL_URL = '<?= site_url('e-osce/nilai') ?>';
const CSRF_NAME = '<?= csrf_token() ?>';
const INFO_URL  = '<?= site_url('e-osce/api/info') ?>';   // API untuk isi modal
const EXAM_PAGE = '<?= site_url('e-osce/ujian') ?>';      // Halaman ujian
function gpsCell(v){
  if (v === 0 || v === '0') return '<span class="badge red">Tidak Lulus</span>';
  if (v === 1 || v === '1') return '<span class="badge">Borderline</span>'; // netral
  if (v === 2 || v === '2') return '<span class="badge green">Lulus</span>';
  return '<span class="muted">-</span>'; // belum ada
}


let state = { page: 1, per: 5, q: '', sort: 'nama_asc', total: 0 };

const $ = (q, ctx=document) => ctx.querySelector(q);
function el(tag, cls){ const e=document.createElement(tag); if(cls) e.className=cls; return e; }
function setCsrf(t){
  const m = document.querySelector('meta[name="csrf-token"]');
  if (m) m.setAttribute('content', t);
}
async function load(){
  const url = new URL(API, location.origin);
  url.searchParams.set('page', state.page);
  url.searchParams.set('per', state.per);
  url.searchParams.set('q', state.q);
  url.searchParams.set('sort', state.sort);
  $('#rows').innerHTML = '<tr><td colspan="7" class="empty">Memuat data…</td></tr>';

  const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
  if(!res.ok){ $('#rows').innerHTML = '<tr><td colspan="7" class="empty">Gagal memuat.</td></tr>'; return; }
  const data = await res.json();
  if(data.csrf_token) setCsrf(data.csrf_token);
  state.total = data.total || 0;

  renderRows(data.data || []);
  renderPager();
}

function renderRows(items){
  const tbody = $('#rows');
  tbody.innerHTML = '';
  if(items.length === 0){
    tbody.innerHTML = '<tr><td colspan="8" class="empty">Tidak ada data.</td></tr>';
    $('#summary').textContent = 'Menampilkan 0 dari '+state.total+' data';
    return;
  }

  const startNo = (state.page-1)*state.per;
  items.forEach((r, i)=>{
    const tr = el('tr');
    tr.innerHTML = `
      <td class="c-check"><input type="checkbox" class="chk"></td>
      <td class="c-no">${startNo + i + 1}.</td>
      <td class="c-nim">${escapeHtml(r.nim || '-')}</td>
      <td class="c-nama">${escapeHtml(r.nama || '-')}</td>
      <td class="c-status">${badgeStatus(r.status)}</td>
      <td class="c-nilai">${nilaiCell(r.global_skor)}</td>
    <td class="c-gps">${gpsCell(r.gps)}</td>
       <!-- ⬅️ baru -->
      <td class="c-aksi">${aksiCell(r)}</td>`;
    tbody.appendChild(tr);
  });

  const end = Math.min(state.page*state.per, state.total);
  const start = state.total ? (startNo+1) : 0;
  $('#summary').textContent = `Menampilkan ${start} dari ${state.total} data`;
}


function badgeStatus(st){
  if(st==='sudah') return '<span class="badge green">Sudah Ujian</span>';
  return '<span class="badge red">Belum Ujian</span>';
}
function nilaiCell(skor){
  if(skor===null || skor===undefined || skor==='') return '<span class="muted">Belum ada Nilai</span>';
  return `<strong>${Number(skor)}</strong>`;
}
function aksiCell(r){
  if(r.status==='belum'){
    return `<button class="btn primary btn-start" data-id="${r.id_mahasiswa}">Mulai Ujian</button>`;
  }
  return `<a class="btn light" href="${DETAIL_URL}/${r.id_mahasiswa}">Detail</a>`;
}

function renderPager(){
  const pages = Math.max(1, Math.ceil(state.total / state.per));
  const cont = $('#pages'); cont.innerHTML = '';
  const mk = (n) => {
    const b = el('button', 'page'+(n===state.page?' active':'')); b.textContent=n;
    b.addEventListener('click', ()=>{ state.page=n; load(); });
    return b;
  };
  for(let i=1;i<=pages;i++){
    if (i===1 || i===pages || Math.abs(i-state.page)<=2){
      cont.appendChild(mk(i));
    } else if (Math.abs(i-state.page)===3){
      cont.appendChild(el('span','dots')).textContent='…';
    }
  }
  $('#prev').disabled = (state.page<=1);
  $('#next').disabled = (state.page>=pages);
}

function escapeHtml(s){ return (s??'').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m])); }

document.addEventListener('click', async (e)=>{
  if(e.target.matches('#prev')){ state.page=Math.max(1, state.page-1); load(); }
  if(e.target.matches('#next')){ state.page=state.page+1; load(); }

});

$('#q').addEventListener('input', debounce(()=>{ state.q = $('#q').value.trim(); state.page=1; load(); }, 300));
$('#sort').addEventListener('change', ()=>{ state.sort = $('#sort').value; state.page=1; load(); });
$('#per').addEventListener('change', ()=>{ state.per = parseInt($('#per').value||5,10); state.page=1; load(); });

function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn.apply(this,a), ms); }; }

// PWA: register service worker
if('serviceWorker' in navigator){
  window.addEventListener('load', ()=> {
    navigator.serviceWorker.register('<?= base_url('osce/sw.js') ?>');
  });
}


let currentMhsId = null;

function openModal(){
  $('#infoModal').classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeModal(){
  $('#infoModal').classList.remove('show');
  document.body.style.overflow = '';
}
// klik "Mulai Ujian" di tabel -> fetch info -> isi modal -> tampilkan
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btn-start');
  if (!btn) return;

  e.preventDefault();
  currentMhsId = btn.getAttribute('data-id');

  try {
    const res = await fetch(`${INFO_URL}/${currentMhsId}`, { headers: { 'Accept':'application/json' }});
    if (!res.ok) throw new Error('Gagal memuat informasi');
    const j = await res.json();
    if (j.csrf_token) setCsrf(j.csrf_token);
    if (j.status !== 'ok') throw new Error(j.message || 'Gagal memuat informasi');

    const d = j.data || {};
    $('#mNama').textContent    = d.nama || '-';
    $('#mNim').textContent     = d.nim || '-';
    $('#mUjian').textContent   = d.namaUjian || '-';
    $('#mDep').textContent     = d.departemen || '-';
    $('#mBlok').textContent    = d.blok || '-';
    $('#mTanggal').textContent = d.tanggal || '-';
    $('#mWaktu').textContent   = d.waktu || '-';
    $('#mTugasP').innerHTML    = (d.tugas_p && d.tugas_p.trim()) ? d.tugas_p : '<p>-</p>';
    $('#mTugasK').innerHTML    = (d.tugas_k && d.tugas_k.trim()) ? d.tugas_k : '<p>-</p>';

    openModal();
  } catch (err) {
    (window.Swal?.fire) ? Swal.fire('Gagal', err.message, 'error') : alert(err.message);
  }
});

// 2) Tutup modal
$('#mClose').addEventListener('click', closeModal);

// 3) Tombol "Mulai Ujian" dalam modal -> redirect ke halaman ujian
$('#mStartBtn').addEventListener('click', () => {
  if (!currentMhsId) return;
  const b = $('#mStartBtn');
  b.disabled = true;
  b.textContent = 'Membuka...';
  closeModal();
  window.location.href = `${EXAM_PAGE}/${currentMhsId}`;
});
// tombol pada modal
// document.getElementById('mClose').addEventListener('click', closeModal);
// document.getElementById('mStartBtn').addEventListener('click', async ()=>{
//   if(!currentMhsId) return;
//   const btn = document.getElementById('mStartBtn');
//   btn.disabled = true; btn.textContent = 'Memulai...';
//   try{
//     const res = await fetch(`${START_URL}/${currentMhsId}`, {
//       method:'POST',
//       headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
//       body: new URLSearchParams({ [CSRF_NAME]: document.querySelector('meta[name="csrf-token"]')?.content || '' })
//     });
//     const j = await res.json();
//     if(j.csrf_token) setCsrf(j.csrf_token);
//     if(j.status!=='ok') throw new Error(j.message || 'Gagal memulai');
//     closeModal();
//     alert(j.message || 'Ujian dimulai');
//     // opsional: refresh list
//     load();
//   }catch(err){
//     alert(err.message || 'Gagal memulai');
//   }finally{
//     btn.disabled = false; btn.textContent = 'Mulai Ujian';
//   }
// });
load();
</script>
</body>
</html>
