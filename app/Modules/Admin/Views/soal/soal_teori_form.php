<?php
// Modules/Admin/Views/soal/soal_teori_add.php
$this->extend('\Modules\Admin\Views\layouts\admin');
$this->section('content');

use Modules\Auth\Libraries\Auth;
$me        = Auth::user();
$role      = (int)($me['role_id'] ?? $me['id_role'] ?? -1);
$canReview = in_array($role, [0,4], true);
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/soal/teori') ?>">Soal Teori</a></li>
      <li class="breadcrumb-item active">Tambah Soal</li>
    </ol>
  </nav>
  <div>
    <a href="<?= site_url('admin/soal/teori') ?>" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <button class="btn btn-primary" form="formSoal">
      <i class="bi bi-save2"></i> Simpan
    </button>
  </div>
</div>

<form id="formSoal" class="card card-body" method="post">
  <?= csrf_field() ?>

  <div class="row g-3">
    <!-- t1 / t2 / t3 -->
    <div class="col-md-6">
      <label class="form-label">Kompetensi Utama</label>
      <select name="t1" class="form-select js-select2" required>
        <option value="">- Pilih Kompetensi Utama -</option>
        <?php foreach ($komp as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Departemen</label>
      <select name="departemen" class="form-select js-select2">
        <option value="">- Semua Departemen -</option>
        <?php foreach ($departemen as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Penyakit / Kelainan</label>
      <select name="t2" class="form-select js-select2" required>
        <option value="">- Pilih Penyakit / Kelainan -</option>
        <?php foreach ($sakit as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Blok</label>
      <select name="blok" class="form-select js-select2">
        <option value="">- Semua Blok -</option>
        <?php foreach ($blok as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Bidang Ilmu</label>
      <select name="t3" class="form-select js-select2" required>
        <option value="">- Pilih Bidang Ilmu -</option>
        <?php foreach ($bidang as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Kode ujian (select2 ajax) + No.Register (otomatis) -->
    <div class="col-md-6">
      <label class="form-label">Kode Ujian</label>
      <select name="id_paket" class="form-select js-kode-teori" required></select>
      <div class="form-text">Cari “Kode – Nama – Tanggal” dari tabel <em>buat_teori</em>.</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">No. Register</label>
      <input type="text" class="form-control" name="no_register" readonly>
      <div class="form-text">Format: {id_soal}/{kode_kom}/{kode_penyakit}/{kode_bidang}</div>
    </div>

    <!-- Vignette & Pertanyaan -->
    <div class="col-12">
      <label class="form-label">Vignette</label>
      <textarea id="vignette" name="vignette" class="form-control" rows="4"></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
      <textarea id="pertanyaan" name="pertanyaan" class="form-control" rows="4" required></textarea>
    </div>

    <!-- GALERI GAMBAR -->
    <div class="col-12">
      <label class="form-label d-flex align-items-center justify-content-between">
        <span>Gambar <small class="text-muted">(jpg/png ≤ 5MB, bisa lebih dari 1)</small></span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenUpload">
          <i class="bi bi-plus-circle"></i> Tambah
        </button>
      </label>
      <div id="galeri" class="d-flex flex-wrap gap-3"></div>
    </div>

    <!-- Opsi & Bobot -->
    <div class="col-12"><strong>Opsi & Bobot</strong></div>
    <?php foreach (['a','b','c','d','e'] as $opt): ?>
      <div class="col-md-6">
        <div class="input-group">
          <span class="input-group-text text-uppercase"><?= $opt ?></span>
          <textarea name="<?= $opt ?>" rows="2" class="form-control" placeholder="Opsi <?= strtoupper($opt) ?>"></textarea>
          <span class="input-group-text">Bobot</span>
          <input type="number" step="0.01" class="form-control" name="bobot_<?= $opt ?>" value="0">
        </div>
      </div>
    <?php endforeach; ?>

    <div class="col-md-6">
      <label class="form-label">Kunci Jawaban <span class="text-danger">*</span></label>
      <select name="kunci" class="form-select" required>
        <option value="">- Pilih -</option>
        <?php foreach(['A','B','C','D','E'] as $k): ?><option><?= $k ?></option><?php endforeach; ?>
      </select>
    </div>

    <!-- Alasan / Referensi / Status -->
    <div class="col-12">
      <label class="form-label">Alasan</label>
      <textarea name="alasan" rows="2" class="form-control" <?= $canReview?'':'disabled' ?>></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Referensi</label>
      <textarea name="referensi" rows="2" class="form-control" <?= $canReview?'':'disabled' ?>></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select js-select2" <?= $canReview?'':'disabled' ?>>
        <?php foreach (['draft','review','publish','reject'] as $s): ?>
          <option value="<?= $s ?>" <?= $s==='draft'?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!$canReview): ?><div class="form-text">Pertama kali disimpan sebagai <b>draft</b>.</div><?php endif; ?>
    </div>
  </div>
</form>

<!-- MODAL UPLOAD -->
<div class="modal fade" id="modalUpload" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formUpload" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Unggah Media</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input class="form-control" type="file" name="media" accept=".jpg,.jpeg,.png" required>
        <img id="prevImg" alt="" class="img-fluid mt-3 d-none"/>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Tambah
        </button>
      </div>
    </form>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>

<style>
  #galeri .thumb{position:relative;width:170px}
  #galeri .thumb img{width:100%;height:110px;object-fit:cover;border-radius:.5rem;border:1px solid #e5e7eb}
  #galeri .thumb .btn-del{position:absolute;left:10px;right:10px;bottom:8px}
</style>

<script>
(function(){
  const modalEl = document.getElementById('modalUpload');
  const modal   = new bootstrap.Modal(modalEl);

  // editor
  $('#vignette, #pertanyaan').summernote({
    height: 220,
    toolbar: [
      ['style', ['bold','italic','underline','clear']],
      ['para', ['ul','ol','paragraph']],
      ['insert', ['link']],
      ['view', ['codeview']]
    ]
  });

  // select2 standar
  $('.js-select2').select2({ width:'100%' });

  // select2 AJAX untuk "Kode Ujian"
  $('.js-kode-teori').select2({
    width:'100%',
    placeholder:'Ketik nama/kode ujian…',
    ajax:{
      url:'<?= site_url('admin/soal/teori/cari-kode') ?>',
      dataType:'json',
      delay:200,
      data:params=>({ q: params.term || '' }),
      processResults:data=>({ results: data.items || [] })
    },
    minimumInputLength:0,
    templateResult: (item)=> item.loading ? item.text : (item.text || ''),
    templateSelection:(item)=> item.text || item.id || ''
  });

  // ===== No. Register otomatis dari t1/t2/t3 =====
  function refreshRegister(){
    const t1 = $('[name="t1"]').val();
    const t2 = $('[name="t2"]').val();
    const t3 = $('[name="t3"]').val();
    if(!t1 || !t2 || !t3){
      $('input[name="no_register"]').val('');
      return;
    }
    $.get('<?= site_url('admin/soal/teori/reg-generate') ?>', {t1, t2, t3})
      .done(function(res){
        if(res.status==='ok'){
          $('input[name="no_register"]').val(res.register);
          if(res.csrf_token) window.__csrf = res.csrf_token;
        }else{
          $('input[name="no_register"]').val('');
        }
      });
  }
  $('[name="t1"],[name="t2"],[name="t3"]').on('change select2:select select2:clear', refreshRegister);

  // ===== Upload gambar (modal) =====
  $('#btnOpenUpload').on('click', function(){
    $('#formUpload')[0].reset();
    $('#prevImg').addClass('d-none').attr('src','');
    modal.show();
  });

  $(document).on('change', '#formUpload input[type=file]', function(){
    const f = this.files[0]; if(!f) return;
    const url = URL.createObjectURL(f);
    $('#prevImg').attr('src', url).removeClass('d-none');
  });

  $('#formUpload').on('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    if(window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);
    const $b = $(this).find('button[type=submit]').prop('disabled',true).text('Mengunggah…');

    $.ajax({ url:'<?= site_url('admin/soal/teori/upload') ?>', method:'POST', data:fd, processData:false, contentType:false })
      .done(function(res){
        if(res.csrf_token) window.__csrf = res.csrf_token;
        if(res.status==='ok'){
          const html = `
            <div class="thumb" data-fn="${res.name}">
              <img src="${res.url}" alt="">
              <button type="button" class="btn btn-sm btn-danger btn-del">Hapus</button>
              <input type="hidden" name="files[]" value="${res.name}">
            </div>`;
          $('#galeri').append(html);
          modal.hide();
          window.swalToast ? swalToast('Gambar ditambahkan') : console.log('Gambar ditambahkan');
        }else{
          Swal.fire('Gagal', res.message || 'Upload gagal', 'error');
        }
      })
      .fail(xhr=>{
        Swal.fire('Gagal', xhr?.responseJSON?.message || 'Upload gagal', 'error');
      })
      .always(()=> $b.prop('disabled',false).html('<i class="bi bi-plus-circle"></i> Tambah'));
  });

  // hapus preview
  $(document).on('click','#galeri .btn-del', function(){
    const $t = $(this).closest('.thumb');
    const fn = $t.data('fn');
    Swal.fire({title:'Hapus gambar ini?',icon:'warning',showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.post('<?= site_url('admin/soal/teori/upload/delete') ?>', {
          name: fn, '<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>')
        }).always(()=> $t.remove());
      });
  });

  // ===== SIMPAN VIA AJAX =====
  $('#formSoal').on('submit', function(e){
    e.preventDefault();

    // commit summernote ke textarea
    $('#vignette').val($('#vignette').summernote('code'));
    $('#pertanyaan').val($('#pertanyaan').summernote('code'));

    // paksa draft jika bukan admin/reviewer
    <?php if (!$canReview): ?>
    if(!$('input[name="status"]').length){
      $(this).append('<input type="hidden" name="status" value="draft">');
    }
    <?php endif; ?>

    // update token hidden jika ada token baru
    const $tok = $('#formSoal input[name="<?= csrf_token() ?>"]');
    if(window.__csrf) $tok.val(window.__csrf);

    const fd = new FormData(this);

    const $btn = $('button[form="formSoal"]').prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…');
    if(window.Loader) Loader.show();

    $.ajax({
      url:'<?= site_url('admin/soal/teori/simpan') ?>',
      method:'POST', data:fd, processData:false, contentType:false
    })
    .done(function(res){
      if(res && res.csrf_token) window.__csrf = res.csrf_token;
      if(res && res.status==='ok'){
        window.swalToast ? swalToast('Soal tersimpan') : console.log('Soal tersimpan');
        setTimeout(()=> location.href = '<?= site_url('admin/soal/teori') ?>', 600);
      }else{
        Swal.fire('Gagal', (res && res.message) || 'Tidak dapat menyimpan', 'error');
      }
    })
    .fail(function(xhr){
      const msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || xhr.responseText || 'Gagal menyimpan';
      Swal.fire('Gagal', msg, 'error');
    })
    .always(function(){
      $btn.prop('disabled', false).html('<i class="bi bi-save2"></i> Simpan');
      if(window.Loader) Loader.hide();
    });
  });

})();
</script>
<?php $this->endSection(); ?>
