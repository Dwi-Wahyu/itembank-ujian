<table class="table table-hover mb-0" id="tblPeserta">
  <thead class="table-light">
    <tr>
      <th style="width:70px">Opsi</th>
      <th style="width:70px">No. Ujian</th>
      <th style="width:200px">Username / NIM</th>
      <th>Nama</th>
      <th style="width:160px">Kelas</th>
      <th style="width:160px">Nilai</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!empty($rows)): foreach ($rows as $r):
    $hasAttempt = !empty($r['has_attempt']);
    $benar      = (int)($r['benar'] ?? 0);
    $isLow      = $hasAttempt && $min > 0 && $benar < $min;   // << hanya merah jika sudah attempt
  ?>
    <tr class="<?= $isLow ? 'row-red' : '' ?>">
      <td>
        <button class="btn btn-sm <?= $isLow ? 'btn-light' : 'btn-outline-danger' ?> btn-hapus-peserta" data-id="<?= $r['mid'] ?>">
          <i class="bi bi-x-circle"></i>
        </button>
      </td>
      <td><?= esc($r['no_ujian']) ?></td>
      <td><?= esc($r['nim']) ?></td>
      <td><?= esc($r['nama']) ?></td>
      <td><?= esc($r['kelas'] ?? '-') ?></td>
      <td>
        <?php if ($hasAttempt): ?>
          <?= 'Benar = '.esc($benar).'<br>Salah = '.esc((int)$r['salah']).'<br>Kosong = '.esc((int)$r['kosong']) ?>
        <?php else: ?>
          <span class="text-muted">Belum mengerjakan</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data peserta untuk saat ini.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
