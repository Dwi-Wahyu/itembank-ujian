<?php
$pages = max(1, (int)ceil(($total ?: 0) / ($per ?: 10)));
function qp($p=[]){ return current_url().'?'.http_build_query(array_merge($_GET,$p)); }
?>
<div class="card">
   <div class="osce-table-scroll">   <!-- ⬅️ ganti table-responsive jadi ini -->
    <table class="table table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:110px">#</th>
          <th style="width:120px">Kode</th>
          <th style="width:200px">Soal</th>
          <th>NIP Pengawas</th>
          <th>Nama Pengawas</th>
          <th>Station</th>
          <th style="width:90px">Kode</th>
          <th style="width:90px">Waktu</th>
          <th style="width:160px">Created</th>
        </tr>
      </thead>

      <tbody>
        <?php if(empty($rows)): ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-4">Tidak ada data.</td>
          </tr>
        <?php else: foreach($rows as $r): ?>
          <tr>
          <td>
            <div class="btn-group btn-group-sm">
              <a class="btn btn-outline-info"
              href="<?= site_url('admin/osce-soal/detail/'.$r['id']) ?>"
              title="Detail">
              <i class="bi bi-eye"></i>
            </a>
            <button class="btn btn-outline-primary btn-edit" data-id="<?= $r['id'] ?>" title="Edit">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button class="btn btn-outline-danger btn-del"
            data-url="<?= site_url('admin/osce-soal/delete/'.$r['id']) ?>" title="Hapus">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </td>
      <td class="text-wrap"><?= esc($r['kode'] ?? ('#'.$r['osce_id'])) ?></td>
      <td class="text-wrap"><?= esc($r['soal_register'] ?? ('#'.$r['soal_id'])) ?></td>
      <td class="text-wrap"><?= esc($r['nip_pengawas']) ?></td>
      <td class="text-wrap"><?= esc($r['nama_pengawas']) ?></td>
      <td class="text-wrap"><?= esc($r['nama_station']) ?></td>
      <td><?= esc($r['kode']) ?></td>
      <td>
        <?php
          $parts = explode(':', $r['waktu'] ?? '00:00:00');
          $min   = ((int)$parts[0] * 60) + (int)($parts[1] ?? 0);
          echo $min . ' mnt';
        ?>
      </td>
      <td><small><?= esc($r['created_at']) ?></small></td>
    </tr>
  <?php endforeach; endif; ?>
</tbody>
</table>
</div>

<div class="card-body d-flex justify-content-end align-items-center flex-wrap gap-2">
  <div class="small text-muted">
    Menampilkan <?= count($rows)?(($page-1)*10+1):0 ?>–<?= (($page-1)*10 + count($rows)) ?> dari <?= $total ?> entri
  </div>
  <nav>
    <?= render_pagination($page, $pages, function($p) { return qp(['page'=>$p]); }) ?>
  </nav>
</div>
</div>
