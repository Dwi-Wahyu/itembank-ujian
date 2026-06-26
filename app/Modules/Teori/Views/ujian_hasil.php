<!-- Modules/Teori/Views/ujian_hasil.php -->
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hasil Ujian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<meta http-equiv="Cache-Control" content="no-store" />
<style>
:root{ --brand:#0ea5a5; --ink:#0f172a; --muted:#64748b; }
body{background:#f6f7fb;color:var(--ink);font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
.wrap{max-width:880px;margin:40px auto;padding:0 16px}
.cardx{background:#fff;border-radius:20px;box-shadow:0 10px 30px rgba(2,6,23,.06);border:0}
.header-brand{display:flex;gap:12px;align-items:center;justify-content:center;margin-bottom:8px}
.header-brand img{height:40px}
.badge-pill{background:#e2f3f3;color:#0d9488;border:1px solid #99f6e4;border-radius:999px;padding:.35rem .75rem;font-weight:700}
.stat{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
@media(max-width:768px){ .stat{grid-template-columns:repeat(2,1fr);} }
.stat .box{border-radius:14px;padding:16px;text-align:center}
.stat .box h3{margin:0;font-weight:800}
.stat .ok{background:#ecfdf5;color:#065f46}
.stat .no{background:#fef2f2;color:#991b1b}
.stat .empty{background:#f8fafc;color:#334155}
.stat .total{background:#eef2ff;color:#3730a3}
.btn-brand{background:var(--brand);border-color:var(--brand);color:#fff}
  .stat{display:grid;grid-template-columns:repeat(6,1fr);gap:12px} /* dari 4 -> 6 kolom */
  @media(max-width:768px){ .stat{grid-template-columns:repeat(2,1fr);} }
  .stat .pass{background:#fff7ed;color:#9a3412}   /* oranye lembut */
  .stat .status-ok{background:#ecfdf5;color:#065f46}
  .stat .status-no{background:#fef2f2;color:#991b1b}
</style>
</head>
<body>
<div class="wrap">
  <div class="card cardx">
    <div class="card-body p-4 p-md-5">
      <div class="header-brand">
        <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="UNHAS">
        <div class="text-center">
          <div class="fw-bold">Universitas Hasanuddin</div>
          <div class="small text-muted">Aplikasi eUjian Unhas</div>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <h4 class="fw-bold mb-2">Hasil Ujian</h4>
        <span class="badge-pill">Kode: <?= esc($attempt['kode']) ?></span>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <table class="table table-sm">
            <tr><th style="width:140px">Nama</th><td><?= esc($mhs['nama'] ?? '-') ?></td></tr>
            <tr><th>NIM</th><td><?= esc($mhs['nim'] ?? '-') ?></td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm">
            <tr><th style="width:140px">Tanggal Ujian</th><td><?= esc($tanggal) ?></td></tr>
            <tr><th>Kode Ujian</th><td><?= esc($attempt['kode']) ?></td></tr>
             <tr><th>Hasil Ujian</th><td><?= esc($attempt['no_ujian']) ?></td></tr>
          </table>
        </div>
      </div>
<div class="stat mb-4">
  <div class="box ok">
    <div>Benar</div>
    <h3><?= (int)$sum['benar'] ?></h3>
  </div>
  <div class="box no">
    <div>Salah</div>
    <h3><?= (int)$sum['salah'] ?></h3>
  </div>
  <div class="box empty">
    <div>Kosong</div>
    <h3><?= (int)$sum['kosong'] ?></h3>
  </div>
  <div class="box total">
    <div>Total Soal</div>
    <h3><?= (int)$sum['total'] ?></h3>
  </div>

  <!-- ⬇️ Passing Grade -->
  <div class="box pass">
    <div>Passing Grade</div>
    <h3><?= (int)$min ?></h3>
  </div>

  <!-- ⬇️ Status -->
  <?php $isLulus = !empty($lulus); ?>
  <div class="box <?= $isLulus ? 'status-ok' : 'status-no' ?>">
    <div>Status</div>
    <h3><?= $isLulus ? 'Lulus' : 'Tidak Lulus' ?></h3>
  </div>
</div>

<!-- Info -->
<?php if ($isLulus): ?>
  <div class="alert alert-success">
    Selamat, Anda <strong>Lulus</strong>. Passing Grade: <?= (int)$min ?>, Jawaban Benar: <?= (int)$sum['benar'] ?>.
  </div>
<?php else: ?>
  <div class="alert alert-danger">
    Maaf, Anda <strong>Tidak Lulus</strong>. Passing Grade: <?= (int)$min ?>, Jawaban Benar: <?= (int)$sum['benar'] ?>.
  </div>
<?php endif; ?>



      <div class="alert alert-info">
        Ujian telah diselesaikan. Anda tidak dapat kembali ke halaman soal.
      </div>

      <div class="d-flex gap-2">
        <!-- <a class="btn btn-secondary" href="<?= base_url('teori/dashboard') ?>">Kembali ke Dashboard</a> -->
        <a class="btn btn-danger ms-auto" href="<?= base_url('teori/logout') ?>">Logout</a>
      </div>
    </div>
  </div>
</div>

<script>
// Blok back (best-effort) + no-cache
history.pushState(null,'',location.href);
window.addEventListener('popstate', ()=> history.pushState(null,'',location.href));
// Jangan tampilkan prompt; cukup cegah unload back-cache
window.addEventListener('beforeunload', (e)=>{ e.preventDefault(); e.returnValue=''; });
</script>
</body>
</html>
