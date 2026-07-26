<?php
$this->extend('\Modules\Admin\Views\layouts\admin');
$this->section('content');
$row   = $row ?? [];
$files = $files ?? [];
$revs  = $revs ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-1">Detail Telaah Soal:</h4>
    <div class="small text-muted"><?= esc($row['register'] ?? '-') ?></div>
  </div>
  <a href="<?= site_url('admin/soal/teori') ?>" class="btn btn-secondary">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<hr class="mt-0">

<!-- RINGKASAN SOAL (readonly; label + text) -->
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="small text-muted">Vignette</div>
    <div class="border rounded p-2 bg-light"><?= $row['vignette'] ?></div>
  </div>
  <div class="col-12">
    <div class="small text-muted">Pertanyaan</div>
    <div class="border rounded p-2 bg-light"><?= $row['pertanyaan'] ?></div>
  </div>

  <div class="col-md-6">
    <div class="small text-muted">Opsi A (bobot: <?= (float)($row['bobot_a'] ?? 0) ?>)</div>
    <div class="border rounded p-2 bg-light"><?= $row['a'] ?></div>
  </div>
  <div class="col-md-6">
    <div class="small text-muted">Opsi B (bobot: <?= (float)($row['bobot_b'] ?? 0) ?>)</div>
    <div class="border rounded p-2 bg-light"><?= $row['b'] ?></div>
  </div>
  <div class="col-md-6">
    <div class="small text-muted">Opsi C (bobot: <?= (float)($row['bobot_c'] ?? 0) ?>)</div>
    <div class="border rounded p-2 bg-light"><?= $row['c'] ?></div>
  </div>
  <div class="col-md-6">
    <div class="small text-muted">Opsi D (bobot: <?= (float)($row['bobot_d'] ?? 0) ?>)</div>
    <div class="border rounded p-2 bg-light"><?= $row['d'] ?></div>
  </div>
  <div class="col-md-6">
    <div class="small text-muted">Opsi E (bobot: <?= (float)($row['bobot_e'] ?? 0) ?>)</div>
    <div class="border rounded p-2 bg-light"><?= $row['e'] ?></div>
  </div>
  <div class="col-md-6">
    <div class="small text-muted">Kunci Jawaban</div>
    <div class="border rounded p-2 bg-light fw-semibold"><?= esc($row['kunci'] ?? '-') ?></div>
  </div>

  <div class="col-12">
    <div class="small text-muted">Gambar</div>
    <div class="d-flex flex-wrap gap-2">
      <?php if ($files): foreach($files as $f): ?>
        <div class="border rounded" style="width:120px">
          <div style="width:100%;height:72px;overflow:hidden;background:#f8f9fa">
            <img src="<?= esc($f['url']) ?>" style="width:100%;height:100%;object-fit:cover">
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="text-muted">Tidak ada gambar.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- RIWAYAT + TOMBOL UBAH STATUS -->
 <?php if ($me['role_id']==0 || $me['role_id']==4) {
  # code...
  ?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="mb-0">Riwayat Telaah</h5>
  <button class="btn btn-primary" id="btnUbahStatus" data-soal="<?= (int)$row['id'] ?>">
    <i class="bi bi-pencil-square me-1"></i> Ubah Status / Tambah Telaah
  </button>
</div>
<?php } ?>
<ul class="list-group" id="listRiwayat">
    <li class="list-group-item d-flex justify-content-between align-items-center revisi-item"
        data-id="<?= (int)$row['id'] ?>">
      <div>
        <div class="fw-semibold"><?= strtoupper('Dibuat') ?></div>
        <div class="small text-muted">
          Dibuat: <?= esc($row['created_at'] ?: '-') ?> | Petugas : <?= esc($row['dosen'] ?: '-') ?>
        </div>
      </div>
      <i class="bi bi-chevron-right"></i>
    </li>
  <?php if (empty($revs)): ?>
    <li class="list-group-item text-muted">Belum ada telaah.</li>
  <?php else: foreach ($revs as $rv): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center revisi-item"
        data-id="<?= (int)$rv['id'] ?>">
      <div>
        <div class="fw-semibold"><?= strtoupper($rv['status'] ?: 'draft') ?></div>
        <div class="small text-muted">
          Dibuat: <?= esc($rv['created_at'] ?: '-') ?> | Petugas: <?= esc($rv['name'] ?: '-') ?>
        </div>
      </div>
      <i class="bi bi-chevron-right"></i>
    </li>
  <?php endforeach; endif; ?>
</ul>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<!-- MODAL UBAH STATUS / TULIS TELA'AH -->
<div class="modal fade" id="modalTelaah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form class="modal-content" id="formTelaah">
      <?= csrf_field() ?>
      <input type="hidden" name="soal_id" value="<?= (int)$row['id'] ?>">
      <div class="modal-header">
        <h5 class="modal-title">Ubah Status / Tambah Telaah</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Relevansi & kesesuaian</label>
            <input class="form-control" name="t1" placeholder="Sangat relevan / ...">
          </div>
          <div class="col-md-6">
            <label class="form-label">Bahasa yang digunakan</label>
            <input class="form-control" name="t2">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tingkat kesulitan soal</label>
            <input class="form-control" name="t3">
          </div>
          <div class="col-md-6">
            <label class="form-label">Vignette: Terdapat?</label>
            <input class="form-control" name="t4">
          </div>
          <div class="col-md-6">
            <label class="form-label">Vignette: Berfungsi?</label>
            <input class="form-control" name="t5">
          </div>
          <div class="col-md-6">
            <label class="form-label">Badan soal terlalu panjang/sulit/kompleks?</label>
            <input class="form-control" name="t6">
          </div>
          <div class="col-12">
            <label class="form-label">Lead In: Memenuhi close the option rules?</label>
            <input class="form-control" name="t7">
          </div>
          <div class="col-12">
            <label class="form-label">Pernyataan/pernyataan negatif</label>
            <input class="form-control" name="t8">
          </div>
          <div class="col-md-4">
            <label class="form-label">Kesalahan tata bahasa?</label>
            <input class="form-control" name="t9">
          </div>
          <div class="col-md-4">
            <label class="form-label">Istilah absolut?</label>
            <input class="form-control" name="t10">
          </div>
          <div class="col-md-4">
            <label class="form-label">Jawaban benar yang panjang?</label>
            <input class="form-control" name="t11">
          </div>

        <div class="col-md-4">
  <label class="form-label">Status</label>
  <select name="status" class="form-select" required>
    <?php 
      $statuses = [
        0 => 'draft',
        1 => 'review',
        2 => 'publish',
        3 => 'reject'
      ];
      foreach ($statuses as $val => $label): 
    ?>
      <option value="<?= $val ?>"><?= ucfirst($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>

        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL DETAIL RIWAYAT -->
<div class="modal fade" id="modalTelaahDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Telaah</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="wrapDetailTelaah"></div>
    </div>
  </div>
</div>

<script>
(function(){
  // tombol ubah status
  $('#btnUbahStatus').on('click', function(){
    new bootstrap.Modal('#modalTelaah').show();
  });

  // simpan telaah
  $('#formTelaah').on('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    if (window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);
    const $btn = $(this).find('button[type=submit]').prop('disabled',true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…');

    $.ajax({
      url: '<?= site_url('admin/soal/teori/revisi-save') ?>',
      method: 'POST', data: fd, processData:false, contentType:false
    }).done(function(res){
      if(res.csrf_token) window.__csrf = res.csrf_token;
      if(res.status==='ok'){
        swalToast && swalToast('Telaah tersimpan');
        loadRiwayat(<?= (int)$row['id'] ?>);
        bootstrap.Modal.getInstance(document.getElementById('modalTelaah')).hide();
      }else{
        Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
      }
    }).fail(xhr=>{
      Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menyimpan', 'error');
    }).always(()=> $btn.prop('disabled',false).html('<i class="bi bi-check2-circle me-1"></i> Simpan'));
  });

  // klik salah satu riwayat → tampil modal detail (readonly)
$(document).on('click', '.revisi-item', function(){
  const id = $(this).data('id');
  $.get('<?= site_url('admin/soal/teori/revisi-get') ?>/' + id)
    .done(function(res){
      if(res.status !== 'ok'){ 
        Swal.fire('Gagal', res.message || 'Tidak dapat memuat detail', 'error');
        return;
      }
      const d = res.data || {};
      const statusText = (d.status_name || d.status || 'draft').toString().toUpperCase();
      const reviewer   = d.reviewer || d.name || '-';

      // helper R(lbl,val, isYesNo=false) assumed already exists di kode kamu
      const html = `
        <div class="mb-3">
          <div class="small text-muted">Dibuat</div>
          <div class="fw-semibold">${d.created_at || '-'}</div>
          <div class="small text-muted mt-1">Petugas: <span class="fw-semibold">${reviewer}</span></div>
        </div>
        <hr>
        <div class="row g-3">
          ${R('Relevansi & kesesuaian', d.t1)}
          ${R('Bahasa yang digunakan', d.t2, true)}
          ${R('Tingkat kesulitan soal', d.t3, true)}
          ${R('Vignette – Terdapat?', d.t4, true)}
          ${R('Vignette – Berfungsi?', d.t5, true)}
          ${R('Badan soal terlalu panjang/sulit/kompleks?', d.t6, true)}
          ${R('Lead In – Close the option rules?', d.t7)}
          ${R('Pernyataan/pernyataan negatif', d.t8)}
          ${R('Option – Kesalahan tata bahasa?', d.t9, true)}
          ${R('Option – Istilah absolut?', d.t10, true)}
          ${R('Option – Jawaban benar yang panjang?', d.t11, true)}
          ${R('Status', statusText, true)}
        </div>`;
      $('#wrapDetailTelaah').html(html);
      new bootstrap.Modal('#modalTelaahDetail').show();
    })
    .fail(function(){
      Swal.fire('Gagal', 'Tidak dapat memuat detail', 'error');
    });
});


  // helper render readonly kotak
  function R(label,val,half){
    return `<div class="${half?'col-md-6':'col-12'}">
      <div class="small text-muted">${label}</div>
      <div class="border rounded p-2 bg-light">${val||'-'}</div>
    </div>`;
  }

  // refresh riwayat

function loadRiwayat(soalId){
  $.get('<?= site_url('admin/soal/teori/revisi-list') ?>/'+soalId)
    .done(function(res){
      if(res.status !== 'ok') return;

      if(res.csrf_token) window.__csrf = res.csrf_token;

      // ==== header "Dibuat" (persis seperti PHP) ====
      const listEl = document.getElementById('listRiwayat');
      const ds = listEl ? listEl.dataset : {};
      const soal = res.soal || {
        id: soalId,
        created_at: ds.created_at || '-',
        dosen: ds.dosen || '-'   // siapkan #listRiwayat data-dosen & data-created_at jika perlu
      };

      const headerHTML = `
        <li class="list-group-item d-flex justify-content-between align-items-center revisi-item"
            data-id="${Number(soal.id)||Number(soalId)||0}">
          <div>
            <div class="fw-semibold">${'DIBUAT'}</div>
            <div class="small text-muted">
              Dibuat: ${soal.created_at || '-'} | Petugas : ${soal.dosen || '-'}
            </div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </li>`;

      // ==== body items (persis seperti loop PHP) ====
      const items = Array.isArray(res.items) ? res.items : [];
      const bodyHTML = items.length
        ? items.map(r => `
            <li class="list-group-item d-flex justify-content-between align-items-center revisi-item"
                data-id="${Number(r.id)||0}">
              <div>
                <div class="fw-semibold">${String(r.status || 'draft').toUpperCase()}</div>
                <div class="small text-muted">
                  Dibuat: ${r.created_at || '-'} | Petugas: ${r.name || '-'}
                </div>
              </div>
              <i class="bi bi-chevron-right"></i>
            </li>
          `).join('')
        : `<li class="list-group-item text-muted">Belum ada telaah.</li>`;

      // render full list (header + body)
      $('#listRiwayat').html(headerHTML + bodyHTML);
    })
    .fail(function(){
      // optional: fallback UI
      $('#listRiwayat').html('<li class="list-group-item text-danger">Gagal memuat riwayat.</li>');
    });
}


})();
</script>
<?php $this->endSection(); ?>
