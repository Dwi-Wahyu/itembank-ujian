<?php
$pages = max(1, (int)ceil(($total ?: 0) / ($per ?: 10)));
function qp($p=[]){ return current_url().'?'.http_build_query(array_merge($_GET,$p)); }
?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:260px">No. Register</th>
          <th style="width:100px">Format</th>
          <th>Skenario</th>
          <th style="width:120px">Instruksi</th>
          <th style="width:110px">Jlh. Aspek</th>
          <th style="width:120px">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
      <?php else: foreach($rows as $r): ?>
        <tr>
          <td class="text-wrap"><?= esc($r['register']) ?></td>
          <td>OSCE</td>
          <td class="text-wrap">
            <div class="clamp-2">
              <?= str_replace(' ', '&nbsp;', htmlspecialchars(mb_substr(strip_tags($r['skenario']), 0, 25))) ?>
              <?= (mb_strlen(strip_tags($r['skenario'])) > 25 ? '...' : '') ?>
            </div>
          </td>
          <td class="text-wrap">
            <div class="clamp-2">
              <?= str_replace(' ', '&nbsp;', htmlspecialchars(mb_substr(strip_tags($r['intruksi']), 0, 25))) ?>
              <?= (mb_strlen(strip_tags($r['intruksi'])) > 25 ? '...' : '') ?>
            </div>
          </td>
          <td>
            <a href="javascript:void(0)"
               class="badge bg-secondary js-aspek"
               data-soal-id="<?= (int)$r['id'] ?>"
               data-jlh="<?= (int)($r['aspek_jlh'] ?? 0) ?>">
               <?= (int)($r['aspek_jlh'] ?? 0) ?>
            </a>
          </td>
          <td class="text-center">
            <div class="dropdown">
              <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" 
                      data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <?php $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>
                <?php if (($me['role_id'] ?? -1) != 6): ?>
                  <li>
                    <a class="dropdown-item" title="Review" href="<?= site_url('admin/soal/praktek/review/'.$r['id']) . esc($qs) ?>">
                      <i class="bi bi-clipboard-check me-2"></i> Review
                    </a>
                  </li>
                <?php endif; ?>
                <?php if ($me['role_id']==0 || $me['role_id']==1): ?>
                  <li>
                    <a class="dropdown-item" title="Edit" href="<?= site_url('admin/soal/praktek/edit/'.$r['id']) . esc($qs) ?>">
                      <i class="bi bi-pencil-square me-2"></i> Ubah
                    </a>
                  </li>
                  <li>
                    <button class="dropdown-item btn-del text-danger" title="Hapus" 
                            data-url="<?= site_url('admin/soal/praktek/delete/'.$r['id']) ?>"
                            data-id="<?= (int)$r['id'] ?>">
                      <i class="bi bi-trash me-2"></i> Hapus
                    </button>
                  </li>
                <?php endif; ?>
              </ul>
            </div>
            <span class="badge bg-<?= $r['status_label']==='publish'?'success':($r['status_label']==='review'?'info':($r['status_label']==='reject'?'danger':'secondary')) ?>"><?= strtoupper($r['status_label'] ?: 'draft') ?></span>
          </td>
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
      <?= render_pagination($page, $pages, function($p) { return qp(['page' => $p]); }) ?>
    </nav>
  </div>
</div>

<style>
.clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
</style>
