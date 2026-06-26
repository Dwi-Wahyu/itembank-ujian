<?php
/** @var array $rows */
/** @var string $kode */
?>

<table class="table table-sm table-hover mb-0" id="tblPeserta">
  <thead class="table-light">
    <tr>
      <th style="width: 40px;">#</th>
      <th style="min-width: 120px;">NIM</th>
      <th style="min-width: 220px;">Nama</th>
      <th style="min-width: 120px;">Kelas</th>
      <th style="width: 220px;" class="text-end">Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr>
        <td colspan="5" class="text-center text-muted py-3">
          Belum ada peserta.
        </td>
      </tr>
    <?php else: ?>
      <?php $i = 1; foreach ($rows as $r): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($r['nim'] ?? '-') ?></td>
        <td><?= esc($r['nama'] ?? '-') ?></td>
        <td><?= esc($r['kelas'] ?? '-') ?></td>
        <td class="text-end">
          <!-- tombol lihat history station -->
          <button class="btn btn-xs btn-outline-info btn-history"
          data-station-id="<?= (int)$r['station_id'] ?>"
          data-mhs-id="<?= (int)($r['mid'] ?? 0) ?>">

          <i class="bi bi-clock-history me-1"></i> History
        </button>

        <!-- tombol hapus peserta -->
        <button type="button"
        class="btn btn-xs btn-outline-danger btn-hapus-peserta"
        data-id="<?= (int)($r['mid'] ?? 0) ?>">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  </tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
