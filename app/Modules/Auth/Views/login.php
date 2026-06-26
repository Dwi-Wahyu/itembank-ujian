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

    /* LEFT HERO: foto + overlay gradasi hijau + teks & logo */
    .left-hero{
      position:relative;
      color:#fff;
      min-height:40vh;
      background:
        linear-gradient(135deg,
          rgba(14,165,165,.55),
          rgba(11,118,110,.55)
        ),
        url('<?= base_url('assets/img/login-side.png') ?>');
      background-position:center;
      background-size:cover;
      background-repeat:no-repeat;
      background-blend-mode:multiply;
    }
    @media (min-width: 992px){
      .left-hero{ min-height:100vh; }
    }

    /* Logo pojok kiri atas */
    .left-hero .brand-mark{
      position:absolute; top:18px; left:22px;
      display:flex; align-items:center; gap:10px;
      text-decoration:none; color:#fff;
    }
  
    .left-hero .brand-mark span{
      font-weight:800; font-size:16px; line-height:1;
      text-shadow:0 2px 8px rgba(0,0,0,.25);
    }

    /* Teks besar di tengah kiri */
    .left-hero .hero-caption{
      position:absolute; inset:0;
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      text-align:left; padding:24px;
    }
    .left-hero .hero-caption h1{
      font-weight:900; font-size:clamp(28px,2.8vw,44px); margin-bottom:.25rem;
      text-shadow:0 6px 22px rgba(0,0,0,.22);
    }
    .left-hero .hero-caption p{
      font-size:clamp(14px,1.2vw,18px); opacity:.95;
      text-shadow:0 4px 14px rgba(0,0,0,.2);
      margin:0;
    }
.left-hero .brand-mark img{
  height: 40px;
  width: auto;
  display: block;
  border: 2.5px solid #ffffff;     /* warna border */
  border-radius: 10px;              /* sudut membulat; ganti 50% untuk bulat penuh */
  padding: 4px;                     /* jarak logo ke border */
  background: rgba(243, 243, 243, 1);/* opsional: latar tipis */
  box-shadow: 0 4px 14px rgba(0,0,0,.18);
}
    /* Kartu kanan */
    .auth-card{ max-width:520px; width:100%; margin:0 auto; }
    .form-control{ border-radius:10px; height:48px; }
    .form-check-input{ width:1.1rem; height:1.1rem; }
    .shadow-soft{ box-shadow:0 10px 30px rgba(2,6,23,.06); }
    .small-muted{ color:#6b7280; font-size:.925rem; }
    .copyright a{ text-decoration:none; }

    /* Tombol brand */
    .brand-btn{
      background:var(--brand); border-color:var(--brand); color:#fff!important;
    }
    .brand-btn:hover,.brand-btn:focus{ filter:brightness(.95); color:#fff!important; }
  </style>
</head>
<body>
  <div class="container-fluid p-0">
    <div class="row g-0 split-wrap">

      <!-- LEFT: desktop -->
      <div class="col-12 col-lg-6 d-none d-lg-block p-0">
        <div class="left-hero">
          <!-- Logo kiri atas -->
          <a href="<?= base_url() ?>" class="brand-mark">
            <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="Logo FKG">
            <span>Universitas Hasanuddin <br/>Aplikasi eUjian Unhas</span>
          </a>
          <div class="hero-caption">
            <h1>E-Ujian CBT <br/>Fakultas Kedokteran Gigi
  </h1>
     
          </div>
        </div>
      </div>

      <!-- RIGHT: form -->
      <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5">
        <div class="auth-card px-3 px-md-4">
          <div class="card border-0 shadow-soft rounded-4">
            <div class="card-body p-4 p-md-5">
              <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">Selamat Datang</h4>
                <p class="small-muted mb-0">
                  Silahkan masukkan NIP/Username dan Kode Unik Ujian untuk masuk ke aplikasi.
                </p>
              </div>

              <form id="loginForm" autocomplete="off">
                <?= csrf_field() ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">NIP/Username</label>
                  <input type="text" name="username" class="form-control" placeholder="Masukan Username anda..." required>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold">Kode Unik Ujian</label>
                  <input type="password" name="exam_code" class="form-control" placeholder="Masukkan Kode Unik Ujian..." required>
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

          <div class="text-center mt-4 small-muted copyright">
            2025 © <a href="#" target="_blank">E-Ujian SkytelIndo</a>
          </div>
        </div>
      </div>

      <!-- LEFT: mobile -->
      <div class="col-12 d-lg-none p-0">
        <div class="left-hero" style="min-height:40vh">
          <a href="<?= base_url() ?>" class="brand-mark">
            <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="Logo FKG">
            <span>Universitas Hasanuddin</span>
            <span>Aplikasi eUjian Unhas</span>
          </a>
          <div class="hero-caption">
            <h1>E-Ujian CBT
  </h1>
            <p>Fakultas Kedokteran Gigi</p>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    function getCsrf(){ const m=document.querySelector('meta[name="csrf-token"]'); return m?m.getAttribute('content'):'<?= csrf_hash() ?>'; }
    function setCsrf(t){ let m=document.querySelector('meta[name="csrf-token"]'); if(!m){m=document.createElement('meta');m.setAttribute('name','csrf-token');document.head.appendChild(m);} m.setAttribute('content',t); const h=document.querySelector('input[name="<?= csrf_token() ?>"]'); if(h) h.value=t; }

    $('#loginForm').on('submit', function(e){
      e.preventDefault();
      $('#btnLogin').prop('disabled', true).text('Memproses...');
      $('#loginMsg').addClass('d-none').text('');

      $.ajax({
        url:'<?= base_url('auth/login') ?>',
        method:'POST',
        data:$(this).serialize(),
       
        dataType:'json'
      }).done(function(res){
        if(res.csrf_token){ setCsrf(res.csrf_token); }
        if(res.status==='ok'){
          window.location.href = res.redirect ?? '<?= base_url('dashboard') ?>';
        }else{
          $('#loginMsg').removeClass('d-none').text(res.message ?? 'Gagal login.');
        }
      }).fail(function(xhr){
        let msg='Terjadi kesalahan jaringan.'; if(xhr?.responseJSON?.message) msg=xhr.responseJSON.message;
        $('#loginMsg').removeClass('d-none').text(msg);
      }).always(function(){
        $('#btnLogin').prop('disabled', false).text('Masuk Sekarang');
      });
    });
    
  </script>
</body>
</html>
