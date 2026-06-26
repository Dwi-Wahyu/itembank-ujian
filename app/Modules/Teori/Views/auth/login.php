<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - E-Ujian</title>
  <?= csrf_meta() ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= base_url('assets/css/auth.css') ?>" rel="stylesheet">
  <style>
    :root{ --brand:#0ea5a5; }
    body{ font-family:'Inter',system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans"; }
    .split-wrap{ min-height:100vh; }
    .left-hero{
      position:relative; color:#fff; min-height:40vh;
      background: linear-gradient(135deg, rgba(14,165,165,.55), rgba(11,118,110,.55)),
        url('<?= base_url('assets/img/login-side.png') ?>');
      background-position:center; background-size:cover; background-repeat:no-repeat; background-blend-mode:multiply;
    }
    @media (min-width: 992px){ .left-hero{ min-height:100vh; } }
    .left-hero .brand-mark{ position:absolute; top:18px; left:22px; display:flex; align-items:center; gap:10px; text-decoration:none; color:#fff; }
    .left-hero .brand-mark span{ font-weight:800; font-size:16px; line-height:1; text-shadow:0 2px 8px rgba(0,0,0,.25); }
    .left-hero .hero-caption{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:left; padding:24px; }
    .left-hero .hero-caption h1{ font-weight:900; font-size:clamp(28px,2.8vw,44px); margin-bottom:.25rem; text-shadow:0 6px 22px rgba(0,0,0,.22); }
    .left-hero .hero-caption p{ font-size:clamp(14px,1.2vw,18px); opacity:.95; text-shadow:0 4px 14px rgba(0,0,0,.2); margin:0; }
    .left-hero .brand-mark img{
      height: 40px; width:auto; display:block;
      border:2.5px solid #fff; border-radius:10px; padding:4px; background:#f3f3f3; box-shadow:0 4px 14px rgba(0,0,0,.18);
    }
    .auth-card{ max-width:520px; width:100%; margin:0 auto; }
    .form-control{ border-radius:10px; height:48px; }
    .form-check-input{ width:1.1rem; height:1.1rem; }
    .shadow-soft{ box-shadow:0 10px 30px rgba(2,6,23,.06); }
    .small-muted{ color:#6b7280; font-size:.925rem; }
    .brand-btn{ background:var(--brand); border-color:var(--brand); color:#fff!important; }
    .brand-btn:hover,.brand-btn:focus{ filter:brightness(.95); color:#fff!important; }
    /* overlay loading generik */
.loading-backdrop{
  position:fixed; inset:0; z-index:1055;
  background:rgba(255,255,255,.88);
  display:none; align-items:center; justify-content:center;
}
.loading-backdrop .spinner{
  width:52px; height:52px; border-radius:50%;
  border:4px solid rgba(14,165,165,.25); border-top-color:#0ea5a5;
  animation:spin 1s linear infinite;
}
@keyframes spin{ to{ transform:rotate(360deg) } }

  </style>
</head>
<body>
    <!-- LOADING PAGE -->
<div id="pageLoading" class="loading-backdrop">
  <div class="text-center">
    <div class="spinner mx-auto"></div>
    <div class="mt-3 text-muted">Memproses...</div>
  </div>
</div>
  <div class="container-fluid p-0">
    <div class="row g-0 split-wrap">
      <div class="col-12 col-lg-6 d-none d-lg-block p-0">
        <div class="left-hero">
          <a href="<?= base_url() ?>" class="brand-mark">
            <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="Logo FKG">
            <span>Universitas Hasanuddin <br/>Aplikasi eUjian Unhas</span>
          </a>
          <div class="hero-caption">
            <h1>E-Ujian CBT <br/>Fakultas Kedokteran Gigi</h1>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5">
        <div class="auth-card px-3 px-md-4">
          <div class="card border-0 shadow-soft rounded-4">
            <div class="card-body p-4 p-md-5">
              <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">Selamat Datang</h4>
                <p class="small-muted mb-0">Silahkan masukkan NIM/Username dan Kode Unik Ujian untuk masuk ke aplikasi.</p>
              </div>

              <form id="loginForm" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">NIM/Username</label>
                  <input type="text" name="nim" class="form-control" placeholder="Masukan NIM anda..." required>
                </div>
                 <div class="mb-2">
    <label class="form-label fw-semibold">No. Ujian</label>
    <input type="text" name="no_ujian" class="form-control" placeholder="Masukkan No. Ujian..." required>
  </div>
                <div class="d-flex align-items-center justify-content-between mb-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="keepLogin" name="remember">
                    <label class="form-check-label" for="keepLogin">Tetap login</label>
                  </div>
                </div>

                <button type="submit" class="btn brand-btn w-100 py-2 fw-semibold rounded-3" id="btnLogin">
                  Masuk Sekarang
                </button>
              </form>

              <div class="mt-4 text-center">
                <small id="loginMsg" class="text-danger d-none"></small>
              </div>
            </div>
          </div>

          <div class="text-center mt-4 small-muted">
            2025 © <a href="#" target="_blank">E-Ujian SkytelIndo</a>
          </div>
        </div>
      </div>

      <div class="col-12 d-lg-none p-0">
        <div class="left-hero" style="min-height:40vh">
          <a href="<?= base_url() ?>" class="brand-mark">
            <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="Logo FKG">
            <span>Universitas Hasanuddin</span>
            <span>Aplikasi eUjian Unhas</span>
          </a>
          <div class="hero-caption">
            <h1>E-Ujian CBT</h1>
            <p>Fakultas Kedokteran Gigi</p>
          </div>
        </div>
      </div>

    </div>
  </div>
<!-- MODAL INFORMASI UJIAN -->
<div class="modal fade" id="infoUjianModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">INFORMASI</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <div id="mhsNama" class="fw-semibold" style="font-size:18px"></div>
          <div id="mhsNim" class="text-muted"></div>
        </div>

        <h6 class="fw-bold mb-2">Informasi Ujian</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <tbody>
              <tr><th style="width:180px">Nama Ujian</th><td id="vNamaUjian"></td></tr>
              <tr><th>Departemen</th><td id="vDepartemen"></td></tr>
              <tr><th>Blok</th><td id="vBlok"></td></tr>
              <tr><th>Tanggal</th><td id="vTanggal"></td></tr>
              <tr><th>Mulai</th><td id="vMulai"></td></tr>
              <tr><th>Selesai</th><td id="vSelesai"></td></tr>
              <tr><th>Jumlah Soal</th><td id="vJumlahSoal"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <small class="text-muted me-auto">Pastikan Anda siap sebelum memulai.</small>
        <button type="button" class="btn brand-btn" id="btnMulaiUjian">Mulai Ujian</button>
      </div>
    </div>
  </div>
</div>
<!-- WAJIB: Bootstrap JS untuk Modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    function getCsrf(){ const m=document.querySelector('meta[name="csrf-token"]'); return m?m.getAttribute('content'):'<?= csrf_hash() ?>'; }
    function setCsrf(t){ let m=document.querySelector('meta[name="csrf-token"]'); if(!m){m=document.createElement('meta');m.setAttribute('name','csrf-token');document.head.appendChild(m);} m.setAttribute('content',t); const h=document.querySelector('input[name="<?= csrf_token() ?>"]'); if(h) h.value=t; }

   const BASE = '<?= base_url() ?>';
const pageLoading = $('#pageLoading');

  function fmtDate(d){ return d || '-'; }
  function fmtTime(t){ return t ? t.substring(0,5) : '-'; } // HH:MM

$('#loginForm').on('submit', function(e){
  e.preventDefault();
  $('#btnLogin').prop('disabled', true).text('Memproses...');
  $('#loginMsg').addClass('d-none').text('');
  pageLoading.fadeIn(80);

  $.ajax({
    url: '<?= base_url('teori/login') ?>',
    method: 'POST',
    data: $(this).serialize(),
    dataType: 'json'
  })
  .done(function(res){
    if (res.csrf_token) setCsrf(res.csrf_token);

    if (res.status === 'ok') {
      if (res.exam) {
        // isi identitas
        $('#mhsNama').text(res.mhs?.nama || '');
        $('#mhsNim').text(res.mhs?.nim || '');

        // isi info ujian
        $('#vNamaUjian').text(res.exam.nama_ujian || '');
        $('#vDepartemen').text(res.exam.departemen || 'Semua Departemen');
        $('#vBlok').text(res.exam.blok || 'Semua Blok');
        $('#vTanggal').text(fmtDate(res.exam.tanggal));
        $('#vMulai').text(fmtTime(res.exam.mulai));
        $('#vSelesai').text(fmtTime(res.exam.selesai));
        $('#vJumlahSoal').text(res.exam.jumlah_soal ?? 0);

        pageLoading.fadeOut(120);

        // tombol mulai
     $('#btnMulaiUjian').off('click').on('click', function(){
  const kode = encodeURIComponent(res.exam?.kode || res.exam_code || '');
  // kalau mau ikutkan no_ujian di URL juga:
  const noUjian = encodeURIComponent(res.no_ujian || '');
  window.location.href = BASE + 'teori/ujian/mulai?kode=' + kode + '&no_ujian=' + noUjian;
});

        // tampilkan modal info
        new bootstrap.Modal(document.getElementById('infoUjianModal')).show();
      } else {
        pageLoading.fadeOut(120);
        Swal.fire({
          icon: 'warning',
          title: 'Tidak ada ujian aktif',
          text: 'Kode valid, tapi ujian tidak tersedia saat ini.'
        });
      }
    } else {
      // STATUS ERROR → tampilkan swal
      pageLoading.fadeOut(120);
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: res.message || 'Gagal login / kode ujian tidak valid.'
      });
      // opsional tetap isi label kecil
      $('#loginMsg').removeClass('d-none').text(res.message || 'Gagal login.');
    }
  })
  .fail(function(xhr){
    pageLoading.fadeOut(120);
    const msg = (xhr?.responseJSON?.message) || xhr?.statusText || 'Terjadi kesalahan jaringan.';
    // update CSRF bila ada di response error
    if (xhr?.responseJSON?.csrf_token) setCsrf(xhr.responseJSON.csrf_token);

    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: msg
    });
    $('#loginMsg').removeClass('d-none').text(msg);
  })
  .always(function(){
    $('#btnLogin').prop('disabled', false).text('Masuk Sekarang');
  });
});

  </script>
</body>
</html>
