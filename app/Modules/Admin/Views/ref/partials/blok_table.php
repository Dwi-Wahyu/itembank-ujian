<?php
$pages = max(1, (int)ceil(($total ?: 0) / ($per ?: 20)));
function qurl_part($p=[]){ return current_url().'?'.http_build_query(array_merge($_GET,$p)); }
?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:120px">Aksi</th>
          <th>Nama</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="2" class="text-center text-muted py-4">Tidak ada data.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary btn-edit" data-id="<?= $r['id'] ?>">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button class="btn btn-outline-danger btn-del"
                      data-url="<?= site_url('admin/master/blok/delete/'.$r['id']) ?>">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
          <td><?= esc($r['nama']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small text-muted">
      Menampilkan <?= count($rows)?(($page-1)*$per+1):0 ?>–<?= (($page-1)*$per + count($rows)) ?> dari <?= $total ?> entri
    </div>
    <nav>
      <?= render_pagination($page, $pages, function($p) { return qurl_part(['page' => $p]); }) ?>
    </nav>
  </div>
</div>
