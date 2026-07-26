
<?php $rows = $rows ?? []; ?>
<?php if(empty($rows)): ?>
  <div class="text-muted">Belum ada history telaah.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light">
        <tr>
            <th style="width:200px">Reviewer</th>
          <th style="width:200px">Tanggal</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $h): ?>
        <tr class="js-rev" data-id="<?= (int)$h['id'] ?>" role="button" style="cursor:pointer">
         <td><?= esc($h['name']) ?></td>  
        <td><?= esc($h['created_at']) ?></td>
          <td>
            <?php
              $lbl = strtolower($h['status_label'] ?? 'draft');
              $cls = $lbl==='publish'?'success':($lbl==='review'?'info':($lbl==='reject'?'danger':'secondary'));
            ?>
            <span class="badge bg-<?= $cls ?>"><?= strtoupper($lbl) ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
