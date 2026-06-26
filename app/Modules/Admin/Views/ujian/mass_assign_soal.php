<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>

<?php $this->section('content'); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= site_url('admin/ujian/teori') ?>">Ujian Teori</a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('admin/ujian/teori/detail/' . $uji['id']) ?>">Detail</a></li>
            <li class="breadcrumb-item active" aria-current="page">Mass Assign Soal</li>
        </ol>
    </nav>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filter Bank Soal</h5>
    </div>
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Cari Soal (Register/Vignette/Pertanyaan)</label>
                <input type="text" name="q" class="form-control" value="<?= esc($filters['q']) ?>" placeholder="Ketik kata kunci...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Departemen</label>
                <select name="departemen_id" class="form-select">
                    <option value="">- Semua Departemen -</option>
                    <?php foreach ($departemen as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filters['depId'] == $d['id'] ? 'selected' : '' ?>><?= esc($d['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Blok</label>
                <select name="blok_id" class="form-select">
                    <option value="">- Semua Blok -</option>
                    <?php foreach ($blok as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $filters['blokId'] == $b['id'] ? 'selected' : '' ?>><?= esc($b['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<form action="<?= site_url('admin/ujian/teori/mass-assign-soal-save/' . $uji['id']) ?>" method="POST">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Daftar Bank Soal (Published)</h5>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-all me-1"></i> Tugaskan Soal Terpilih</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px" class="text-center">
                            <input type="checkbox" class="form-check-input" id="checkAll">
                        </th>
                        <th style="width: 180px">No. Register</th>
                        <th>Vignette / Pertanyaan</th>
                        <th style="width: 120px" class="text-center">Status Paket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada soal yang ditemukan atau semua soal sudah terbit.
                            </td>
                        </tr>
                    <?php else: foreach ($rows as $r): 
                        $inThisPaket = (int)$r['id_paket'] === (int)$uji['id'];
                        $inOtherPaket = $r['id_paket'] && (int)$r['id_paket'] !== (int)$uji['id'];
                    ?>
                        <tr class="<?= $inThisPaket ? 'table-success' : '' ?>">
                            <td class="text-center">
                                <input type="hidden" name="visible_ids[]" value="<?= $r['id'] ?>">
                                <input type="checkbox" name="soal_ids[]" value="<?= $r['id'] ?>" 
                                       class="form-check-input row-check" <?= $inThisPaket ? 'checked' : '' ?>>
                            </td>
                            <td><code class="small"><?= esc($r['register']) ?></code></td>
                            <td>
                                <div class="fw-bold text-truncate" style="max-width: 500px;"><?= esc(strip_tags($r['vignette'])) ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 500px;"><?= esc(strip_tags($r['pertanyaan'])) ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($inThisPaket): ?>
                                    <span class="badge bg-success">Sudah di Sesi Ini</span>
                                <?php elseif ($inOtherPaket): ?>
                                    <span class="badge bg-warning text-dark">Di Sesi Lain</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Belum Terpakai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="small text-muted">
                Menampilkan <?= count($rows)?(($page-1)*$per+1):0 ?>–<?= (($page-1)*$per + count($rows)) ?> dari <?= $total ?> entri
            </div>
            <nav>
                <?= render_pagination($page, $pages, function($p) { 
                    $params = $_GET;
                    $params['page'] = $p;
                    return current_url() . '?' . http_build_query($params); 
                }) ?>
            </nav>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-all me-1"></i> Simpan Halaman Ini</button>
        </div>
    </div>
</form>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
document.getElementById('checkAll').addEventListener('change', function() {
    const checks = document.querySelectorAll('.row-check');
    checks.forEach(c => c.checked = this.checked);
});
</script>
<?php $this->endSection(); ?>
