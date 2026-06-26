<?php
$this->extend('\Modules\Admin\Views\layouts\admin');
$this->section('content');

$tab   = $tab ?? 'mendatang';
$f     = $filters ?? [];
$q     = $f['q'] ?? '';
$depId = $f['depId'] ?? '';
$blokId= $f['blokId'] ?? '';
$d1    = $f['d1'] ?? '';
$d2    = $f['d2'] ?? '';

function qurl($p=[]){ return current_url().'?'.http_build_query(array_merge($_GET,$p)); }
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="page-title">Sesi Ujian Teori</h2>
  <button class="btn btn-primary" id="btnAdd"><i class="bi bi-plus-circle me-1"></i> Tambah Sesi</button>
</div>

<ul class="nav nav-pills mb-3">
  
  <li class="nav-item">
    <a class="nav-link js-tab <?= $tab==='mendatang'?'active':'' ?>"
       data-tab="mendatang" href="<?= qurl(['tab'=>'mendatang','page'=>1]) ?>">Mendatang</a>
  </li>
  <li class="nav-item">
    <a class="nav-link js-tab <?= $tab==='berlangsung'?'active':'' ?>"
       data-tab="berlangsung" href="<?= qurl(['tab'=>'berlangsung','page'=>1]) ?>">Berlangsung</a>
  </li>
  <li class="nav-item">
    <a class="nav-link js-tab <?= $tab==='selesai'?'active':'' ?>"
       data-tab="selesai" href="<?= qurl(['tab'=>'selesai','page'=>1]) ?>">Selesai</a>
  </li>
</ul>


<form id="filterForm" class="row g-2 align-items-end mb-3" method="get" action="<?= base_url('admin/ujian/teori') ?>">
  <input type="hidden" name="tab" value="<?= esc($tab) ?>">
  <div class="col-md-3">
    <label class="form-label">Nama Ujian</label>
    <input type="text" name="q" value="<?= esc($q) ?>" class="form-control" placeholder="Cari nama ujian…">
  </div>
  <div class="col-md-3">
    <label class="form-label">Departemen</label>
    <select name="departemen_id" class="form-select">
      <option value="">Semua Departemen</option>
      <?php foreach ($departemen as $d): ?>
      <option value="<?= $d['id'] ?>" <?= $depId==$d['id']?'selected':'' ?>><?= esc($d['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Blok</label>
    <select name="blok_id" id="f_blok" class="form-select">
      <option value="">Semua Blok</option>
      <?php foreach ($blok as $b): ?>
      <option value="<?= $b['id'] ?>" <?= $blokId==$b['id']?'selected':'' ?>><?= esc($b['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="w-100 d-md-none"></div>
  <div class="col-md-3">
    <label class="form-label">Dari Tanggal</label>
    <input type="date" name="d1" value="<?= esc($d1) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Sampai</label>
    <input type="date" name="d2" value="<?= esc($d2) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <button class="btn btn-outline-primary mt-2"><i class="bi bi-search"></i> Terapkan</button>
    <a class="btn btn-link mt-2" href="<?= base_url('admin/ujian/teori?tab='.$tab) ?>">Reset</a>
  </div>
</form>

<!-- container yang akan di-reload AJAX -->
<div id="teoriList">
  <?= view('\Modules\Admin\Views\ujian\partials\teori_table', get_defined_vars()) ?>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" id="formAddTeori" autocomplete="off">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Ujian Teori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Ujian</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Departemen</label>
            <select class="form-select" name="dapertemen_id">
              <option value="">- Semua Departemen -</option>
              <?php foreach ($departemen as $d): ?>
              <option value="<?= $d['id'] ?>"><?= esc($d['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Blok</label>
            <select class="form-select" name="blok">
              <option value="">- Semua Blok -</option>
              <?php foreach ($blok as $b): ?>
              <option value="<?= $b['id'] ?>"><?= esc($b['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Tanggal</label>
            <input type="text" name="tanggal" class="form-control js-date" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Mulai</label>
            <input type="text" name="mulai" class="form-control js-time">
          </div>
          <div class="col-md-4">
            <label class="form-label">Selesai</label>
            <input type="text" name="selesai" class="form-control js-time">
          </div>
        </div>
        <div class="row g-3 mt-1">
         <div class="col-md-6">
  <label class="form-label">Kode</label>
  <div class="input-group">
    <input type="text" name="kode" id="add_kode" class="form-control" placeholder="(otomatis jika kosong)">
    <button type="button" class="btn btn-outline-secondary" id="btnGenKode"><i class="bi bi-shuffle"></i></button>
  </div>
</div>

          <div class="col-md-6">
            <label class="form-label">Jumlah Soal</label>
            <input type="number" min="1" name="jumlah_soal" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nilai Minimum</label>
            <input type="number" min="1" name="nilai_minimum" class="form-control">
          </div>
        </div>
        <input type="hidden" name="status" value="pending">
      </div>
      <div class="modal-footer">
        <div class="me-auto text-danger small d-none" id="addErr"></div>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
        <button type="submit" class="btn btn-success" id="btnSave"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" id="formEditTeori" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header">
        <h5 class="modal-title">Ubah Ujian Teori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Ujian</label>
          <input type="text" class="form-control" name="nama" id="edit_nama" required>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Departemen</label>
            <select class="form-select" name="dapertemen_id" id="edit_dep">
              <option value="">- Semua Departemen -</option>
              <?php foreach ($departemen as $d): ?>
                <option value="<?= $d['id'] ?>"><?= esc($d['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Blok</label>
            <select class="form-select" name="blok" id="edit_blok">
              <option value="">- Semua Blok -</option>
              <?php foreach ($blok as $b): ?>
                <option value="<?= $b['id'] ?>"><?= esc($b['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Tanggal</label>
            <input type="text" class="form-control js-date" name="tanggal" id="edit_tanggal" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Mulai</label>
            <input type="text" class="form-control js-time" name="mulai" id="edit_mulai">
          </div>
          <div class="col-md-4">
            <label class="form-label">Selesai</label>
            <input type="text" class="form-control js-time" name="selesai" id="edit_selesai">
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Kode</label>
            <input type="text" class="form-control" name="kode" id="edit_kode">
          </div>
          <div class="col-md-6">
            <label class="form-label">Jumlah Soal</label>
            <input type="number" min="1" class="form-control" name="jumlah_soal" id="edit_jml">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-success" id="btnUpdate"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const $list = $('#teoriList');

  function getParam(url, key){
    const u = new URL(url, location.origin);
    return u.searchParams.get(key);
  }
  function setActiveTabFromUrl(url){
    const t = getParam(url, 'tab') || 'mendatang';
    $('.nav-pills .nav-link').removeClass('active')
      .filter(`[data-tab="${t}"]`).addClass('active');
    $('#filterForm input[name="tab"]').val(t); // sinkron ke hidden input filter
  }

  // ---- MODAL references + pickers ----
  const modalAddEl  = document.getElementById('modalAdd');
  const modalEditEl = document.getElementById('modalEdit');
  const modalAdd  = new bootstrap.Modal(modalAddEl);
  const modalEdit = new bootstrap.Modal(modalEditEl);
  modalAddEl.addEventListener('shown.bs.modal',  () => window.initPickers(modalAddEl));
  modalEditEl.addEventListener('shown.bs.modal', () => window.initPickers(modalEditEl));

  // ---- loader list AJAX + set active tab ----
  function loadList(url){
    const finalURL = url + (url.includes('?') ? '&' : '?') + 'frag=list';
    Loader.show();
    $list.css('opacity', .6);
    $.get(finalURL).done(function(html){
      $list.html(html).css('opacity', 1);
      history.replaceState(null, '', url);
      setActiveTabFromUrl(url);       // <-- penting, update tab aktif
      swalToast('Data diperbarui');
    }).fail(function(xhr){
      Swal.fire('Gagal', xhr?.responseText || 'Tidak dapat memuat data', 'error');
    }).always(()=> Loader.hide());
  }

  // ---- init: tandai tab sesuai URL saat pertama kali muat halaman ----
  setActiveTabFromUrl(location.href);

  // ---- klik TAB -> ajax + tandai aktif segera ----
  $(document).on('click', '.js-tab', function(e){
    e.preventDefault();
    const href = $(this).attr('href');
    setActiveTabFromUrl(href);   // visual cepat
    loadList(href);              // muat isi tabel
  });

  // ---- paging -> ajax ----
  $(document).on('click', '.js-page', function(e){
    e.preventDefault();
    loadList($(this).attr('href'));
  });

  // ---- filter -> ajax ----
  $('#filterForm').on('submit', function(e){
    e.preventDefault();
    const base = '<?= base_url('admin/ujian/teori') ?>';
    const url  = base + '?' + $(this).serialize();
    loadList(url);
  });

  // ---- TAMBAH SESI: tampilkan modal ----
  $('#btnAdd').on('click', function(){
    // reset form sederhana
    $('#formAddTeori')[0].reset();
    // jika kamu rotate CSRF, boleh set ulang hidden token di sini bila perlu
    modalAdd.show();
  });
$('#btnGenKode').on('click', function(){
  Loader.show();
  $.get('<?= base_url('admin/ujian/teori/newcode') ?>', function(res){
    if(res.status==='ok'){
      $('#add_kode').val(res.kode);
      swalToast('Kode dibuat');
    } else {
      Swal.fire('Gagal', res.message || 'Tidak bisa membuat kode', 'error');
    }
  }).fail(()=> Swal.fire('Gagal','Tidak bisa membuat kode','error'))
    .always(()=> Loader.hide());
});

  // ---- SIMPAN TAMBAH (AJAX) ----
  $('#formAddTeori').on('submit', function(e){
    e.preventDefault();
    const $btn = $('#btnSave').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    Loader.show();
    $.post('<?= base_url('admin/ujian/teori/create') ?>', $(this).serialize())
      .done(function(res){
        if(res.status === 'ok'){
          modalAdd.hide();
          swalToast('Sesi tersimpan');
          // refresh list sesuai URL saat ini
          loadList(location.pathname + location.search.replace(/(&?frag=list)/,''));
        } else {
          Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
        }
      })
      .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menyimpan', 'error'))
      .always(()=>{
        Loader.hide();
        $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Simpan');
      });
  });

  // ---- EDIT: buka modal + isi data ----
  $(document).on('click', '.btn-edit', function(){
    const id = $(this).data('id');
    Loader.show();
    $.get('<?= base_url('admin/ujian/teori/get') ?>/' + id)
      .done(function(res){
        if(res.status === 'ok'){
          const d = res.data;
          $('#edit_id').val(d.id);
          $('#edit_nama').val(d.nama);
          $('#edit_dep').val(d.dapertemen_id);
          $('#edit_blok').val(d.blok);
          $('#edit_tanggal').val(d.tanggal);
          $('#edit_mulai').val((d.mulai||'').substring(0,5));
          $('#edit_selesai').val((d.selesai||'').substring(0,5));
          $('#edit_kode').val(d.kode);
          $('#edit_jml').val(d.jumlah_soal);
          modalEdit.show();
          swalToast('Data dimuat');
        } else {
          Swal.fire('Gagal', res.message || 'Data tidak ditemukan', 'error');
        }
      })
      .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat memuat data', 'error'))
      .always(()=> Loader.hide());
  });

  // ---- EDIT: simpan ----
  $('#formEditTeori').on('submit', function(e){
    e.preventDefault();
    const id = $('#edit_id').val();
    const $btn = $('#btnUpdate').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    Loader.show();
    $.post('<?= base_url('admin/ujian/teori/update') ?>/' + id, $(this).serialize())
      .done(function(res){
        if(res.status === 'ok'){
          modalEdit.hide();
          swalToast('Perubahan disimpan');
          loadList(location.pathname + location.search.replace(/(&?frag=list)/,''));
        } else {
          Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
        }
      })
      .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menyimpan', 'error'))
      .always(()=>{
        Loader.hide();
        $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Simpan');
      });
  });

  // ---- HAPUS: swal konfirmasi ----
  $(document).on('click', '.btn-del', function(){
    const url = $(this).data('url');
    Swal.fire({
      title:'Hapus sesi ini?',
      text:'Tindakan tidak bisa dibatalkan.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Ya, hapus',
      cancelButtonText:'Batal'
    }).then((r)=>{
      if(!r.isConfirmed) return;
      Loader.show();
      $.post(url, {'<?= csrf_token() ?>':'<?= csrf_hash() ?>'})
        .done(function(res){
          swalToast('Data dihapus');
          loadList(location.pathname + location.search.replace(/(&?frag=list)/,''));
        })
        .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus', 'error'))
        .always(()=> Loader.hide());
    });
  });
})();

</script>

<?php $this->endSection(); ?>
