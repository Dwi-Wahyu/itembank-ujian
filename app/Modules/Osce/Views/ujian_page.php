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
  .gps-panel { background:#10b9811a; border-radius:12px; padding:12px 14px; color:#065f46; margin-bottom:12px; }
  .gps-panel b { color:#065f46 }
  
  body {
    margin: 0;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background:#f3f4f6;
  }

  /* Topbar admin (green logo + pengawas) - NOT fixed as requested */
  .topbar {
    position: static !important;
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

  /* Topbar ujian (mahasiswa + timer) - NOT fixed as requested */
  .exam-topbar {
    position: static !important;
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

  /* LAYOUT KONTEN (SIDEBAR + SOAL) */
  .exam-body {
    max-width:1200px;
    margin:0 auto;
    padding:16px;
    display:grid;
    grid-template-columns:minmax(260px, 0.9fr) minmax(320px, 1.4fr);
    gap:16px;
    box-sizing:border-box;
    align-items: start;
  }

  /* Sticky left sidebar without internal scrollbar */
  .exam-left {
    min-width:0;
    position: sticky;
    top: 16px;
  }
  
  .exam-right {
    min-width:0;
  }

  /* kartu umum */
  .card,
  .qcard {
    background:#ffffff;
    border-radius:12px;
    box-shadow:0 4px 16px rgba(15,23,42,0.05);
    padding:16px;
    box-sizing:border-box;
    margin-bottom:16px;
    scroll-margin-top: 20px;
    transition: all 0.25s ease;
  }
  .card-title {
    font-weight:600;
    margin-bottom:8px;
    font-size:0.95rem;
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

  /* KARTU SOAL & ASPEK - Baseline alignment for number and title */
  .q-head {
    display:flex;
    align-items:baseline;
    gap:8px;
    margin-bottom:12px;
  }
  .q-no {
    font-weight:700;
    color: #0EA5A5;
    font-size: 0.95rem;
    line-height: 1.4;
    flex-shrink: 0;
  }
  .q-title {
    font-weight: normal; /* Removed auto bold */
    font-size: 0.95rem;
    line-height: 1.4;
    flex: 1;
  }
  .q-title p {
    margin-top: 0;
    margin-bottom: 0.5em;
  }
  .q-title p:last-child {
    margin-bottom: 0;
  }

  .q-body {
    margin-bottom:10px;
  }
  .q-options .opt {
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:10px 12px;
    border-radius:8px;
    border:1px solid #e5e7eb;
    margin-bottom:8px;
    font-size:0.9rem;
    cursor: pointer;
    background: #ffffff;
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .q-options .opt:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }

  /* Highlight Option When Selected */
  .q-options .opt.selected,
  .q-options .opt:has(input:checked) {
    background: #e0f2fe !important;
    border-color: #0EA5A5 !important;
    font-weight: 600;
    color: #0f766e;
    box-shadow: 0 0 0 1px #0EA5A5;
  }

  .q-options .opt input[type="radio"] {
    margin-top:3px;
    cursor: pointer;
    accent-color: #0EA5A5;
  }

  /* Collapsible Panduan Skor */
  .legend-collapsible {
    margin-top:12px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size:0.85rem;
    color:#4b5563;
    overflow: hidden;
  }
  .legend-collapsible summary {
    padding: 8px 12px;
    font-weight:600;
    color: #334155;
    cursor: pointer;
    user-select: none;
    background: #f1f5f9;
    transition: background 0.15s ease;
  }
  .legend-collapsible summary:hover {
    background: #e2e8f0;
  }
  .legend-collapsible .legend-body {
    padding: 10px 12px;
    line-height: 1.4;
  }

  /* Status Summary Header */
  .summary-bar {
    background: #ffffff;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 2px 10px rgba(15,23,42,0.04);
    flex-wrap: wrap;
    border: 1px solid #e2e8f0;
  }
  .summary-info {
    display: flex;
    gap: 16px;
    align-items: center;
    font-size: 0.9rem;
    width: 100%;
    justify-content: space-between;
  }
  .summary-badge {
    font-weight: 600;
    background: #f1f5f9;
    padding: 6px 12px;
    border-radius: 999px;
    color: #334155;
  }
  .summary-total {
    font-size: 1rem;
    font-weight: 700;
    color: #0EA5A5;
  }

  /* Unscored Warning Visuals */
  .unscored-warning {
    border: 2px solid #ef4444 !important;
    background-color: #fef2f2 !important;
    box-shadow: 0 0 14px rgba(239, 68, 68, 0.25) !important;
    animation: shake 0.4s ease-in-out;
  }
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-4px); }
    40%, 80% { transform: translateX(4px); }
  }

  .unscored-badge {
    display: inline-block;
    background: #ef4444;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    margin-left: 8px;
  }

  /* Custom Toast Notification */
  .toast-notice {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #ef4444;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 10px 25px rgba(239,68,68,0.3);
    z-index: 9999;
    display: none;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
  }
  .toast-notice.success {
    background: #10b981 !important;
    box-shadow: 0 10px 25px rgba(16,185,129,0.3) !important;
  }

  .btn {
    border:none;
    border-radius:8px;
    padding:8px 18px;
    font-size:0.9rem;
    font-weight: 600;
    cursor:pointer;
    background:#e5e7eb;
    transition: background 0.15s ease;
  }
  .btn.primary {
    background:#0EA5A5;
    color:#fff;
  }
  .btn.primary:hover {
    background:#0d9494;
  }
  .btn.success {
    background:#22c55e;
    color:#fff;
  }
  .btn.success:hover {
    background:#16a34a;
  }
  .btn.pill {
    border-radius:999px;
  }
  .btn.danger {
    background:#ef4444;
    color:#fff;
  }

  /* SHEET KONFIRMASI */
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
    padding:20px;
    max-width:420px;
    width:90%;
    box-sizing:border-box;
    text-align:center;
  }
  .confirm-icon {
    width:36px;
    height:36px;
    border-radius:999px;
    background:#fee2e2;
    color:#b91c1c;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 10px;
    font-weight:700;
    font-size: 1.2rem;
  }
  .confirm-title {
    font-weight:600;
    font-size: 1.1rem;
    margin-bottom:6px;
  }
  .confirm-sub {
    font-size:0.875rem;
    color:#4b5563;
    margin-bottom:12px;
  }
  .confirm-total {
    font-size:1rem;
    margin-bottom:16px;
  }
  .sheet-actions.center {
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
  }

  /* BREAKPOINTS */
  @media (max-width: 1024px) {
    .exam-body {
      grid-template-columns: minmax(0,1fr);
    }
    .exam-left {
      position: static;
      order: 2;
    }
    .exam-right {
      order: 1;
    }
  }

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
  }
</style>

</head>
<body>

<!-- Notification Toast -->
<div id="toastNotice" class="toast-notice"></div>

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
    <!-- Sticky left sidebar for Skenario & Tugas -->
    <aside class="exam-left">
      <div class="card">
        <div class="card-title">Skenario</div>
        <div class="card-body">
          <div id="exMedia" class="media-list"></div>
          <?= $skenario ?>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Tugas Kandidat</div>
        <div class="card-body"><?= $tugas_k ?></div>
      </div>
    </aside>

    <section class="exam-right">
      <!-- Ringkasan Status Penilaian -->
      <div class="summary-bar">
        <div class="summary-info">
          <div class="summary-badge">Terisi: <span id="scoredCount">0</span>/<span id="totalAspectsCount">0</span></div>
          <div class="summary-total">Total Skor: <span id="exTotal">0</span></div>
        </div>
      </div>

      <!-- Wadah Semua Aspek -->
      <div id="aspectsContainer"></div>

      <!-- Wadah Global Performance Scale (GPS) -->
      <div class="qcard" id="gps_card">
        <div class="q-head">
          <div class="q-title" style="font-weight:700; color:#0EA5A5;">Global Performance Scale (GPS)</div>
        </div>
        <div class="q-body">
          <div class="gps-panel">
            <div><b>Keterangan:</b></div>
            <div>0: Tidak Lulus</div>
            <div>1: Borderline</div>
            <div>2: Lulus</div>
          </div>

          <div id="gpsOptions" class="q-options"></div>

          <div style="margin-top:16px;">
            <div style="font-weight:700;margin-bottom:6px;color:#111827;">Keterangan / Catatan Performa Mahasiswa:</div>
            <textarea id="exKeterangan" rows="3" placeholder="Masukkan deskripsi performa mahasiswa..." style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:0.9rem;" <?= !empty($readOnly) ? 'disabled' : '' ?>><?= esc($ketInit ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Single Submit Button at the bottom of the evaluation form -->
      <?php if (empty($readOnly)): ?>
        <div style="text-align: right; margin-top: 24px; margin-bottom: 40px;">
          <button class="btn success" style="padding: 12px 28px; font-size: 1.05rem;" onclick="validateAndSubmit()">Simpan Penilaian</button>
        </div>
      <?php endif; ?>

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
const gpsInit    = <?= json_encode($gpsInit ?? null) ?>;
const ketInit    = <?= json_encode($ketInit ?? '') ?>;
const GPS_OPTIONS = [
  {v:0, t:'Tidak Lulus'},
  {v:1, t:'Borderline'},
  {v:2, t:'Lulus'}
];

let gps = (gpsInit === null ? null : Number(gpsInit));
let keterangan = ketInit || '';
const mediaSoal = <?= json_encode($mediaSoal ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

const SUBMIT_URL = <?= json_encode(site_url('e-osce/ujian/submit/'.$mhs['id'])) ?>;
const CSRF_NAME  = <?= json_encode($csrf_name) ?>;

/* ===== state ===== */
let nilai = {...nilaiInit};

/* ===== helpers ===== */
function decodeEntities(str){
  if (!str) return '';
  const ta = document.createElement('textarea');
  ta.innerHTML = str; let v = ta.value;
  ta.innerHTML = v; return ta.value;
}
function formatHHMMSStoMMSSMin(hms){
  const [h,m,s] = (hms||'00:00:00').split(':').map(x=>parseInt(x||0,10));
  return `${String(h*60+m).padStart(2,'0')}:${String(s).padStart(2,'0')}Min`;
}

function showToast(msg, type = 'error'){
  const $t = $('#toastNotice');
  $t.removeClass('success');
  if (type === 'success') {
    $t.addClass('success');
    $t.html(`<span style="font-size:1.15rem;line-height:1;">✓</span> <span>${msg}</span>`);
  } else {
    $t.html(`<span>${msg}</span>`);
  }
  $t.stop(true, true).css('display', 'inline-flex').hide().fadeIn(200).delay(3500).fadeOut(400);
}

function renderMediaSoal(){
  const $wrap = $('#exMedia'); $wrap.empty();
  (mediaSoal || []).forEach(url => $('<img>', {src:url, alt:'Lampiran'}).appendTo($wrap));
}

/* ===== update progress & total score ===== */
function updateSummary(){
  let t = 0;
  let filledCount = 0;
  
  items.forEach(it => {
    if (nilai[it.id] !== undefined && nilai[it.id] !== null && !isNaN(nilai[it.id])) {
      t += parseInt(nilai[it.id], 10);
      filledCount++;
    }
  });

  const totalReq = items.length + 1; // items + GPS
  const currentFilled = filledCount + (gps !== null && !isNaN(gps) ? 1 : 0);

  $('#exTotal').text(t);
  $('#scoredCount').text(currentFilled);
  $('#totalAspectsCount').text(totalReq);
}

/* ===== render all aspect cards directly ===== */
function renderAllAspects(){
  const $wrap = $('#aspectsContainer');
  $wrap.empty();

  items.forEach((it, idx) => {
    const cardId = `aspect_card_${it.id}`;
    
    const $card = $('<div>', {
      class: 'qcard aspect-card',
      id: cardId
    });

    const $head = $('<div>', { class: 'q-head' });
    $head.append($('<div>', { class: 'q-no' }).text((it.no || idx + 1) + '.'));
    $head.append($('<div>', { class: 'q-title' }).html(decodeEntities(it.teks || '')));

    const $body = $('<div>', { class: 'q-body' });
    const $opts = $('<div>', { class: 'q-options' });

    (it.opsi || []).forEach(o => {
      const optId = `opt_${it.id}_${o.v}`;
      const isChecked = (Number(nilai[it.id]) === Number(o.v));
      const $lab  = $('<label>', { class: 'opt' + (isChecked ? ' selected' : ''), for: optId });
      const $inp  = $('<input>', {
        type: 'radio',
        name: `a_${it.id}`,
        id: optId,
        value: o.v,
        disabled: READ_ONLY
      });

      if (isChecked) {
        $inp.prop('checked', true);
      }

      if (!READ_ONLY) {
        $inp.on('change', function() {
          nilai[it.id] = parseInt(o.v, 10);
          $card.removeClass('unscored-warning');
          $card.find('.unscored-badge').remove();
          $opts.find('.opt').removeClass('selected');
          $lab.addClass('selected');
          updateSummary();
        });
      }

      $lab.append($inp);
      $lab.append($('<span>', { class: 'opt-text' }).text(`${o.v} ${decodeEntities(o.t)}`));
      $opts.append($lab);
    });

    $body.append($opts);

    // Collapsible Panduan Skor placed at the BOTTOM of aspect options
    if (it.legend) {
      const $details = $('<details>', { class: 'legend-collapsible' });
      $details.append('<summary>Panduan Skor / Rubrik</summary>');
      $details.append($('<div>', { class: 'legend-body' }).html(decodeEntities(it.legend)));
      $body.append($details);
    }

    $card.append($head, $body);
    $wrap.append($card);
  });
}

/* ===== render GPS options ===== */
function renderGPSSection(){
  const $box = $('#gpsOptions');
  $box.empty();

  GPS_OPTIONS.forEach(o => {
    const optId = `gps_${o.v}`;
    const isChecked = (gps !== null && Number(gps) === Number(o.v));
    const $lab  = $('<label>', { class: 'opt' + (isChecked ? ' selected' : ''), for: optId });
    const $inp  = $('<input>', {
      type: 'radio',
      name: 'gps',
      id: optId,
      value: o.v,
      disabled: READ_ONLY
    });

    if (isChecked) {
      $inp.prop('checked', true);
    }

    if (!READ_ONLY) {
      $inp.on('change', function() {
        gps = Number(o.v);
        $('#gps_card').removeClass('unscored-warning');
        $('#gps_card').find('.unscored-badge').remove();
        $box.find('.opt').removeClass('selected');
        $lab.addClass('selected');
        updateSummary();
      });
    }

    $lab.append($inp);
    $lab.append($('<span>', { class: 'opt-text' }).text(o.t));
    $box.append($lab);
  });

  $('#exKeterangan').on('input', function(){
    keterangan = $(this).val();
  });
}

/* ===== validation & auto-scroll ===== */
function validateAndSubmit(){
  if (READ_ONLY) return;

  let unscoredElements = [];
  let firstUnscoredEl = null;

  $('.unscored-warning').removeClass('unscored-warning');
  $('.unscored-badge').remove();

  // Validate each aspect
  items.forEach((it, idx) => {
    const val = nilai[it.id];
    if (val === undefined || val === null || isNaN(val)) {
      const $card = $(`#aspect_card_${it.id}`);
      $card.addClass('unscored-warning');
      if ($card.find('.unscored-badge').length === 0) {
        $card.find('.q-head').append('<span class="unscored-badge">Belum Diisi</span>');
      }
      unscoredElements.push({ id: it.id, el: $card[0] });
      if (!firstUnscoredEl) firstUnscoredEl = $card[0];
    }
  });

  // Validate GPS
  if (gps === null || isNaN(gps)) {
    const $gpsCard = $('#gps_card');
    $gpsCard.addClass('unscored-warning');
    if ($gpsCard.find('.unscored-badge').length === 0) {
      $gpsCard.find('.q-head').append('<span class="unscored-badge">Belum Diisi</span>');
    }
    unscoredElements.push({ id: 'gps', el: $gpsCard[0] });
    if (!firstUnscoredEl) firstUnscoredEl = $gpsCard[0];
  }

  if (unscoredElements.length > 0) {
    // Smooth auto scroll to first unscored element
    firstUnscoredEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Custom non-blocking toast notification instead of browser alert()
    showToast(`Mohon lengkapi penilaian! Masih terdapat ${unscoredElements.length} aspek/komponen yang belum dinilai.`, 'error');
    return;
  }

  openFinish();
}

/* ===== timer ===== */
if (READ_ONLY){
  $('#exTimer').text(formatHHMMSStoMMSSMin(savedTime));
} else {
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

/* ===== modal selesai ===== */
function openFinish(){ $('#cfTotal').text($('#exTotal').text()); $('#confirmFinish').addClass('show'); }
function closeFinish(){ $('#confirmFinish').removeClass('show'); }
$('#cfCancel').on('click', closeFinish);

/* ===== submit ===== */
async function submitExam(){
  const fd = new URLSearchParams();

  for (const k in nilai) {
    fd.append(`nilai[${k}]`, nilai[k]);
  }

  const elapsed = window.__elapsedSec || 0;
  const h = Math.floor(elapsed / 3600);
  const m = Math.floor((elapsed % 3600) / 60);
  const s = elapsed % 60;
  const hh = String(h).padStart(2,'0');
  const mm = String(m).padStart(2,'0');
  const ss = String(s).padStart(2,'0');
  fd.append('waktu', `${hh}:${mm}:${ss}`);
  fd.append('durasi_detik', elapsed);

  if (gps !== null && !isNaN(gps)) {
    fd.append('gps', gps);
  }

  const currentKet = $('#exKeterangan').val() !== undefined ? $('#exKeterangan').val() : (keterangan || '');
  fd.append('keterangan', currentKet);

  const csrfSel = (window.CSS && CSS.escape)
    ? `input[name="${CSS.escape(CSRF_NAME)}"]`
    : `input[name="${CSRF_NAME}"]`;
  const csrfInput = document.querySelector(csrfSel);
  if (csrfInput) fd.append(CSRF_NAME, csrfInput.value);

  try{
    const res = await fetch(SUBMIT_URL, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
      body: fd.toString()
    });
    const j = await res.json();

    if (j.status === 'ok') {
      if (window.__osceTimer) clearInterval(window.__osceTimer);
      showToast(`Tersimpan. Total skor: ${j.total}`, 'success');
      setTimeout(() => {
        window.location.href = <?= json_encode(site_url('e-osce/panel')) ?>;
      }, 1000);
    } else {
      showToast(j.message || 'Gagal menyimpan', 'error');
    }
  } catch (e){
    showToast('Gagal menyimpan (jaringan). Coba lagi.', 'error');
    console.error(e);
  }
}

$('#cfOk').on('click', async function(){
  if (!READ_ONLY){
    closeFinish();
    await submitExam();
  }
});

/* ===== init ===== */
$(function(){
  renderMediaSoal();
  renderAllAspects();
  renderGPSSection();
  updateSummary();
});
</script>
</body>
</html>
