<div id="soalListWrap" class="p-3 bg-light border-bottom mb-3 sticky-top" style="top: -16px; z-index: 10;">
  <div class="row g-2">
    <div class="col-md-12">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="soalSearch" class="form-control" placeholder="Cari pertanyaan / vignette..." value="<?= esc($q) ?>">
      </div>
    </div>
  </div>
</div>

<div class="table-responsive p-3 pb-0" id="soalTableContent">
  <table class="table table-sm table-bordered table-hover align-middle mb-0" id="tblPilihSoal">
    <thead class="bg-light">
      <tr>
        <th width="40" class="text-center">#</th>
        <th>Pertanyaan</th>
        <th width="100" class="text-center">Pilih</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="3" class="text-center text-muted p-4">Tidak ada soal ditemukan.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-center small text-muted"><?= esc($r['id']) ?></td>
            <td>
              <div class="fw-bold"><?= esc(strip_tags((string)$r['vignette'])) ?></div>
              <div class="small"><?= esc(word_limiter(strip_tags((string)$r['pertanyaan']), 20)) ?></div>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-primary btn-pilih-soal" data-id="<?= $r['id'] ?>">
                Pilih
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($total > $per): ?>
  <div class="card-footer pb-3 bg-white pt-3 border-0">
    <nav aria-label="Page navigation">
      <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php 
          $max_pages = ceil($total / $per);
          $start     = max(1, $page - 2);
          $end       = min($max_pages, $page + 2);
        ?>
        <?php if ($page > 1): ?>
          <li class="page-item"><a class="page-link soal-page" href="#" data-page="<?= $page - 1 ?>">«</a></li>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= ($i === (int)$page) ? 'active' : '' ?>">
            <a class="page-link soal-page" href="#" data-page="<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($page < $max_pages): ?>
          <li class="page-item"><a class="page-link soal-page" href="#" data-page="<?= $page + 1 ?>">»</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
<?php endif; ?>
