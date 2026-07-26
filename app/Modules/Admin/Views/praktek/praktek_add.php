<?php
// === Modules/Admin/Views/soal_praktek/praktek_add.php ===
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
      <li class="breadcrumb-item"><a href="<?= site_url('admin/soal/praktek') ?>">Soal Praktek</a></li>
      <li class="breadcrumb-item active">Tambah Soal</li>
    </ol>
  </nav>
  <div>
    <a href="<?= site_url('admin/soal/praktek') ?>" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <button class="btn btn-primary" form="formPraktek">
      <i class="bi bi-save2"></i> Simpan
    </button>
  </div>
</div>

<form id="formPraktek" class="card card-body" method="post">
  <?= csrf_field() ?>

  <div class="row g-3">
    <!-- T1 / T2 / SUB2 / T3 / T4 -->
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
      <label class="form-label">Kelompok Penyakit</label>
      <select name="t2" class="form-select js-select2">
        <option value="">- Pilih Kelompok Penyakit -</option>
        <?php foreach ($kelompok as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Penyakit / Kelainan</label>
      <select name="sub2" class="form-select js-select2" required>
        <option value="">- Pilih Penyakit / Kelainan -</option>
        <?php foreach ($penyakit as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Ranah Keterampilan Teknis</label>
      <select name="t3" class="form-select js-select2">
        <option value="">- Pilih Ranah Keterampilan Teknis -</option>
        <?php foreach ($ranah as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Bidang Ilmu</label>
      <select name="t4" class="form-select js-select2" required>
        <option value="">- Pilih Bidang Ilmu -</option>
        <?php foreach ($bidang as $x): ?>
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
      <label class="form-label">Blok</label>
      <select name="blok" class="form-select js-select2">
        <option value="">- Semua Blok -</option>
        <?php foreach ($blok as $x): ?>
          <option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Kode Ujian (OSCE) + No.Register (auto seperti teori) -->
    <!-- <div class="col-md-6">
      <label class="form-label">Kode Ujian (OSCE)</label>
      <select name="kode_ujian" class="form-select js-kode-praktek" required></select>
      <div class="form-text">Cari “Kode – Nama – Tanggal” dari tabel <em>osce</em>.</div>
    </div> -->

    <div class="col-md-6">
      <label class="form-label">No. Register</label>
      <input type="text" class="form-control" name="register" readonly>
      <div class="form-text">Format: {id_soal}/{kode_kom}/{kode_penyakit}/{kode_bidang}</div>
    </div>

    <!-- Editor -->
    <div class="col-12">
      <label class="form-label">Tujuan</label>
      <textarea id="tujuan" name="tujuan" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Skenario</label>
      <textarea id="skenario" name="skenario" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Tugas Peserta</label>
      <textarea id="tugas_k" name="tugas_k" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Tugas Penguji</label>
      <textarea id="tugas_p" name="tugas_p" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Instruksi</label>
      <textarea id="intruksi" name="intruksi" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Peralatan</label>
      <textarea id="peralatan" name="peralatan" class="form-control" rows="4"></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Referensi</label>
      <textarea id="referensi" name="referensi" class="form-control" rows="4"></textarea>
    </div>

    <!-- Lampiran Gambar -->
    <div class="col-12">
      <label class="form-label d-flex align-items-center justify-content-between">
        <span>Lampiran Gambar
          <small class="text-muted">(jpg/png ≤ 5MB, bisa lebih dari 1)</small>
        </span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenUpload">
          <i class="bi bi-plus-circle"></i> Tambah
        </button>
      </label>

      <div id="galeri" class="d-flex flex-wrap gap-2">
        <!-- thumbnail dinamis -->
      </div>
    </div>

    <!-- Status (khusus admin/reviewer) -->
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
        <h5 class="modal-title">Unggah Gambar</h5>
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
  /* thumb kecil seperti desain */
  #galeri .thumb{
    position:relative;width:120px
  }
  #galeri .thumb img{
    width:100%;height:75px;object-fit:cover;border-radius:.5rem;border:1px solid #e5e7eb
  }
  #galeri .thumb .btn-del{
    position:absolute;left:10px;right:10px;bottom:6px;padding:.1rem .4rem
  }
</style>

<script>
(function(){
  const modalEl = document.getElementById('modalUpload');
  const modal   = new bootstrap.Modal(modalEl);

  // editor
  $('#tujuan, #skenario, #tugas_k, #tugas_p, #intruksi, #peralatan, #referensi').summernote({
    height: 200,
    toolbar: [
      ['style', ['bold','italic','underline','clear']],
      ['para', ['ul','ol','paragraph']],
      ['insert', ['link']],
      ['view', ['codeview']]
    ]
  });

  // select2 standar
  $('.js-select2').select2({ width:'100%' });

  // select2 AJAX untuk "Kode Ujian" (dari osce)
  $('.js-kode-praktek').select2({
    width:'100%',
    placeholder:'Ketik nama/kode ujian…',
    ajax:{
      url:'<?= site_url('admin/soal/praktek/cari-kode') ?>',
      dataType:'json',
      delay:200,
      data:params=>({ q: params.term || '' }),
      processResults:data=>({ results: data.items || [] })
    },
    minimumInputLength:0,
    templateResult:(item)=> item.loading ? item.text : (item.text || ''),
    templateSelection:(item)=> item.text || item.id || ''
  });

  // ===== No.Register otomatis dari t1 / sub2 / t4 (sesuai teori) =====
  function refreshRegister(){
    const t1   = $('[name="t1"]').val();
    const sub2 = $('[name="sub2"]').val();
    const t4   = $('[name="t4"]').val();
    if(!t1 || !sub2 || !t4){
      $('[name="register"]').val(''); return;
    }
    $.get('<?= site_url('admin/soal/praktek/reg-generate') ?>', {t1, sub2, t4})
      .done(function(res){
        if(res.status==='ok'){
          $('[name="register"]').val(res.register);
          if(res.csrf_token) window.__csrf = res.csrf_token;
        }
      });
  }
  $('[name="t1"],[name="sub2"],[name="t4"]').on('change select2:select select2:clear', refreshRegister);

  // ===== Upload (modal) =====
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

    $.ajax({
      url:'<?= site_url('admin/soal/praktek/upload') ?>',
      method:'POST', data:fd, processData:false, contentType:false
    }).done(function(res){
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
      }else{
        Swal.fire('Gagal', res.message || 'Upload gagal', 'error');
      }
    }).fail(xhr=>{
      Swal.fire('Gagal', xhr?.responseJSON?.message || 'Upload gagal', 'error');
    }).always(()=> $b.prop('disabled',false).html('<i class="bi bi-plus-circle"></i> Tambah'));
  });

  // hapus preview
  $(document).on('click','#galeri .btn-del', function(){
    const $t = $(this).closest('.thumb');
    const fn = $t.data('fn');
    Swal.fire({title:'Hapus gambar ini?',icon:'warning',showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.post('<?= site_url('admin/soal/praktek/upload/delete') ?>', {
          name: fn, '<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>')
        }).always(()=> $t.remove());
      });
  });

  // ===== SIMPAN VIA AJAX =====
  $('#formPraktek').on('submit', function(e){
    e.preventDefault();

    // commit summernote
    ['tujuan','skenario','tugas_k','tugas_p','intruksi','peralatan','referensi'].forEach(id=>{
      $('#'+id).val($('#'+id).summernote('code'));
    });

    // paksa draft bila bukan reviewer/admin
    <?php if (!$canReview): ?>
    if(!$('input[name="status"]').length){
      $(this).append('<input type="hidden" name="status" value="draft">');
    }
    <?php endif; ?>

    const $tok = $('#formPraktek input[name="<?= csrf_token() ?>"]');
    if(window.__csrf) $tok.val(window.__csrf);

    const fd = new FormData(this);

    const $btn = $('button[form="formPraktek"]').prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…');

    $.ajax({
      url:'<?= site_url('admin/soal/praktek/simpan') ?>',
      method:'POST', data:fd, processData:false, contentType:false
    })
    .done(function(res){
      if(res && res.csrf_token) window.__csrf = res.csrf_token;
      if(res && res.status==='ok'){
        if(window.swalToast) swalToast('Soal praktek tersimpan');
        setTimeout(()=> location.href = '<?= site_url('admin/soal/praktek') ?>', 600);
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
    });
  });

})();
</script>
<?php $this->endSection(); ?>
