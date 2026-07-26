<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); $mode = $mode ?? 'add'; $row = $row ?? null; ?>
<style>
  #filePreview .thumb{
    position: relative; width: 120px; height: 90px; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;
    display:flex; align-items:center; justify-content:center; padding:4px; background:#fafafa;
  }
  #filePreview .thumb img{ width:100%; height:100%; object-fit:cover; }
  #filePreview .thumb .rm{
    position:absolute; top:4px; right:4px;
  }
  #filePreview .doc{
    font-size:12px; padding:6px; text-align:center; word-break:break-all;
  }
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/soal/praktek') ?>">Aspek</a></li>
      <li class="breadcrumb-item active"><?= $mode==='add'?'Tambah':'Edit' ?> Aspek</li>
    </ol>
  </nav>
  <div>
   <a href="<?= site_url('admin/soal/praktek') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    <?php if($mode==='edit'): ?>
      <button class="btn btn-success" form="formAspek"><i class="bi bi-check2-circle"></i> Update</button>
    <?php else: ?>
      <button class="btn btn-primary" form="formAspek"><i class="bi bi-save2"></i> Simpan</button>
    <?php endif; ?>
  </div>
</div>

<form id="formAspek" class="card card-body" method="post" enctype="multipart/form-data"   action="<?= $mode==='add' ? site_url('admin/aspek/create') : site_url('admin/aspek/update/'.(int)$row['id']) ?>">
  <?= csrf_field() ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Soal (Register)</label>
      <select name="soal_id" class="form-select" required>
        <option value="">- Pilih Soal -</option>
        <?php foreach(($soal??[]) as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($row['soal_id']??$id_soal)==$s['id']?'selected':'' ?>>
            <?= $s['id'] ?> – <?= esc($s['register']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">T1</label>
      <select name="t1" class="form-select">
        <option value="">-</option>
        <?php foreach(($komp??[]) as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (string)($row['t1']??'')===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">T2</label>
      <select name="t2" class="form-select">
        <option value="">-</option>
        <?php foreach(($sakit??[]) as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (string)($row['t2']??'')===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">T3</label>
      <select name="t3" class="form-select">
        <option value="">-</option>
        <?php foreach(($bidang??[]) as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (string)($row['t3']??'')===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

  <div class="col-12">
  <label class="form-label">Aspek</label>
  <textarea id="aspek" class="form-control" name="aspek" rows="5"><?= $row['aspek'] ?? '' ?></textarea>
</div>

<div class="col-12">
  <label class="form-label">Keterangan</label>
  <textarea id="keterangan" class="form-control" name="keterangan" rows="5"><?= $row['keterangan'] ?? '' ?></textarea>
</div>


   <div class="col-12">
  <label class="form-label">Lampiran (bisa banyak)</label>
  <input type="file"
         class="form-control"
         id="files"
         name="files[]"
         accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
         multiple>
  <div class="form-text">Maksimal beberapa file; gambar akan dibuat preview.</div>

  <!-- PREVIEW -->
  <div id="filePreview" class="mt-2 d-flex flex-wrap gap-2"></div>

  <?php
  // Tampilkan file lama (jika mode edit)
  if (!empty($row['file'])):
      $old = json_decode($row['file'], true);
      if (!is_array($old)) $old = [$row['file']]; // fallback jika masih string tunggal
  ?>
    <div class="mt-2">
      <div class="small text-muted mb-1">Lampiran saat ini:</div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($old as $fn): ?>
          <a class="btn btn-sm btn-outline-secondary"
             href="<?= base_url('uploads/aspek/'.$fn) ?>" target="_blank">
            <i class="bi bi-paperclip"></i> <?= esc($fn) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="form-text">File lama akan dipertahankan. File baru akan ditambahkan.</div>
    </div>
  <?php endif; ?>
</div>

  </div>
</form>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<script>
(function(){
   
    // aktifkan WYSIWYG untuk Aspek & Keterangan
    $('#aspek, #keterangan').summernote({
      height: 220,
      toolbar: [
        ['style', ['bold','italic','underline','clear']],
        ['para',  ['ul','ol','paragraph']],
        ['insert',['link']],
        ['view',  ['codeview']]
      ]
    });
  const form   = document.getElementById('formAspek');
  const $form  = $('#formAspek');
  const input  = document.getElementById('files');
  const box    = document.getElementById('filePreview');

  // Bucket file baru yang dipilih user (agar bisa remove sebelum submit)
  let dt = new DataTransfer();

  function isImage(file){
    return /^image\//i.test(file.type);
  }

  function extName(name){
    const i = name.lastIndexOf('.');
    return i>=0 ? name.slice(i+1).toLowerCase() : '';
  }

  function renderPreviews(){
    box.innerHTML = '';
    if (!dt.files.length){
      box.innerHTML = '<div class="text-muted small">Belum ada file baru dipilih.</div>';
      return;
    }
    Array.from(dt.files).forEach((f, idx)=>{
      const wrap = document.createElement('div');
      wrap.className = 'thumb';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-sm btn-danger rm';
      btn.innerHTML = '<i class="bi bi-x"></i>';
      btn.addEventListener('click', function(){
        // remove index dari DataTransfer
        const ndt = new DataTransfer();
        Array.from(dt.files).forEach((ff, i)=>{
          if (i !== idx) ndt.items.add(ff);
        });
        dt = ndt;
        input.files = dt.files;
        renderPreviews();
      });
      wrap.appendChild(btn);

      if (isImage(f)){
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.onload = ()=> URL.revokeObjectURL(img.src);
        wrap.appendChild(img);
      } else {
        const d  = document.createElement('div');
        d.className = 'doc';
        d.innerHTML = `<i class="bi bi-file-earmark-text"></i><br>${extName(f.name).toUpperCase()}<br>${f.name}`;
        wrap.appendChild(d);
      }
      box.appendChild(wrap);
    });
  }

  // Saat user memilih file → tambahkan ke dt, render preview
  input.addEventListener('change', function(){
    if (!this.files || !this.files.length) return;
    Array.from(this.files).forEach(f => dt.items.add(f));
    input.files = dt.files;
    renderPreviews();
    // reset input agar bisa pilih file yang sama lagi kalau perlu
    this.value = '';
  });

  // Submit via jQuery AJAX
  $form.on('submit', function(e){
    e.preventDefault();

    // Kirim semua field + file: gunakan FormData
    const fd = new FormData(form);

    // (files[] sudah otomatis ikut karena input.files = dt.files)
    // Tambahkan CSRF jika kamu simpan global (opsional; header X-CSRF juga sudah diset global)
    if (window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);

    const $btn = $(document.activeElement).is('button') ? $(document.activeElement) : $(this).find('button[type=submit]').first();
    const btnHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

    Loader && Loader.show();

    $.ajax({
      url: this.getAttribute('action'),
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false
    })
    .done(function(res, _t, xhr){
      window.refreshCsrfFrom && window.refreshCsrfFrom(res, xhr);
      if (res && res.status === 'ok'){
        swalToast && swalToast('Data tersimpan');
        // redirect balik ke list
        setTimeout(()=> { location.href = '<?= site_url('admin/ujian/praktek') ?>'; }, 300);
      } else {
        Swal.fire('Gagal', (res && res.message) || 'Tidak dapat menyimpan', 'error');
      }
    })
    .fail(function(xhr){
      window.refreshCsrfFrom && window.refreshCsrfFrom(null, xhr);
      const msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || xhr.responseText || 'Gagal menyimpan';
      Swal.fire('Gagal', msg, 'error');
    })
    .always(function(){
      Loader && Loader.hide();
      $btn.prop('disabled', false).html(btnHtml);
    });
  });

  // Render awal (jika tidak ada file baru)
  renderPreviews();
})();
</script>

<?php $this->endSection(); ?>
