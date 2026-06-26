<div class="p-3">


<div class="mb-3">
  <label class="form-label">Pencarian</label>
  <input type="text" id="mhsSearch" class="form-control" placeholder="Cari nama atau NIM..." value="<?= esc($q) ?>">
</div>

<div id="mhsListWrap" >  <!-- <<< area yang akan diberi overlay -->
  <div class="table-responsive border rounded">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:200px">Username / NIM</th>
          <th>Nama</th>
          <th style="width:60px" class="text-end">#</th>
        </tr>
      </thead>
      <tbody>
      <?php if(!empty($rows)): foreach($rows as $r): ?>
        <tr>
          <td><?= esc($r['nim']) ?></td>
          <td><?= esc($r['nama']) ?></td>
          <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-primary btn-pilih" data-id="<?= (int)$r['id'] ?>">
              <i class="bi bi-person-plus"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $pages = max(1, (int)ceil($total / $per));
    $page  = max(1, (int)$page);
  ?>
  <div class="d-flex justify-content-center gap-2 mt-3">
    <button type="button" class="btn btn-sm btn-outline-secondary mhs-page" data-page="<?= max(1,$page-1) ?>">Previous</button>
    <?php for($p=1;$p<=$pages && $p<=10;$p++): ?>
      <button type="button" class="btn btn-sm mhs-page <?= $p==$page?'btn-primary active':'btn-outline-primary' ?>" data-page="<?= $p ?>"><?= $p ?></button>
    <?php endfor; ?>
    <?php if($pages>10): ?>
      <span class="align-self-center">…</span>
      <button type="button" class="btn btn-sm btn-outline-primary mhs-page" data-page="<?= $pages ?>"><?= $pages ?></button>
    <?php endif; ?>
    <button type="button" class="btn btn-sm btn-outline-secondary mhs-page" data-page="<?= min($pages,$page+1) ?>">Next</button>
  </div>
</div>

</div>
