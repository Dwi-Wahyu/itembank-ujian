<?php
$this->extend('\Modules\Admin\Views\layouts\admin');
$this->section('content');
use Modules\Auth\Libraries\Auth;
$me   = Auth::user();
$role = (int)($me['role_id'] ?? $me['id_role'] ?? -1);
$canReview = in_array($role, [0,4], true);
?>
<style>
  /* Galeri compact: thumbnail kecil + bar hapus merah */
  #galeri.galeri-compact { gap: 10px; }

  #galeri.galeri-compact .thumb{
    position: relative;
    width: 140px;                 /* kecil */
    border-radius: .5rem;
  }
  #galeri.galeri-compact .thumb .img-wrap{
    width: 100%;
    height: 86px;                 /* kecil */
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    overflow: hidden;
    background: #f8f9fa;
  }
  #galeri.galeri-compact .thumb img{
    width: 100%;
    height: 100%;
    object-fit: cover;            /* crop center */
    display: block;
  }
  #galeri.galeri-compact .thumb .btn-del{
    position: absolute;
    left: 0; right: 0; bottom: 0;
    border-radius: 0 0 .5rem .5rem;
    padding: .25rem .5rem;
    font-size: .75rem;
    line-height: 1rem;
  }
</style>

<!-- header + tombol kembali sama -->


<form id="formSoal" class="card card-body" method="post">

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/soal/teori') ?>">Soal Teori</a></li>
      <li class="breadcrumb-item active">Edit Soal</li>
    </ol>
  </nav>
  <div>
    <a href="<?= site_url('admin/soal/teori') ?>" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <!-- TOMBOL SIMPAN -->
    <button type="submit" class="btn btn-primary" form="formSoal" id="btnSave">
      <i class="bi bi-save2"></i> Simpan
    </button>
  </div>
</div>
  <?= csrf_field() ?>
<div class="row g-3">
  <!-- t1 / t2 / t3 -->
  <div class="col-md-6">
    <label class="form-label">Kompetensi Utama</label>
    <select name="t1" class="form-select js-select2" required>
      <option value="">- Pilih Kompetensi Utama -</option>
      <?php foreach ($komp as $x): ?>
        <option value="<?= $x['id'] ?>" <?= (int)($row['t1'] ?? 0) === (int)$x['id'] ? 'selected' : '' ?>>
          <?= esc($x['nama']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Departemen</label>
    <select name="departemen" class="form-select js-select2">
      <option value="">- Semua Departemen -</option>
      <?php foreach ($departemen as $x): ?>
        <option value="<?= $x['id'] ?>" <?= (int)($row['departemen'] ?? 0) === (int)$x['id'] ? 'selected' : '' ?>>
          <?= esc($x['nama']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Penyakit / Kelainan</label>
    <select name="t2" class="form-select js-select2" required>
      <option value="">- Pilih Penyakit / Kelainan -</option>
      <?php foreach ($sakit as $x): ?>
        <option value="<?= $x['id'] ?>" <?= (int)($row['t2'] ?? 0) === (int)$x['id'] ? 'selected' : '' ?>>
          <?= esc($x['nama']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Blok</label>
    <select name="blok" class="form-select js-select2">
      <option value="">- Semua Blok -</option>
      <?php foreach ($blok as $x): ?>
        <option value="<?= $x['id'] ?>" <?= (int)($row['blok'] ?? 0) === (int)$x['id'] ? 'selected' : '' ?>>
          <?= esc($x['nama']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Bidang Ilmu</label>
    <select name="t3" class="form-select js-select2" required>
      <option value="">- Pilih Bidang Ilmu -</option>
      <?php foreach ($bidang as $x): ?>
        <option value="<?= $x['id'] ?>" <?= (int)($row['t3'] ?? 0) === (int)$x['id'] ? 'selected' : '' ?>>
          <?= esc($x['nama']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Kode ujian (Select2 AJAX) + No. Register -->
  <div class="col-md-6">
    <label class="form-label">Kode Ujian</label>
    <select name="id_paket" class="form-select js-kode-teori" required>
      <?php if (!empty($row['id_paket'])): ?>
        <!-- tampilkan opsi awal agar Select2 punya nilai terpilih -->
        <option value="<?= esc($row['id_paket']) ?>" selected>
          <?= esc($paketText ?? $paketText) ?>
        </option>
      <?php endif; ?>
    </select>
    <div class="form-text">Cari “Kode – Nama – Tanggal” dari tabel <em>buat_teori</em>.</div>
  </div>

  <div class="col-md-6">
    <label class="form-label">No. Register</label>
    <input type="text" class="form-control" name="no_register"
           value="<?= esc($row['register'] ?? '') ?>" readonly>
    <div class="form-text">Format: {id_soal}/{kode_kom}/{kode_penyakit}/{kode_bidang}</div>
  </div>
</div>

    <!-- Vignette / Pertanyaan -->
    <textarea id="vignette" name="vignette" class="form-control" rows="4">
      <?= esc($row['vignette']) ?>
    </textarea>
    <textarea id="pertanyaan" name="pertanyaan" class="form-control" rows="4" required>
      <?= esc($row['pertanyaan']) ?>
    </textarea>

    <!-- Galeri file yang sudah ada -->
    <!-- GALERI GAMBAR -->
<label class="form-label d-flex align-items-center justify-content-between">
  <span>Gambar <small class="text-muted">(jpg/png ≤ 5MB, bisa lebih dari 1)</small></span>
  <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenUpload">
    <i class="bi bi-plus-circle"></i> Tambah
  </button>
</label>

<div id="galeri" class="d-flex flex-wrap galeri-compact">
  <?php if (!empty($files)): foreach ($files as $f): ?>
    <div class="thumb" data-fn="<?= esc($f['name']) ?>">
      <div class="img-wrap">
        <img src="<?= esc($f['url']) ?>" alt="">
      </div>
      <button type="button" class="btn btn-sm btn-danger w-100 btn-del">
        <i class="bi bi-x-circle me-1"></i> Hapus
      </button>
      <input type="hidden" name="files[]" value="<?= esc($f['name']) ?>">
    </div>
  <?php endforeach; endif; ?>
</div>

<div class="col-12"><strong>Opsi & Bobot</strong></div>

<?php // A ?>
<div class="col-md-6">
  <div class="input-group">
    <span class="input-group-text">A</span>
    <textarea name="a" rows="2" class="form-control"><?= esc($row['a'] ?? '') ?></textarea>
    <span class="input-group-text">Bobot</span>
    <input type="number" step="0.01" class="form-control" name="bobot_a" value="<?= esc($row['bobot_a'] ?? 0) ?>">
  </div>
</div>

<?php // B ?>
<div class="col-md-6">
  <div class="input-group">
    <span class="input-group-text">B</span>
    <textarea name="b" rows="2" class="form-control"><?= esc($row['b'] ?? '') ?></textarea>
    <span class="input-group-text">Bobot</span>
    <input type="number" step="0.01" class="form-control" name="bobot_b" value="<?= esc($row['bobot_b'] ?? 0) ?>">
  </div>
</div>

<?php // C ?>
<div class="col-md-6">
  <div class="input-group">
    <span class="input-group-text">C</span>
    <textarea name="c" rows="2" class="form-control"><?= esc($row['c'] ?? '') ?></textarea>
    <span class="input-group-text">Bobot</span>
    <input type="number" step="0.01" class="form-control" name="bobot_c" value="<?= esc($row['bobot_c'] ?? 0) ?>">
  </div>
</div>

<?php // D ?>
<div class="col-md-6">
  <div class="input-group">
    <span class="input-group-text">D</span>
    <textarea name="d" rows="2" class="form-control"><?= esc($row['d'] ?? '') ?></textarea>
    <span class="input-group-text">Bobot</span>
    <input type="number" step="0.01" class="form-control" name="bobot_d" value="<?= esc($row['bobot_d'] ?? 0) ?>">
  </div>
</div>

<?php // E ?>
<div class="col-md-6">
  <div class="input-group">
    <span class="input-group-text">E</span>
    <textarea name="e" rows="2" class="form-control"><?= esc($row['e'] ?? '') ?></textarea>
    <span class="input-group-text">Bobot</span>
    <input type="number" step="0.01" class="form-control" name="bobot_e" value="<?= esc($row['bobot_e'] ?? 0) ?>">
  </div>
</div>
<div class="col-md-6">
  <label class="form-label">Kunci Jawaban <span class="text-danger">*</span></label>
  <select name="kunci" class="form-select" required>
    <?php foreach(['A','B','C','D','E'] as $k): ?>
      <option value="<?= $k ?>" <?= ($row['kunci'] ?? '') === $k ? 'selected' : '' ?>><?= $k ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="col-12">
  <label class="form-label">Alasan</label>
  <textarea name="alasan" rows="2" class="form-control" <?= $canReview?'':'disabled' ?>><?= esc($row['alasan'] ?? '') ?></textarea>
</div>

<div class="col-12">
  <label class="form-label">Referensi</label>
  <textarea name="referensi" rows="2" class="form-control" <?= $canReview?'':'disabled' ?>><?= esc($row['referensi'] ?? '') ?></textarea>
</div>

<div class="col-md-4">
  <label class="form-label">Status</label>
  <select name="status" class="form-select js-select2" <?= $canReview?'':'disabled' ?>>
    <?php foreach (['draft','review','publish','reject'] as $s): ?>
      <option value="<?= $s ?>" <?= ($row['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>

</div>


    <!-- opsi/bobot: isi value dari $row['a'] dst + $row['bobot_a'] dst -->
    <!-- status/alasan/referensi sama seperti add (gunakan $row) -->

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
<!-- include select2 + summernote + CSS sama seperti add -->

<script>
(function(){
  const modalEl = document.getElementById('modalUpload');
  const modal   = modalEl ? new bootstrap.Modal(modalEl) : null;

  $('#vignette, #pertanyaan').summernote({ height:220, toolbar:[
    ['style',['bold','italic','underline','clear']],
    ['para',['ul','ol','paragraph']],
    ['insert',['link']],
    ['view',['codeview']]
  ]});

  $('.js-select2').select2({ width:'100%' });

// Select2 AJAX untuk "Kode Ujian"
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
  templateResult: item => item.loading ? item.text : (item.text || ''),
  templateSelection: item => item.text || item.id || ''
});

// Regenerate No. Register saat t1/t2/t3 berubah
function refreshRegisterEdit(){
  const t1 = $('[name="t1"]').val();
  const t2 = $('[name="t2"]').val();
  const t3 = $('[name="t3"]').val();
  if(!t1 || !t2 || !t3){
    $('input[name="no_register"]').val('');
    return;
  }
  $.get('<?= site_url('admin/soal/teori/reg-generate') ?>', { t1, t2, t3 })
    .done(function(res){
      if(res.status === 'ok'){
        $('input[name="no_register"]').val(res.register);
        if(res.csrf_token) window.__csrf = res.csrf_token;
      }
    });
}
$('[name="t1"],[name="t2"],[name="t3"]').on('change select2:select select2:clear', refreshRegisterEdit);
  // Upload modal + hapus preview: sama dengan view tambah (boleh copy persis)

  // Submit UPDATE
  $('#formSoal').on('submit', function(e){
    e.preventDefault();
    $('#vignette').val($('#vignette').summernote('code'));
    $('#pertanyaan').val($('#pertanyaan').summernote('code'));

    const $tok = $('#formSoal input[name="<?= csrf_token() ?>"]');
    if(window.__csrf) $tok.val(window.__csrf);

    const fd = new FormData(this);
    const $btn = $('button[form="formSoal"]').prop('disabled',true)
                  .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…');
    if(window.Loader) Loader.show();

    $.ajax({
      url:'<?= site_url('admin/soal/teori/update/'.$row['id']) ?>',
      method:'POST', data:fd, processData:false, contentType:false
    })
    .done(function(res){
      if(res && res.csrf_token) window.__csrf = res.csrf_token;
      if(res && res.status==='ok'){
        window.swalToast ? swalToast('Perubahan disimpan') : alert('Perubahan disimpan');
        setTimeout(()=> location.href='<?= site_url('admin/soal/teori') ?>', 500);
      }else{
        Swal.fire('Gagal', (res && res.message)||'Tidak dapat menyimpan', 'error');
      }
    })
    .fail(function(xhr){
      const msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || xhr.responseText || 'Gagal';
      Swal.fire('Gagal', msg, 'error');
    })
    .always(function(){
      $btn.prop('disabled',false).html('<i class="bi bi-save2"></i> Simpan');
      if(window.Loader) Loader.hide();
    });
  });
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
     // sebelumnya: <img ...> langsung
const html = `
  <div class="thumb" data-fn="${res.name}">
    <div class="img-wrap">
      <img src="${res.url}" alt="">
    </div>
    <button type="button" class="btn btn-sm btn-danger w-100 btn-del">
      <i class="bi bi-x-circle me-1"></i> Hapus
    </button>
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
})();
</script>
<?php $this->endSection(); ?>
