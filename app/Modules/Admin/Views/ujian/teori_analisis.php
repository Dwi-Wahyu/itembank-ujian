<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); 
$min = (int)($uji['nilai_minimum'] ?? 0);
$sum = $summary ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= site_url('admin/ujian/teori') ?>">Ujian Teori</a></li>
      <li class="breadcrumb-item"><a href="<?= site_url('admin/ujian/teori/detail/' . $uji['id']) ?>"><?= esc($uji['nama']) ?></a></li>
      <li class="breadcrumb-item active" aria-current="page">Analisis Ujian</li>
    </ol>
  </nav>
  <div class="d-flex gap-2">
    <a href="<?= site_url('admin/ujian/teori/detail/' . $uji['id']) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
    </a>
    <a href="<?= site_url('admin/ujian/teori/laporan/' . rawurlencode($uji['kode'])) ?>" target="_blank" class="btn btn-outline-danger btn-sm">
      <i class="bi bi-file-earmark-pdf me-1"></i> Unduh PDF
    </a>
    <a href="<?= site_url('admin/ujian/teori/analisis/' . $uji['id'] . '?refresh=1') ?>" class="btn btn-primary btn-sm" onclick="Loader.show()">
      <i class="bi bi-arrow-clockwise me-1"></i> Hitung Ulang Analisis
    </a>
  </div>
</div>

<!-- INFO HEADER CARD -->
<div class="card shadow-sm mb-4 border-0">
  <div class="card-body">
    <div class="row g-3 align-items-center">
      <div class="col-md-7">
        <h4 class="mb-1 text-primary fw-bold">
          <i class="bi bi-bar-chart-line-fill me-2"></i><?= esc($uji['nama']) ?>
        </h4>
        <div class="text-muted small">
          <span class="me-3"><i class="bi bi-qr-code me-1"></i>Kode: <strong><?= esc($uji['kode']) ?></strong></span>
          <span class="me-3"><i class="bi bi-building me-1"></i><?= esc($dep) ?></span>
          <span class="me-3"><i class="bi bi-grid-3x3-gap me-1"></i><?= esc($blok) ?></span>
          <span><i class="bi bi-calendar3 me-1"></i><?= tgl_id($uji['tanggal']) ?></span>
        </div>
      </div>
      <div class="col-md-5 text-md-end">
        <span class="badge bg-secondary p-2 me-1">Passing Grade: <?= $min ?></span>
        <span class="badge bg-light text-muted p-2 border">
          <i class="bi bi-clock-history me-1"></i>Diperbarui: <?= !empty($sum['processed_at']) ? date('d/m/Y H:i', strtotime($sum['processed_at'])) : '-' ?>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- 4 KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small fw-semibold">RATA-RATA NILAI</div>
            <h2 class="fw-bold mb-0 text-primary"><?= number_format((float)($sum['nilai_rata_rata'] ?? 0), 1) ?></h2>
            <div class="text-muted small mt-1">
              Median: <strong><?= number_format((float)($sum['nilai_median'] ?? 0), 1) ?></strong> | SD: <?= number_format((float)($sum['standar_deviasi'] ?? 0), 1) ?>
            </div>
          </div>
          <div class="bg-primary-subtle text-primary p-3 rounded-circle">
            <i class="bi bi-calculator fs-3"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small fw-semibold">NILAI TERTINGGI & TERENDAH</div>
            <div class="d-flex align-items-baseline gap-2 mt-1">
              <span class="fs-2 fw-bold text-success"><?= (int)($sum['nilai_tertinggi'] ?? 0) ?></span>
              <span class="text-muted">/</span>
              <span class="fs-4 fw-bold text-danger"><?= (int)($sum['nilai_terendah'] ?? 0) ?></span>
            </div>
            <div class="text-muted small">
              Rentang (Range): <strong><?= (int)($sum['nilai_tertinggi'] ?? 0) - (int)($sum['nilai_terendah'] ?? 0) ?></strong> poin
            </div>
          </div>
          <div class="bg-success-subtle text-success p-3 rounded-circle">
            <i class="bi bi-trophy fs-3"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small fw-semibold">TINGKAT KELULUSAN</div>
            <h2 class="fw-bold mb-0 text-info"><?= number_format((float)($sum['persen_kelulusan'] ?? 0), 1) ?>%</h2>
            <div class="text-muted small mt-1">
              <strong><?= (int)($sum['total_lulus'] ?? 0) ?></strong> Lulus dari <?= (int)($sum['total_selesai'] ?? 0) ?> Peserta
            </div>
          </div>
          <div class="bg-info-subtle text-info p-3 rounded-circle">
            <i class="bi bi-patch-check fs-3"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small fw-semibold">PARTISIPASI CBT</div>
            <h2 class="fw-bold mb-0 text-warning"><?= (int)($sum['total_selesai'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">/ <?= (int)($sum['total_peserta'] ?? 0) ?></span></h2>
            <div class="text-muted small mt-1">
              Total Butir Soal: <strong><?= count($soalList) ?></strong> butir
            </div>
          </div>
          <div class="bg-warning-subtle text-warning p-3 rounded-circle">
            <i class="bi bi-people fs-3"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- HIGHLIGHTS: TOP BENAR VS TOP SALAH -->
<div class="row g-3 mb-4">
  <!-- Top 5 Soal Paling Banyak Benar -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-success">
          <i class="bi bi-check-circle-fill me-1"></i> Soal Paling Banyak Dijawab Benar
        </h6>
        <span class="badge bg-success-subtle text-success">Top 5</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($topBenar)): ?>
          <div class="p-4 text-center text-muted">Belum ada data analisis soal.</div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($topBenar as $tb): ?>
              <div class="list-group-item p-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div>
                    <span class="badge bg-success me-1">No. <?= $tb['nomor_urut'] ?></span>
                    <span class="badge bg-light text-dark border me-1">Kunci: <?= esc($tb['kunci']) ?></span>
                    <span class="text-muted small"><?= esc($tb['register'] ?? '') ?></span>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-success-subtle text-success fw-bold fs-6">
                      <?= number_format((float)$tb['tingkat_kesukaran'], 1) ?>% Benar
                    </span>
                    <div class="small text-muted mt-1"><?= (int)$tb['total_benar'] ?> / <?= (int)$tb['total_peserta'] ?> peserta</div>
                  </div>
                </div>
                <div class="small text-secondary mt-1 text-truncate" style="max-width: 90%;">
                  <?= esc(strip_tags($tb['pertanyaan'] ?: ($tb['vignette'] ?: 'Tidak ada teks pertanyaan'))) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Top 5 Soal Paling Banyak Salah -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-danger">
          <i class="bi bi-x-circle-fill me-1"></i> Soal Paling Banyak Dijawab Salah
        </h6>
        <span class="badge bg-danger-subtle text-danger">Top 5</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($topSalah)): ?>
          <div class="p-4 text-center text-muted">Belum ada data analisis soal.</div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($topSalah as $ts): 
              $katSalah = in_array(strtolower($ts['kategori_kesukaran'] ?? ''), ['sulit', 'sukar']) ? 'Sulit' : ucfirst($ts['kategori_kesukaran'] ?? '');
            ?>
              <div class="list-group-item p-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div>
                    <span class="badge bg-danger me-1">No. <?= $ts['nomor_urut'] ?></span>
                    <span class="badge bg-light text-dark border me-1">Kunci: <?= esc($ts['kunci']) ?></span>
                    <span class="badge bg-danger-subtle text-danger"><?= esc($katSalah) ?></span>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-danger-subtle text-danger fw-bold fs-6">
                      <?= (int)$ts['total_salah'] ?> Salah
                    </span>
                    <div class="small text-muted mt-1"><?= number_format((float)$ts['tingkat_kesukaran'], 1) ?>% Benar</div>
                  </div>
                </div>
                <div class="small text-secondary mt-1 text-truncate" style="max-width: 90%;">
                  <?= esc(strip_tags($ts['pertanyaan'] ?: ($ts['vignette'] ?: 'Tidak ada teks pertanyaan'))) ?>
                </div>
                <div class="d-flex gap-1 mt-2 small">
                  <span class="badge bg-light text-dark border">A: <?= (int)$ts['pilihan_a'] ?></span>
                  <span class="badge bg-light text-dark border">B: <?= (int)$ts['pilihan_b'] ?></span>
                  <span class="badge bg-light text-dark border">C: <?= (int)$ts['pilihan_c'] ?></span>
                  <span class="badge bg-light text-dark border">D: <?= (int)$ts['pilihan_d'] ?></span>
                  <span class="badge bg-light text-dark border">E: <?= (int)$ts['pilihan_e'] ?></span>
                  <span class="badge bg-light text-dark border">Kosong: <?= (int)$ts['pilihan_kosong'] ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- TABS: BUTIR SOAL & REKAP PESERTA -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom p-0">
    <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="analisisTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active py-3 fw-semibold" id="tab-soal" data-bs-toggle="tab" data-bs-target="#content-soal" type="button" role="tab">
          <i class="bi bi-journal-check me-1"></i> Analisis Butir Soal (<?= count($soalList) ?> Soal)
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link py-3 fw-semibold" id="tab-peserta" data-bs-toggle="tab" data-bs-target="#content-peserta" type="button" role="tab">
          <i class="bi bi-people-fill me-1"></i> Rekapitulasi & Peringkat Peserta (<?= count($pesertaList) ?> Peserta)
        </button>
      </li>
    </ul>
  </div>

  <div class="card-body p-0">
    <div class="tab-content" id="analisisTabsContent">
      <!-- TAB 1: BUTIR SOAL -->
      <div class="tab-pane fade show active p-3" id="content-soal" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tblSoalAnalisis">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;" class="text-center">No</th>
                <th style="min-width: 250px;">Butir Soal</th>
                <th style="width: 130px;" class="text-center">Benar / Salah</th>
                <th style="width: 140px;" class="text-center">Tingkat Kesulitan</th>
                <th style="min-width: 220px;" class="text-center">Distribusi Jawaban</th>
                <th style="width: 90px;" class="text-center">Daya Pembeda</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($soalList)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada butir soal yang dianalisis.</td></tr>
              <?php else: foreach ($soalList as $s): 
                $kat = strtolower($s['kategori_kesukaran'] ?? 'sedang');
                $isSulit = in_array($kat, ['sulit', 'sukar']);
                $badgeKat = match(true) {
                  $kat === 'mudah' => 'bg-success',
                  $isSulit => 'bg-danger',
                  default => 'bg-warning text-dark'
                };
                $labelKat = $isSulit ? 'Sulit' : ucfirst($kat);
                $kunci = strtoupper($s['kunci']);
              ?>
                <tr>
                  <td class="text-center fw-bold"><?= $s['nomor_urut'] ?></td>
                  <td>
                    <div class="fw-semibold text-dark text-truncate" style="max-width: 320px;" title="<?= esc(strip_tags($s['pertanyaan'])) ?>">
                      <?= esc(strip_tags($s['pertanyaan'] ?: ($s['vignette'] ?: 'Soal #'.$s['soal_id']))) ?>
                    </div>
                    <?php if (!empty($s['register'])): ?>
                      <div class="small text-muted"><?= esc($s['register']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <span class="text-success fw-bold"><?= (int)$s['total_benar'] ?></span>
                    <span class="text-muted">/</span>
                    <span class="text-danger fw-bold"><?= (int)$s['total_salah'] ?></span>
                    <?php if ((int)$s['total_kosong'] > 0): ?>
                      <div class="small text-muted">(Kosong: <?= (int)$s['total_kosong'] ?>)</div>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="fw-bold"><?= number_format((float)$s['tingkat_kesukaran'], 1) ?>%</div>
                    <span class="badge <?= $badgeKat ?> small"><?= esc($labelKat) ?></span>
                  </td>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                      <span class="badge <?= $kunci==='A' ? 'bg-success' : 'bg-light text-dark border' ?>">A: <?= (int)$s['pilihan_a'] ?></span>
                      <span class="badge <?= $kunci==='B' ? 'bg-success' : 'bg-light text-dark border' ?>">B: <?= (int)$s['pilihan_b'] ?></span>
                      <span class="badge <?= $kunci==='C' ? 'bg-success' : 'bg-light text-dark border' ?>">C: <?= (int)$s['pilihan_c'] ?></span>
                      <span class="badge <?= $kunci==='D' ? 'bg-success' : 'bg-light text-dark border' ?>">D: <?= (int)$s['pilihan_d'] ?></span>
                      <span class="badge <?= $kunci==='E' ? 'bg-success' : 'bg-light text-dark border' ?>">E: <?= (int)$s['pilihan_e'] ?></span>
                      <?php if ((int)$s['pilihan_kosong'] > 0): ?>
                        <span class="badge bg-light text-secondary border">K: <?= (int)$s['pilihan_kosong'] ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="text-center">
                    <?= $s['daya_pembeda'] !== null ? number_format((float)$s['daya_pembeda'], 2) : '-' ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 2: REKAP PESERTA & LEADERBOARD -->
      <div class="tab-pane fade p-3" id="content-peserta" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tblPesertaAnalisis">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;" class="text-center">Rank</th>
                <th style="width: 120px;">No. Ujian</th>
                <th style="width: 140px;">NIM</th>
                <th>Nama Mahasiswa</th>
                <th style="width: 100px;">Kelas</th>
                <th style="width: 80px;" class="text-center">Benar</th>
                <th style="width: 80px;" class="text-center">Salah</th>
                <th style="width: 80px;" class="text-center">Kosong</th>
                <th style="width: 90px;" class="text-center">Nilai</th>
                <th style="width: 110px;" class="text-center">Kelulusan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($pesertaList)): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada data peserta untuk ujian ini.</td></tr>
              <?php else: 
                $rank = 1;
                foreach ($pesertaList as $p): 
                  $isDone = !empty($p['has_attempt']);
                  $lulus = !empty($p['lulus']);
              ?>
                <tr>
                  <td class="text-center fw-bold">
                    <?php if ($rank === 1 && $isDone): ?>
                      <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill"></i> 1</span>
                    <?php elseif ($rank === 2 && $isDone): ?>
                      <span class="badge bg-secondary">2</span>
                    <?php elseif ($rank === 3 && $isDone): ?>
                      <span class="badge bg-dark-subtle text-dark border">3</span>
                    <?php else: ?>
                      <?= $rank ?>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($p['no_ujian']) ?></td>
                  <td><?= esc($p['nim']) ?></td>
                  <td class="fw-semibold"><?= esc($p['nama']) ?></td>
                  <td><?= esc($p['kelas'] ?: '-') ?></td>
                  <td class="text-center text-success fw-bold"><?= $isDone ? (int)$p['benar'] : '-' ?></td>
                  <td class="text-center text-danger"><?= $isDone ? (int)$p['salah'] : '-' ?></td>
                  <td class="text-center text-muted"><?= $isDone ? (int)$p['kosong'] : '-' ?></td>
                  <td class="text-center">
                    <?php if ($isDone): ?>
                      <span class="badge bg-primary fs-6"><?= (int)$p['nilai'] ?></span>
                    <?php else: ?>
                      <span class="text-muted small">Belum Ujian</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if (!$isDone): ?>
                      <span class="badge bg-light text-muted border">-</span>
                    <?php elseif ($min <= 0): ?>
                      <span class="badge bg-info-subtle text-info">Selesai</span>
                    <?php elseif ($lulus): ?>
                      <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Lulus</span>
                    <?php else: ?>
                      <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Lulus</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php 
                $rank++;
                endforeach; 
              endif; 
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>
