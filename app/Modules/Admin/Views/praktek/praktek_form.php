<?php
$this->extend('\Modules\Admin\Views\layouts\admin');
$this->section('content');

use Modules\Auth\Libraries\Auth;
$me        = Auth::user();
$role      = (int)($me['role_id'] ?? $me['id_role'] ?? -1);
$canReview = in_array($role, [0,4], true);

$r    = $row ?? [];
$edit = !empty($r['id']);
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/soal/praktek') ?>">Soal Praktek</a></li>
      <li class="breadcrumb-item active"><?= $edit?'Ubah Soal':'Tambah Soal' ?></li>
    </ol>
  </nav>
  <div>
    <a href="<?= site_url('admin/soal/praktek') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    <button class="btn btn-primary" form="formPraktek"><i class="bi bi-save2"></i> Simpan</button>
  </div>
</div>

<form id="formPraktek" class="card card-body" method="post">
  <?= csrf_field() ?>
  <?php if($edit): ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><?php endif; ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">No. Register <span class="text-danger">*</span></label>
      <input class="form-control" name="register" value="<?= esc($r['register'] ?? '') ?>" placeholder="Contoh: 2/01.1/KP.1/P.00.1/2.08/2025" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Departemen</label>
      <select name="departemen" class="form-select">
        <option value="">- Semua -</option>
        <?php foreach($departemen as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (int)($r['departemen']??0)===$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Kompetensi Utama (t1)</label>
      <select name="t1" class="form-select">
        <option value="">- Pilih -</option>
        <?php foreach($komp as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (int)($r['t1']??0)==$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Penyakit/Kelainan (t2)</label>
      <select name="t2" class="form-select">
        <option value="">- Pilih -</option>
        <?php foreach($sakit as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (int)($r['t2']??0)==$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Sub 2</label>
      <input class="form-control" name="sub2" value="<?= esc($r['sub2'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Bidang Ilmu (t3)</label>
      <select name="t3" class="form-select">
        <option value="">- Pilih -</option>
        <?php foreach($bidang as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (int)($r['t3']??0)==$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">t4</label>
      <input class="form-control" name="t4" value="<?= esc($r['t4'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Blok</label>
      <select name="blok" class="form-select">
        <option value="">- Semua -</option>
        <?php foreach($blok as $x): ?>
          <option value="<?= $x['id'] ?>" <?= (int)($r['blok']??0)==$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12">
      <label class="form-label">Tujuan Pembelajaran</label>
      <textarea class="form-control" name="tujuan" rows="2"><?= esc($r['tujuan'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Skenario <span class="text-danger">*</span></label>
      <textarea id="skenario" class="form-control" name="skenario" rows="4" required><?= esc($r['skenario'] ?? '') ?></textarea>
    </div>

    <div class="col-md-6">
      <label class="form-label">Tugas K</label>
      <textarea class="form-control" name="tugas_k" rows="2"><?= esc($r['tugas_k'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Tugas P</label>
      <textarea class="form-control" name="tugas_p" rows="2"><?= esc($r['tugas_p'] ?? '') ?></textarea>
    </div>

    <div class="col-md-6">
      <label class="form-label">Instruksi</label>
      <textarea class="form-control" name="intruksi" rows="2"><?= esc($r['intruksi'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Peralatan</label>
      <textarea class="form-control" name="peralatan" rows="2"><?= esc($r['peralatan'] ?? '') ?></textarea>
    </div>

    <!-- Gambar -->
    <div class="col-12">
      <label class="form-label d-flex align-items-center justify-content-between">
        <span>Gambar <small class="text-muted">(jpg/png ≤ 5MB, bisa lebih dari 1)</small></span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenUpload">
          <i class="bi bi-plus-circle"></i> Tambah
        </button>
      </label>
      <div id="galeri" class="d-flex flex-wrap gap-2">
        <?php if (!empty($files)): foreach($files as $f): ?>
          <div class="thumb" data-fn="<?= esc($f['name']) ?>">
            <div class="imgbox"><img src="<?= esc($f['url']) ?>" alt=""></div>
            <button type="button" class="btn btn-sm btn-danger btn-del">Hapus</button>
            <input type="hidden" name="files[]" value="<?= esc($f['name']) ?>">
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Referensi & Status -->
    <div class="col-12">
      <label class="form-label">Referensi</label>
      <textarea class="form-control" name="referensi" rows="2" <?= $canReview?'':'disabled' ?>><?= esc($r['referensi'] ?? '') ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select" <?= $canReview?'':'disabled' ?>>
        <?php foreach(['draft','review','publish','reject'] as $s): ?>
          <option value="<?= $s ?>" <?= ($r['status'] ?? 'draft')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if(!$canReview): ?><div class="form-text">Pertama kali disimpan sebagai <b>draft</b>.</div><?php endif; ?>
    </div>
  </div>
</form>

<!-- MODAL UPLOAD -->
<div class="modal fade" id="modalUpload" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formUpload" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Unggah Media</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input class="form-control" type="file" name="media" accept=".jpg,.jpeg,.png,.webp" required>
        <img id="prevImg" class="img-fluid mt-3 d-none" alt="">
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Tambah</button>
      </div>
    </form>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>

<style>
  #galeri .thumb{position:relative;width:140px}
  #galeri .thumb .imgbox{width:100%;height:84px;overflow:hidden;border:1px solid #e5e7eb;border-radius:.5rem;background:#f8fafc}
  #galeri .thumb img{width:100%;height:100%;object-fit:cover}
  #galeri .thumb .btn-del{position:absolute;left:8px;right:8px;bottom:6px}
</style>
<script>
(function(){
  const modal = new bootstrap.Modal(document.getElementById('modalUpload'));
    const csrfKey = '<?= csrf_token() ?>';      // nama field token, mis. 'csrf_token_name'
  let   csrfVal = '<?= csrf_hash() ?>';  
 // editor
  // INIT pakai name (batasi ke <textarea>)
$('textarea[name="tujuan"], textarea[name="skenario"], textarea[name="tugas_k"], textarea[name="tugas_p"], textarea[name="intruksi"], textarea[name="peralatan"], textarea[name="referensi"]').summernote({
  height: 200,
  toolbar: [
    ['style', ['bold','italic','underline','clear']],
    ['para', ['ul','ol','paragraph']],
    ['insert', ['link']],
    ['view', ['codeview']]
  ]
});

  // upload ui
  $('#btnOpenUpload').on('click', ()=>{ $('#formUpload')[0].reset(); $('#prevImg').addClass('d-none').attr('src','');const $hidden = $('#formUpload input[name="'+csrfKey+'"]');
    $hidden.val(window.__csrf || csrfVal); modal.show(); });
  $(document).on('change','#formUpload input[type=file]', function(){ const f=this.files[0]; if(!f) return; $('#prevImg').attr('src', URL.createObjectURL(f)).removeClass('d-none'); });
  $('#formUpload').on('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);  if (window.__csrf) fd.set(csrfKey, window.__csrf);
    const $btn=$(this).find('button[type=submit]').prop('disabled',true).text('Mengunggah…');
    $.ajax({url:'<?= site_url('admin/soal/praktek/upload') ?>', method:'POST', data:fd, processData:false, contentType:false})
      .done(function(res){
     if (res.csrf_token) {
        window.__csrf = res.csrf_token;
        $('#formUpload input[name="'+csrfKey+'"]').val(res.csrf_token);
        $('form#formPraktek input[name="'+csrfKey+'"]').val(res.csrf_token);
      }
        if(res.status==='ok'){
          if (res.csrf_token) {
        window.__csrf = res.csrf_token;
        $('#formUpload input[name="'+csrfKey+'"]').val(res.csrf_token);
        $('form#formPraktek input[name="'+csrfKey+'"]').val(res.csrf_token);
      }
          $('#galeri').append(`<div class="thumb" data-fn="${res.name}">
              <div class="imgbox"><img src="${res.url}"></div>
              <button type="button" class="btn btn-sm btn-danger btn-del">Hapus</button>
              <input type="hidden" name="files[]" value="${res.name}">
            </div>`);
          modal.hide();
        }else{ Swal.fire('Gagal',res.message||'Upload gagal','error');}
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Upload gagal', 'error'))
      .always(()=> $btn.prop('disabled',false).html('<i class="bi bi-plus-circle"></i> Tambah'));
  });
  $(document).on('click','#galeri .btn-del',function(){
    const $t=$(this).closest('.thumb'), fn=$t.data('fn');
    Swal.fire({title:'Hapus gambar ini?',icon:'warning',showCancelButton:true}).then(r=>{
      if(!r.isConfirmed) return;
      $.post('<?= site_url('admin/soal/praktek/upload/delete') ?>',{'name':fn,'<?= csrf_token() ?>':'<?= csrf_hash() ?>'}).always(()=> $t.remove());
    });
  });

  // submit form ajax
  $('#formPraktek').on('submit', function(e){
    e.preventDefault();
    $('#skenario').val($('#skenario').summernote('code'));
    <?php if(!$canReview): ?> if(!$('input[name="status"]').length){ $(this).append('<input type="hidden" name="status" value="draft">'); } <?php endif; ?>
    const fd=new FormData(this); const id=$('input[name="id"]').val(); const url=id? '<?= site_url('admin/soal/praktek/update') ?>/'+id : '<?= site_url('admin/soal/praktek/simpan') ?>';
    const $btn=$('button[form="formPraktek"]').prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…');
    $.ajax({url, method:'POST', data:fd, processData:false, contentType:false})
      .done(res=>{
        if(res.csrf_token) window.__csrf=res.csrf_token;
        if(res.status==='ok'){ swalToast&&swalToast('Disimpan'); setTimeout(()=>location.href='<?= site_url('admin/soal/praktek') ?>',600); }
        else Swal.fire('Gagal', res.message||'Tidak dapat menyimpan', 'error');
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message||'Tidak dapat menyimpan', 'error'))
      .always(()=> $btn.prop('disabled',false).html('<i class="bi bi-save2"></i> Simpan'));
  });
})();
</script>
<?php $this->endSection(); ?>
