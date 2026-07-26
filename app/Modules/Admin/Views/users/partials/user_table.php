<?php
// $rows, $routeBase tersedia
?>
<div class="table-responsive">
  <table class="table table-striped table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:110px">Aksi</th>
        <th>Nama</th>
        <th>Username</th>
        <th>Email</th>
        <th>Blok</th>
        <th>Departemen</th>
        <!-- <th>Kordinator</th> -->
   
        <!-- <th>Avatar</th> -->
      </tr>
    </thead>
    <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data.</td></tr>
    <?php else: foreach($rows as $r): ?>
      <tr>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary btn-edit" data-id="<?= (int)$r['id'] ?>">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button class="btn btn-outline-danger btn-del" data-id="<?= (int)$r['id'] ?>">
              <i class="bi bi-trash"></i>
            </button>
            <button class="btn btn-outline-warning btn-reset" data-id="<?= (int)$r['id'] ?>" title="Reset Password">
              <i class="bi bi-key"></i>
            </button>
          </div>
        </td>
        <td><?= esc($r['name']) ?></td>
        <td><?= esc($r['username']) ?></td>
        <td><?= esc($r['email']) ?></td>
        <td><?= esc($r['blok_nama'] ?: '-') ?></td>
        <td><?= esc($r['dep_nama'] ?: '-') ?></td>
        <!-- <td><?= esc($r['kordinator']) ?></td> -->
     
        <!-- <td>
          <?php if(!empty($r['thumb_avatar'])): ?>
            <img src="<?= esc($r['thumb_avatar']) ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:50%">
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td> -->
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
