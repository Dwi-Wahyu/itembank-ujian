<!-- Modules/Admin/Views/layouts/admin.php -->
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= esc($title ?? 'Admin') ?> · E-Ujian</title>
  <?= csrf_meta() ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- tambahkan versi agar cache browser kebuka -->
  <link href="<?= base_url('assets/css/admin.css?v=1.0.8') ?>" rel="stylesheet">
  <!-- SweetAlert2 dan Flatpickr harus masuk SEBELUM script halaman -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
  .page-loader{
    position:fixed; inset:0; display:none; align-items:center; justify-content:center;
    background:rgba(255,255,255,.55); z-index: 2050;
  }
  .table-loading{
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,.65);
    z-index:10;
    border-radius:12px;
    pointer-events: none;
  }

  /* === LAYOUT OVERRIDE: bikin konten full width & rapi === */
  .app-shell{
    display:flex;
    min-height:100vh;
  }

  .app-content{
    flex:1;                 /* isi sisa ruang di sebelah sidebar */
    max-width:100%;         /* jangan di-limit */
    padding:20px 24px 24px; /* spasi nyaman kiri-kanan-atas */
    overflow-x:hidden;      /* biar halaman nggak bisa geser kiri-kanan */
  }
</style>

</head>
<body class="app">
  <?= view('\Modules\Admin\Views\partials\header', ['user' => $user ?? null]) ?>

  <!-- BACKDROP: penting untuk mobile slide-in -->
  <div class="app-backdrop" id="backdrop"></div>

  <div class="app-shell">
    <!-- pastikan di partial: <aside class="app-sidebar" id="sidebar"> -->
    <?= view('\Modules\Admin\Views\partials\sidebar', ['menuActive' => $menuActive ?? 'dashboard']) ?>

    <main class="app-content">
      <?= $this->renderSection('content') ?>
 <div id="pageLoader" class="page-loader">
  <div class="spinner-border" role="status" aria-hidden="true"></div>
</div>

      <?= view('\Modules\Admin\Views\partials\footer') ?>
    </main>
  </div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/lodash.debounce@4.0.8/lodash.debounce.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>


<script>
  window.csrfTokenName  = '<?= csrf_token() ?>';
  window.csrfTokenValue = '<?= csrf_hash() ?>';

   window.csrf = {
    name: '<?= csrf_token() ?>',      // contoh: csrf_test_name
    header: 'X-CSRF-TOKEN',

    get() {
      // dari <meta> atau hidden input cadangan
      const m = document.querySelector('meta[name="csrf-token"]');
      if (m && m.getAttribute('content')) return m.getAttribute('content');
      const h = document.querySelector(`input[name="${this.name}"]`);
      return h ? h.value : '';
    },
    set(t) {
      if (!t) return;
      // meta
      let m = document.querySelector('meta[name="csrf-token"]');
      if (!m) { m = document.createElement('meta'); m.setAttribute('name', 'csrf-token'); document.head.appendChild(m); }
      m.setAttribute('content', t);
      // hidden input global (cadangan)
      let hi = document.getElementById('__csrf_global');
      if (!hi) {
        hi = document.createElement('input');
        hi.type = 'hidden'; hi.id = '__csrf_global'; hi.name = this.name;
        document.body.appendChild(hi);
      }
      hi.value = t;
      // update semua hidden yang sama (mis: dari <?= csrf_field() ?>)
      document.querySelectorAll(`input[name="${this.name}"]`).forEach(el => el.value = t);
    },
    body(data={}) {
      data[this.name] = this.get();
      return data;
    }
  };

  // Wrapper POST: injek token di body + header
  $.postCSRF = function(url, data={}, opts={}) {
    const token = window.csrf.get();
    return $.ajax(Object.assign({
      url, method: 'POST',
      data: window.csrf.body(data),
      headers: { [window.csrf.header]: token }
    }, opts));
  };

  // Helper serialize form + CSRF
  $.fn.serializeWithCSRF = function() {
    const o = {}; this.serializeArray().forEach(x => o[x.name] = x.value);
    return window.csrf.body(o);
  };

  // Auto-refresh token bila server mengirimkan yang baru
  $(document).ajaxComplete(function(_e, xhr) {
    const h = xhr.getResponseHeader('X-CSRF-TOKEN');
    if (h) return window.csrf.set(h);
    try {
      const j = JSON.parse(xhr.responseText);
      if (j && j.csrf_token) window.csrf.set(j.csrf_token);
    } catch (_) {}
  });
// fallback kalau _ tidak ada
window.debounce = window.debounce || (window._ && _.debounce) || function(fn, wait){
  let t; return function(...args){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,args), wait); };
};
</script>
  <!-- bust cache JS juga -->
  <script src="<?= base_url('assets/js/admin.js?v=3') ?>"></script>
    <script>
    window.swalToast = function(title, icon='success') {
    if (window.Swal) {
      return Swal.fire({ toast:true, position:'top-end', timer:1700, showConfirmButton:false, icon, title });
    }
    // fallback
    console.log('[toast]', icon, title);
  };
  window.Loader = {
    el: document.getElementById('pageLoader'),
    show(){ this.el.style.display='flex'; },
    hide(){ this.el.style.display='none'; }
  };

    // Inisialisasi pickers sekali, bisa dipanggil di mana saja.
// ctx opsional: container element (default document)
window.initPickers = function(ctx){
  ctx = ctx || document;
  if (!window.flatpickr) return;
  flatpickr(ctx.querySelectorAll('.js-date'), {
     dateFormat: 'Y-m-d',   // nilai yang dikirim ke server
  altInput: true,
  altFormat: 'd/m/Y',    // tampilan ke user
  allowInput: true
  });
  flatpickr(ctx.querySelectorAll('.js-time'), {
    enableTime: true,
    noCalendar: true,
    time_24hr: true,
    dateFormat: 'H:i',
    allowInput: true
  });
};

// jalankan otomatis saat halaman siap
document.addEventListener('DOMContentLoaded', function(){ window.initPickers(); });
window.withListLoading = function($container, fn){
  const overlay = $(
    '<div class="table-loading"><div class="spinner-border" role="status" aria-hidden="true"></div></div>'
  );
  $container.css('position','relative').append(overlay);
  const done = ()=> overlay.remove();
  try { return Promise.resolve(fn()).finally(done); } catch(e){ done(); throw e; }
};

  </script>
  <?= $this->renderSection('scripts') ?>

</body>
</html>
