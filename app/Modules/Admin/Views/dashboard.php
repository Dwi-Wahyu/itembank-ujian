<?= $this->extend('\Modules\Admin\Views\layouts\admin') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4">
  <h1 class="page-title mb-3">Dashboard</h1>

  <!-- FILTER: Periode & Departemen -->
  <form class="card mb-3" method="get" action="">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Periode Mulai</label>
          <input type="date" class="form-control" name="start_date" value="<?= esc($start_date ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label mb-1">Periode Selesai</label>
          <input type="date" class="form-control" name="end_date" value="<?= esc($end_date ?? '') ?>">
        </div>
        
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Terapkan</button>
          <a class="btn btn-outline-secondary w-100" href="<?= current_url() ?>"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
        </div>
      </div>
      <?php if(!empty($start_date) || !empty($end_date) || !empty($idDepartemen)): ?>
      <div class="small text-muted mt-2">
        Filter aktif:
        <?php if(!empty($start_date) || !empty($end_date)): ?>
        Periode <?= esc($start_date ?: '…') ?> s/d <?= esc($end_date ?: '…') ?>
      <?php endif; ?>
      <?php if(!empty($idDepartemen)): ?>
        ; Departemen ID: <?= (int)$idDepartemen ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</form>

<!-- Kartu ringkasan -->
<div class="row g-3">
  <?php
  $cards = [
    ['Total Soal Teori',   $totalTeori   ?? 0, 'bi-pencil-square', 'card-soft-primary'],
    ['Total Soal Praktek', $totalPraktek ?? 0, 'bi-stethoscope',   'card-soft-violet'],
        ['Total Soal Terkirim',$totalTerkirim?? 0, 'bi-check2-circle', 'card-soft-green'],   // draft
        ['Total Soal Diterima',$totalDiterima?? 0, 'bi-download',      'card-soft-cyan'],    // publish
        ['Total Soal Ditolak', $totalDitolak ?? 0, 'bi-x-square',      'card-soft-orange'],  // reject
        ['Total Soal Revisi',  $totalRevisi  ?? 0, 'bi-arrow-repeat',  'card-soft-purple'],  // review
      ];
      foreach ($cards as [$label,$value,$icon,$klass]): ?>
        <div class="col-12 col-md-6 col-xl-4">
          <div class="card metric <?= $klass ?>">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="metric-icon"><i class="bi <?= $icon ?>"></i></div>
              <div>
                <div class="metric-label"><?= esc($label) ?></div>
                <div class="metric-value"><?= number_format((int)$value,0,',','.') ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- GRAFIK 1: TEORI -->
    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">Soal Teori — Jumlah per Departemen per Status</h6></div>
          <div class="card-body" style="height:420px;">
            <?php if (empty($chartTeori['labels'])): ?>
              <div class="text-center text-muted py-5">Belum ada data untuk ditampilkan.</div>
            <?php else: ?>
              <canvas id="barTeori"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- GRAFIK 2: PRAKTEK -->
    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">Soal Praktek OSCE — Jumlah per Departemen per Status</h6></div>
          <div class="card-body" style="height:420px;">
            <?php if (empty($chartPraktek['labels'])): ?>
              <div class="text-center text-muted py-5">Belum ada data untuk ditampilkan.</div>
            <?php else: ?>
              <canvas id="barPraktek"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- GRAFIK 3: JUMLAH SOAL PER DOSEN (TOP 20) -->
    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">Jumlah Soal Teori per Dosen</h6></div>
          <div class="card-body" style="height:460px;">
            <?php if (empty($chartDosenTeori['labels'])): ?>
              <div class="text-center text-muted py-5">Belum ada data.</div>
            <?php else: ?>
              <canvas id="barDosenTeori"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- GRAFIK: JUMLAH SOAL PRAKTEK PER DOSEN -->
    <div class="row g-3 mt-1">
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header"><h6 class="mb-0">Jumlah Soal Praktek OSCE per Dosen</h6></div>
          <div class="card-body" style="height:460px;">
            <?php if (empty($chartDosenPraktek['labels'])): ?>
              <div class="text-center text-muted py-5">Belum ada data.</div>
            <?php else: ?>
              <canvas id="barDosenPraktek"></canvas>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    (function(){
  // ===== TEORI =====
      const tLabels  = <?= json_encode($chartTeori['labels']  ?? []) ?>;
      const tDraft   = <?= json_encode($chartTeori['draft']   ?? []) ?>;
      const tReview  = <?= json_encode($chartTeori['review']  ?? []) ?>;
      const tPublish = <?= json_encode($chartTeori['publish'] ?? []) ?>;
      const tReject  = <?= json_encode($chartTeori['reject']  ?? []) ?>;

      if (tLabels.length) {
        const ctxT = document.getElementById('barTeori').getContext('2d');
        new Chart(ctxT, {
          type: 'bar',
          data: {
            labels: tLabels,
            datasets: [
              { label: 'Draft',   data: tDraft,   backgroundColor: '#9CA3AF' },
              { label: 'Review',  data: tReview,  backgroundColor: '#F59E0B' },
              { label: 'Publish', data: tPublish, backgroundColor: '#10B981' },
              { label: 'Reject',  data: tReject,  backgroundColor: '#EF4444' },
              ]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
              y: { beginAtZero: true, precision: 0 }
            },
            plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } }
          }
        });
      }

  // ===== PRAKTEK =====
      const pLabels  = <?= json_encode($chartPraktek['labels']  ?? []) ?>;
      const pDraft   = <?= json_encode($chartPraktek['draft']   ?? []) ?>;
      const pReview  = <?= json_encode($chartPraktek['review']  ?? []) ?>;
      const pPublish = <?= json_encode($chartPraktek['publish'] ?? []) ?>;
      const pReject  = <?= json_encode($chartPraktek['reject']  ?? []) ?>;

      if (pLabels.length) {
        const ctxP = document.getElementById('barPraktek').getContext('2d');
        new Chart(ctxP, {
          type: 'bar',
          data: {
            labels: pLabels,
            datasets: [
              { label: 'Draft',   data: pDraft,   backgroundColor: '#9CA3AF' },
              { label: 'Review',  data: pReview,  backgroundColor: '#F59E0B' },
              { label: 'Publish', data: pPublish, backgroundColor: '#10B981' },
              { label: 'Reject',  data: pReject,  backgroundColor: '#EF4444' },
              ]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
              y: { beginAtZero: true, precision: 0 }
            },
            plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } }
          }
        });
      }

  // ===== PER DOSEN (horizontal) =====
    // ===== per Dosen – TEORI (batang vertikal) =====
      const dtLabels = <?= json_encode($chartDosenTeori['labels'] ?? []) ?>;
      const dtCounts = <?= json_encode($chartDosenTeori['counts'] ?? []) ?>;
      if (dtLabels.length) {
        new Chart(document.getElementById('barDosenTeori').getContext('2d'), {
          type: 'bar',
          data: {
            labels: dtLabels,
            datasets: [{ label: 'Jumlah Soal Teori', data: dtCounts, backgroundColor: '#3B82F6' }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              x: { ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 } },
              y: { beginAtZero: true, precision: 0 }
            },
            plugins: { legend: { display: true, position: 'top' } }
          }
        });
      }

  // ===== per Dosen – PRAKTEK (batang vertikal) =====
      const dpLabels = <?= json_encode($chartDosenPraktek['labels'] ?? []) ?>;
      const dpCounts = <?= json_encode($chartDosenPraktek['counts'] ?? []) ?>;
      if (dpLabels.length) {
        new Chart(document.getElementById('barDosenPraktek').getContext('2d'), {
          type: 'bar',
          data: {
            labels: dpLabels,
            labels: dpLabels,
            datasets: [{ label: 'Jumlah Soal Praktek', data: dpCounts, backgroundColor: '#8B5CF6' }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
              x: { ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 } },
              y: { beginAtZero: true, precision: 0 }
            },
            plugins: { legend: { display: true, position: 'top' } }
          }
        });
      }
    })();
  </script>

  <?= $this->endSection() ?>
