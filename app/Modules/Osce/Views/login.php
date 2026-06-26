<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Pengawas osce - E-Ujian</title>
  <?= csrf_meta() ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{ --brand:#0ea5a5; }
    body{ font-family:'Inter',system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans"; }
    .split-wrap{ min-height:100vh; }
    .left-hero{
      position:relative; color:#fff; min-height:40vh;
      background:
        linear-gradient(135deg, rgba(14,165,165,.55), rgba(11,118,110,.55)),
        url('<?= base_url('assets/img/login-side.png') ?>');
      background-position:center; background-size:cover; background-repeat:no-repeat; background-blend-mode:multiply;
    }
    @media (min-width: 992px){ .left-hero{ min-height:100vh; } }
    .left-hero .brand-mark{ position:absolute; top:18px; left:22px; display:flex; align-items:center; gap:10px; text-decoration:none; color:#fff; }
    .left-hero .brand-mark img{ height:40px; width:auto; display:block; border:2.5px solid #fff; border-radius:10px; padding:4px; background:rgba(255,255,255,.08); box-shadow:0 4px 14px rgba(0,0,0,.18); }
    .left-hero .brand-mark span{ font-weight:800; font-size:16px; line-height:1; text-shadow:0 2px 8px rgba(0,0,0,.25); }
    .left-hero .hero-caption{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; flex-direction:column; text-align:center; padding:24px; }
    .left-hero .hero-caption h1{ font-weight:900; font-size:clamp(28px,2.8vw,44px); margin-bottom:.25rem; text-shadow:0 6px 22px rgba(0,0,0,.22); }
    .left-hero .hero-caption p{ font-size:clamp(14px,1.2vw,18px); opacity:.95; text-shadow:0 4px 14px rgba(0,0,0,.2); margin:0; }

    .auth-card{ max-width:520px; width:100%; margin:0 auto; }
    .shadow-soft{ box-shadow:0 10px 30px rgba(2,6,23,.06); }
    .small-muted{ color:#6b7280; font-size:.925rem; }
    .brand-btn{ background:var(--brand); border-color:var(--brand); color:#fff!important; }
    .brand-btn:hover,.brand-btn:focus{ filter:brightness(.95); color:#fff!important; }
    .form-control{ border-radius:10px; height:48px; }
    .form-check-input{ width:1.1rem; height:1.1rem; }
    /* biar disabled tidak pudar */
    .btn.brand-btn:disabled,.btn.brand-btn.disabled{
      background:var(--brand)!important;border-color:var(--brand)!important;color:#fff!important;opacity:1!important;cursor:wait;
    }
  </style>
</head>
<body>
  <div class="container-fluid p-0">
    <div class="row g-0 split-wrap">
      <!-- LEFT -->
      <div class="col-12 col-lg-6 d-none d-lg-block p-0">
        <div class="left-hero">
          <a href="<?= base_url() ?>" class="brand-mark">
             <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="Logo FKG">
            <span>FKG E-Ujian</span>
          </a>
          <div class="hero-caption">
            <h1>Panel osce</h1>
            <p>Fakultas Kedokteran Gigi</p>
          </div>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5">
        <div class="auth-card px-3 px-md-4">
          <div class="card border-0 shadow-soft rounded-4">
            <div class="card-body p-4 p-md-5">
              <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">Login Pengawas osce</h4>
                <p class="small-muted mb-0">Masuk menggunakan NIP dan Kode Station.</p>
              </div>

              <form id="osceLoginForm" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">NIP</label>
                  <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP" required>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Kode Station</label>
                  <input type="text" name="kode" class="form-control" placeholder="Masukkan kode" required>
                </div>
                <button type="submit" class="btn brand-btn w-100 py-2 fw-semibold rounded-3" id="btnLoginosce">
                  <span class="btn-label">Login</span>
                  <span class="btn-spinner d-none spinner-border spinner-border-sm align-middle ms-2" role="status" aria-hidden="true"></span>
                </button>
              </form>

              <div class="mt-3 text-center">
                <small id="loginMsg" class="text-danger d-none"></small>
              </div>
            </div>
          </div>
          <div class="text-center mt-4 small-muted">2025 © FKG E-Ujian</div>
        </div>
      </div>

      <!-- LEFT (mobile) -->
      <div class="col-12 d-lg-none p-0">
        <div class="left-hero" style="min-height:40vh">
          <a href="<?= base_url() ?>" class="brand-mark">
            <img src="<?= base_url('assets/img/logo-fkg.png') ?>" alt="Logo FKG">
            <span>FKG E-Ujian</span>
          </a>
          <div class="hero-caption">
            <h1>Panel osce</h1>
            <p>Fakultas Kedokteran Gigi</p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
  function setCsrf(t){
    let m=document.querySelector('meta[name="csrf-token"]');
    if(!m){m=document.createElement('meta');m.setAttribute('name','csrf-token');document.head.appendChild(m);}
    m.setAttribute('content',t);
    const h=document.querySelector('input[name="<?= csrf_token() ?>"]'); if(h) h.value=t;
  }
  function setLoading(on){
    const $b = $('#btnLoginosce');
    if(on){ $b.prop('disabled', true); $b.find('.btn-label').text('Memproses...'); $b.find('.btn-spinner').removeClass('d-none'); }
    else{ $b.prop('disabled', false); $b.find('.btn-label').text('Login'); $b.find('.btn-spinner').addClass('d-none'); }
  }

  $('#osceLoginForm').on('submit', function(e){
    e.preventDefault();
    setLoading(true);
    $('#loginMsg').addClass('d-none').text('');

    $.ajax({
      url: '<?= site_url('e-osce/login') ?>',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json'
    }).done(function(res){
      if(res.csrf_token){ setCsrf(res.csrf_token); }
      if(res.status==='ok'){
        window.location.href = res.redirect ?? '<?= site_url('e-osce/panel') ?>';
        return;
      }
      $('#loginMsg').removeClass('d-none').text(res.message ?? 'Gagal login.');
    }).fail(function(xhr){
      const msg = xhr?.responseJSON?.message || 'Terjadi kesalahan jaringan.';
      $('#loginMsg').removeClass('d-none').text(msg);
    }).always(function(){
      setLoading(false);
    });
  });
  </script>
</body>
</html>
