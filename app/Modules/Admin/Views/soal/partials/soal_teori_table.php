<!-- app/Modules/Admin/Views/soal/partials/soal_teori_table.php -->
<?php
$pages = max(1, (int)ceil(($total ?: 0) / ($per ?: 20)));
function qurl_part($p=[]){ return current_url().'?'.http_build_query(array_merge($_GET,$p)); }
?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th class="col-reg">No. Register</th>
          <th class="col-format">Format</th>
          <th class="col-vignette">Vignette</th>
          <th class="col-tanya">Pertanyaan</th>
          <th class="col-status">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
      <?php else: foreach ($rows as $r):
        $fmt   = 'ABCDE';
        $badge = '<span class="badge bg-secondary">'.strtoupper($r['status'] ?: 'draft').'</span>';
      ?>
        <tr>
          <td class="col-reg"><?= esc($r['register'] ?: '-') ?></td>
          <td class="col-format"><?= $fmt ?></td>
          <td class="col-vignette"><div class="clamp-3"><?= esc(strip_tags($r['vignette'] ?? '')) ?></div></td>
          <td class="col-tanya"><div class="clamp-2"><?= esc(strip_tags($r['pertanyaan'] ?? '')) ?></div></td>
          <td class="col-status"><?= $badge ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <!-- pager tetap -->
  <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small text-muted">
      Menampilkan <?= count($rows)?(($page-1)*$per+1):0 ?>–<?= (($page-1)*$per + count($rows)) ?> dari <?= $total ?> entri
    </div>
    <nav>
      <?= render_pagination($page, $pages, function($p) { return qurl_part(['page'=>$p]); }) ?>
    </nav>
  </div>
</div>
