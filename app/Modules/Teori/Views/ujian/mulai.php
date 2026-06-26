<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($exam['nama'] ?? 'Ujian Teori') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{ --brand:#0ea5a5; --ink:#0f172a; --muted:#6b7280; --chip:#e2e8f0; --ok:#16a34a; --danger:#ef4444; }
body{background:#f6f7fb;color:var(--ink);font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
.container-ujian{max-width:1200px;margin:24px auto;padding:0 12px}
.wrap{display:grid;grid-template-columns:300px 1fr;gap:22px}
@media (max-width: 992px){ .wrap{grid-template-columns:1fr} }
.cardx{background:#fff;border-radius:22px;box-shadow:0 10px 30px rgba(2,6,23,.06)}
.sidebar{padding:18px}
.profile h6{margin:0;font-weight:800}
.profile small{color:#64748b}
.grid-num{display:grid;grid-template-columns:repeat(7,1fr);gap:8px;margin-top:14px}
.grid-num .n{height:36px;border-radius:10px;border:0;background:#edf2f7;color:#111;font-weight:700}
.grid-num .active{background:var(--brand);color:#fff}
.grid-num .answered{background:var(--ok);color:#fff}
.main{padding:20px 22px 18px;position:relative}
.brand-head{display:flex;justify-content:center;gap:10px;align-items:center}
.brand-head img{height:34px}
.brand-head .sub{font-size:12px;color:#475569;margin-top:-6px}
.timer{position:absolute;right:22px;top:16px;color:#475569;font-weight:700}
.qwrap{min-height:280px;margin:14px 0 18px}
.qtext{font-size:16px;line-height:1.7}
.qfile img{max-width:100%;height:auto;border-radius:12px;border:1px solid #e5e7eb;margin-top:10px}
.choice{display:flex;align-items:center;gap:10px;margin:12px 0}
.choice input{width:18px;height:18px}
.footerbar{display:flex;justify-content:space-between;align-items:center;margin-top:16px}
.btn-brand{background:var(--brand);border-color:var(--brand);color:#fff}
.badge-chip{background:#fef3c7;color:#92400e;border:1px solid #f59e0b;border-radius:10px;padding:4px 8px;font-size:12px;position:fixed;left:50%;transform:translateX(-50%);bottom:16px;display:none;z-index:9999}
.noselect{user-select:none;-webkit-user-select:none}

/* Timer merah & kedip ≤ 30 detik */
.timer.warn{ color: var(--danger); animation: timer-blink .9s linear infinite; }
@keyframes timer-blink{ 0%,100%{opacity:1} 50%{opacity:.25} }

/* overlay loading generik */
.loading-backdrop{ position:fixed; inset:0; z-index:1055; background:rgba(255,255,255,.88); display:none; align-items:center; justify-content:center;}
.loading-backdrop .spinner{ width:52px; height:52px; border-radius:50%; border:4px solid rgba(14,165,165,.25); border-top-color:#0ea5a5; animation:spin 1s linear infinite;}
@keyframes spin{ to{ transform:rotate(360deg) } }
</style>
<?= csrf_meta() ?>
</head>
<body class="noselect">

<!-- LOADING UJIAN -->
<div id="examLoading" class="loading-backdrop">
  <div class="text-center">
    <div class="spinner mx-auto"></div>
    <div id="loadingText" class="mt-3 text-muted">Memuat soal & jawaban...</div>
  </div>
</div>

<div class="container-ujian">
  <div class="wrap">
    <aside class="cardx sidebar">
      <div class="profile text-center mb-2">
        <h6><?= esc($mhs['nama'] ?? '') ?></h6>
        <small><?= esc($mhs['nim'] ?? '') ?></small>
      </div>
      <div id="numGrid" class="grid-num"></div>
    </aside>

    <section class="cardx main">
      <div class="brand-head">
        <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="UNHAS">
        <div>
          <div class="fw-bold text-center">Universitas Hasanuddin</div>
          <div class="sub text-center">Aplikasi eUjian Unhas</div>
        </div>
       <div class="timer"><span id="timer">00:00:00</span></div>

      </div>

      <hr class="mt-2 mb-2">

      <div id="qArea" class="qwrap">
        <div class="qtext" id="qText">Memuat soal...</div>
        <div class="qfile" id="qFile"></div>

        <div class="choices">
          <?php foreach(['A','B','C','D','E'] as $opt): ?>
          <label class="choice">
            <input type="radio" name="jawaban" value="<?= $opt ?>"> <span id="lbl<?= $opt ?>"></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="footerbar">
        <div>
          <button id="btnPrev" class="btn btn-secondary">Sebelumnya</button>
          <button id="btnNext" class="btn btn-brand">Selanjutnya</button>
        </div>
        <div>
          <label class="me-3"><input type="checkbox" id="mark"> Tandai Soal</label>
          <button id="btnFinish" class="btn btn-outline-danger">Selesai</button>
        </div>
      </div>
    </section>
  </div>
</div>

<div class="badge-chip" id="chip">Peringatan</div>

<!-- MODAL KONFIRMASI SELESAI -->
<div class="modal fade" id="confirmFinishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Selesaikan Ujian?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        Jawaban yang sudah tersimpan akan dikumpulkan. Anda tidak dapat kembali mengerjakan setelah ini.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
        <button type="button" class="btn btn-danger" id="btnConfirmFinish">Ya, Selesai</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function(){
  /** =========================
   *  CSRF + Request Queue (jqXHR Deferred)
   *  ========================= */
  const CSRF = {
    name: '<?= csrf_token() ?>',
    hash: '<?= csrf_hash() ?>',
    set(h){ this.hash = h; const m=document.querySelector('meta[name="csrf-token"]'); if(m) m.setAttribute('content', h); }
  };

  // Sisipkan token ke SEMUA POST
  $.ajaxSetup({
    beforeSend: function (_xhr, settings) {
      if ((settings.type || 'GET').toUpperCase() === 'POST') {
        if (settings.data instanceof FormData) {
          settings.data.append(CSRF.name, CSRF.hash);
        } else if (typeof settings.data === 'string') {
          settings.data += (settings.data ? '&' : '') + encodeURIComponent(CSRF.name)+'='+encodeURIComponent(CSRF.hash);
        } else if (typeof settings.data === 'object' && settings.data !== null) {
          settings.data[CSRF.name] = CSRF.hash;
        } else {
          settings.data = encodeURIComponent(CSRF.name)+'='+encodeURIComponent(CSRF.hash);
        }
      }
    }
  });

  // Perbarui token dari setiap response JSON (GET/POST)
  $(document).ajaxComplete(function(_e, xhr){
    try {
      const j = xhr.responseJSON || JSON.parse(xhr.responseText);
      if (j && j.csrf_token) CSRF.set(j.csrf_token);
    } catch(_) {}
  });

  // Queue berbasis jQuery Deferred (agar .done/.always tetap bisa dipakai)
  let __queue = $.Deferred().resolve().promise();

  function postCI(url, data) {
    const task = () => $.ajax({ url, method:'POST', data, dataType:'json' });

    const p = __queue = __queue.then(
      function(){ // sukses step sebelumnya → jalankan task
        return task().then(
          function(res){ if(res && res.csrf_token) CSRF.set(res.csrf_token); return res; },
          function(err){
            // retry sekali bila 403 (token expired)
            if (err && err.status === 403) {
              return $.getJSON('<?= base_url('teori/csrf') ?>')
                .then(function(r){ if(r && r.csrf_token) CSRF.set(r.csrf_token); })
                .then(task);
            }
            return $.Deferred().reject(err).promise();
          }
        );
      },
      function(){ // error step sebelumnya → tetap jalankan task agar queue lanjut
        return task();
      }
    );
    return p; // jqXHR-like promise → .done/.fail/.always OK
  }
// Kirim POST langsung, tanpa ikut queue (untuk FINISH)
function postDirect(url, data){
  return $.ajax({
    url, method:'POST', data, dataType:'json', timeout: 8000  // timeout biar gak nunggu lama
  });
}

  /** =========================
   *  Variabel global
   *  ========================= */
  const BASE   = '<?= base_url() ?>';
  const kode   = <?= json_encode($exam['kode']) ?>;
  const attemptId = <?= (int)$attempt['id'] ?>;

  let items = [], answers = {}, idx = 0;
  let remaining = 0, lastBeat = Date.now();
  let viol = <?= (int)$attempt['violations'] ?>;

  /** =========================
   *  Timer (pause/resume)
   *  ========================= */
  const timerEl = document.getElementById('timer');
  let warned30 = false, isPaused = false, timerHandle = null;

// GANTI fungsi ini:
function fmt(s){ const m=Math.floor(s/60), ss=('0'+(s%60)).slice(-2); return `${m}:${ss}`; }

// MENJADI:
function fmtHMS(sec){
  sec = Math.max(0, parseInt(sec||0,10));
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = sec % 60;
  const hh = ('0'+h).slice(-2), mm = ('0'+m).slice(-2), ss = ('0'+s).slice(-2);
  return `${hh}:${mm}:${ss}`;
}

  function updateTimerUI(){
  timerEl.textContent = fmtHMS(remaining);   // <- ganti ke fmtHMS
  if(remaining <= 30){
    timerEl.classList.add('warn');
    if(!warned30){ $('#chip').text('Sisa waktu 30 detik!').fadeIn(120).delay(1200).fadeOut(200); warned30 = true; }
  } else {
    timerEl.classList.remove('warn');
  }
}

  function startTimer(){
    if (timerHandle) return;
    timerHandle = setInterval(()=>{
      if (isPaused) return;
      remaining = Math.max(0, remaining-1);
      updateTimerUI();
      if(remaining===0) submitFinish(true);
    }, 1000);
  }
 function pauseTimer(){
  if(isPaused) return;
  isPaused = true;
  if(timerHandle){ clearInterval(timerHandle); timerHandle = null; }
  lastBeat = Date.now();           // penting: baseline delta saat jeda
}
function resumeTimer(){
  if(!isPaused) return;
  isPaused = false;
  lastBeat = Date.now();           // penting: baseline delta saat lanjut
  startTimer();
}
// === Heartbeat (skip saat pause)
let hbTimer = null;
hbTimer = setInterval(()=>{
  if (isPaused) return;
  const now = Date.now(); const delta = Math.max(1, Math.round((now - lastBeat)/1000)); lastBeat = now;
  postCI(BASE+'teori/ujian/heartbeat', { attempt_id: attemptId, delta })
    .done(res=>{
      if(res?.state){
        remaining = res.remaining ?? remaining;
        viol      = res.violations ?? viol;
        updateTimerUI();
      }
      // JANGAN auto-finish di sini
    });
}, 15000);

  /** =========================
   *  UI util
   *  ========================= */
 
  const examLoading = $('#examLoading');
  const loadingText = $('#loadingText');
  const chip = $('#chip');
const warn = (msg)=>{
  chip.stop(true,true).text(msg).fadeIn(80).delay(1200).fadeOut(200);
};

  function disableControls(disabled){
    $('#btnPrev, #btnNext, #btnFinish, #numGrid .n, input[name="jawaban"], #mark').prop('disabled', !!disabled);
  }

  /** =========================
   *  INIT
   *  ========================= */
  examLoading.fadeIn(80);
  $.getJSON(BASE+'teori/ujian/init', {kode}, (res)=>{
    if(res?.status!=='ok'){ location.href = BASE+'teori/login'; return; }
    remaining = parseInt(res.attempt.remaining||0,10);
    items     = res.items || [];
    answers   = res.answers || {};

    buildGrid(items.length);
    render(0);
    updateTimerUI();
    requestFullscreen();
    startTimer();
  }).always(()=>{ examLoading.fadeOut(120); });

  /** =========================
   *  Grid & render
   *  ========================= */
  function buildGrid(n){
    const g = $('#numGrid').empty();
    for(let i=0;i<n;i++){
      $('<button/>',{class:'n',text:i+1})
        .on('click', ()=> go(i))
        .appendTo(g);
    }
    refreshGrid();
  }
  function refreshGrid(){
    const btns = $('#numGrid .n').removeClass('active answered');
    btns.eq(idx).addClass('active');
    btns.each(function(i){
      const id = items[i]?.id;
      if(id && answers[id]?.jawaban) $(this).addClass('answered');
    });
  }
function render(i){
  idx = Math.max(0, Math.min(i, items.length-1));
  const it = items[idx] || {};

  $('#qText').html((it.vignette? it.vignette+'<br><br>':'') + (it.pertanyaan||''));

  // ==== LAMPIRAN ====
  let htmlFile = '';
  if (Array.isArray(it.file_urls) && it.file_urls.length) {
    htmlFile = it.file_urls.map(u => `<img loading="lazy" src="${u}" alt="lampiran">`).join('');
  } else if (it.file_url) {
    htmlFile = `<img loading="lazy" src="${it.file_url}" alt="lampiran">`;
  } else if (it.file) {
    // fallback lama: it.file bisa sudah termasuk 'uploads/...' atau cuma 'nama.png'
    const f = String(it.file).trim();
    const rel = f.startsWith('uploads/') ? f : ('uploads/soal_teori/' + f.replace(/^.*[\\/]/,''));
    htmlFile = `<img loading="lazy" src="${BASE}${rel}" alt="lampiran">`;
  }
  $('#qFile').html(htmlFile);

  // opsi
  $('#lblA').text(it.a||'—'); $('#lblB').text(it.b||'—'); $('#lblC').text(it.c||'—'); $('#lblD').text(it.d||'—'); $('#lblE').text(it.e||'—');

  // set radio dari resume
  $('input[name="jawaban"]').prop('checked', false);
  const ans = answers[it.id]?.jawaban;
  if(ans) $('input[name="jawaban"][value="'+ans+'"]').prop('checked', true);

  // tandai (local only)
  $('#mark').prop('checked', !!getMark(it.id));

  refreshGrid();
}


  /** =========================
   *  Save current (POST hanya jika ada perubahan) + loading + pause timer
   *  ========================= */
function saveCurrent(cb){
  const it = items[idx]; 
  if(!it){ cb && cb(); return; }

  const newAns  = $('input[name="jawaban"]:checked').val() || null;
  const newMark = $('#mark').is(':checked') ? 1 : 0;
  const prevAns  = answers[it.id]?.jawaban || null;
  const prevMark = getMark(it.id) ? 1 : 0;

  // jika tak ada perubahan → lanjutkan segera
  if (newAns === prevAns && newMark === prevMark) { cb && cb(); return; }

  // Update cache lokal agar UI responsif
  answers[it.id] = { jawaban: newAns };
  setMark(it.id, !!newMark);
  refreshGrid();

  // >>> PENTING: panggil cb (render next) SEGERA, jangan menunggu network
  cb && cb();

  // Kirim ke server di background (antri, non-blocking)
  postCI(BASE+'teori/ujian/jawab', { kode: kode, soal_id: it.id, jawaban: newAns })
    .fail(()=> {
      // opsional: tampilkan chip kecil saat jaringan lambat
      $('#chip').text('Jaringan lambat, mencoba lagi…').stop(true,true).fadeIn(80).delay(900).fadeOut(150);
    });
}


  // Navigasi
const go = (i) => {
  const target = Math.max(0, Math.min(i, items.length-1));
  saveCurrent(()=> render(target));
};

$('#btnPrev').on('click', ()=> go(idx-1));
$('#btnNext').on('click', ()=> go(idx+1));
$('#mark').on('change', ()=> saveCurrent()); // menandai tidak pindah soal


  function markKey(soalId){ return `mark:${kode}:${soalId}`; }
  function getMark(soalId){ return localStorage.getItem(markKey(soalId)) === '1'; }
  function setMark(soalId, v){ if(v) localStorage.setItem(markKey(soalId), '1'); else localStorage.removeItem(markKey(soalId)); }

  /** =========================
   *  Finish (+ auto saat waktu habis)
   *  ========================= */
function submitFinish(auto=false){
  // Stop semua aktivitas yang bisa nambah antrean
  pauseTimer();
  if (hbTimer){ clearInterval(hbTimer); hbTimer = null; }

  disableControls(true);
  loadingText.text(auto ? 'Waktu habis. Menyelesaikan ujian...' : 'Menyelesaikan ujian...');
  examLoading.fadeIn(80);

  const finishURL = BASE + 'teori/ujian/finish';
  let redirected = false;
  const gotoHasil = ()=>{
    if (redirected) return;
    redirected = true;
    // Lepas beforeunload supaya tidak menahan pindah halaman
    window.removeEventListener('beforeunload', preventLeave);
    // Arahkan ke halaman hasil (server akan menyiapkan ringkasan)
    location.href = BASE + 'teori/ujian/hasil/' + attemptId;
  };

  // 1) Coba kirim pakai sendBeacon (non-blocking) → lalu redirect cepat
  if (navigator.sendBeacon) {
    try{
      const fd = new FormData();
      fd.append(CSRF.name, CSRF.hash);
      fd.append('attempt_id', attemptId);
      navigator.sendBeacon(finishURL, fd);
      setTimeout(gotoHasil, 200); // langsung pergi, gak nunggu respons
      return;
    }catch(_){}
  }

  // 2) Fallback: POST langsung (tanpa queue) + fallback timeout untuk tetap redirect
  let doneOrTimeout = false;
  const t = setTimeout(()=>{ if(!doneOrTimeout){ doneOrTimeout = true; gotoHasil(); } }, 1500);

  postDirect(finishURL, { attempt_id: attemptId })
    .always(()=>{ if(!doneOrTimeout){ doneOrTimeout = true; clearTimeout(t); gotoHasil(); } });
}

setInterval(function(){
  $.getJSON('<?= base_url('teori/attempt/status') ?>', { id: attemptId})
    .done(function(r){
      if (r?.status === 'finished') location.reload();
    });
}, 1200);

  const confirmModal = new bootstrap.Modal(document.getElementById('confirmFinishModal'));
  $('#btnFinish').on('click', ()=> confirmModal.show());
  $('#btnConfirmFinish').on('click', function(){
    confirmModal.hide();
    saveCurrent(()=> submitFinish(false));
  });

  /** =========================
   *  Heartbeat (skip saat pause)
   *  ========================= */
 setInterval(()=>{
  if (isPaused) return;
  const now = Date.now(); const delta = Math.max(1, Math.round((now - lastBeat)/1000)); lastBeat = now;

  // <- HAPUS reason:'tick'
  postCI(BASE+'teori/ujian/heartbeat', { attempt_id: attemptId, delta })
    .done(res=>{
      if(res?.state){
        remaining = res.remaining ?? remaining;
        viol      = res.violations ?? viol;
        updateTimerUI();
      }
      // if(res?.status === 'finished'){
      //   // Opsional: bedakan pesan sesuai penyebab
      //   submitFinish(true);
      // }
    });
}, 15000);

  /** =========================
   *  Anti-cheat (best-effort)
   *  ========================= */
  function requestFullscreen(){
  const el=document.documentElement;
  if(el.requestFullscreen) el.requestFullscreen({navigationUI:'hide'}).catch(()=>{});
}

document.addEventListener('fullscreenchange', ()=>{
  if(!document.fullscreenElement){
    // Keluar fullscreen → PAUSE timer, beri peringatan, minta balik fullscreen
    // pauseTimer();
    warn('Keluar dari layar penuh — timer dijeda');
    // Boleh kirim notifikasi ringan (tanpa mengubah waktu)
    // postCI(BASE+'teori/ujian/heartbeat', { attempt_id: attemptId, delta: 1, reason:'fullscreen-exit' });
    setTimeout(requestFullscreen, 200);
  } else {
    // Kembali fullscreen → RESUME timer
    resumeTimer();
    warn('Kembali ke layar penuh — timer berjalan');
  }
});

document.addEventListener('visibilitychange', ()=>{
  if(document.hidden){
  //  pauseTimer();
    warn('Jendela tidak aktif — timer dijeda');
    // Boleh kirim heartbeat ringan, tapi tidak perlu memicu auto-finish:
    // postCI(BASE+'teori/ujian/heartbeat', { attempt_id: attemptId, delta: 1, reason:'visibility' });
  } else {
    resumeTimer();
    warn('Kembali ke ujian — timer berjalan');
  }
});

 window.addEventListener('blur', ()=>{
  pauseTimer();
  warn('Fokus jendela hilang — timer dijeda');
  // postCI(BASE+'teori/ujian/heartbeat', { attempt_id: attemptId, delta: 1, reason:'blur' });
});

window.addEventListener('focus', ()=>{
  resumeTimer();
  warn('Fokus kembali — timer berjalan');
});

  window.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){ e.preventDefault(); e.stopImmediatePropagation(); requestFullscreen(); }
    const combo = e.ctrlKey || e.metaKey || e.altKey;
    if(['f11','f5'].includes(e.key.toLowerCase()) || (combo && ['r','w','t','n','p','s','l','k'].includes(e.key.toLowerCase()))){
      e.preventDefault(); e.stopImmediatePropagation();
    }
  }, true);
  document.addEventListener('contextmenu', e=>e.preventDefault());
  document.addEventListener('copy', e=>e.preventDefault());
  document.addEventListener('cut', e=>e.preventDefault());
  document.addEventListener('paste', e=>e.preventDefault());
  history.pushState(null,'',location.href);
  window.addEventListener('popstate', ()=> history.pushState(null,'',location.href));
function preventLeave(e){ e.preventDefault(); e.returnValue=''; }
window.addEventListener('beforeunload', preventLeave);

})();
</script>

</body>
</html>
