<div class="table-responsive">
  <table class="table table-sm table-bordered table-hover align-middle mb-0" id="tblSoalPaket">
    <thead class="bg-light">
      <tr>
        <th width="40" class="text-center">No</th>
        <th>Pertanyaan</th>
        <th width="100" class="text-center">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="3" class="text-center text-muted p-4">Belum ada soal ditambahkan.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td class="text-center"><?= $i + 1 ?></td>
            <td>
              <div class="fw-bold"><?= esc(strip_tags((string)$r['vignette'])) ?></div>
              <div class="small text-muted"><?= esc(word_limiter(strip_tags((string)$r['pertanyaan']), 20)) ?></div>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-danger btn-hapus-soal" data-id="<?= $r['id'] ?>" title="Hapus dari paket">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
