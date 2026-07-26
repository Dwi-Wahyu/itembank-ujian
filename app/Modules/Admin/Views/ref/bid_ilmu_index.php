<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex align-items-center justify-content-between mb-2">
  <h2 class="page-title mb-0">Referensi — Bidang Ilmu</h2>
  <button class="btn btn-primary" id="btnAdd"><i class="bi bi-plus-circle me-1"></i> Tambah</button>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form id="filterForm" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label mb-0 small">Pencarian</label>
        <input type="text" class="form-control" name="q" value="<?= esc($q ?? '') ?>" placeholder="Kode / Nama">
      </div>
      <div class="col-md-6 text-md-end">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Terapkan</button>
        <a class="btn btn-link" href="<?= site_url('admin/master/bid-ilmu') ?>">Reset</a>
      </div>
    </form>
  </div>
</div>

<div id="listBox">
  <?= view('\Modules\Admin\Views\ref\partials\bid_ilmu_table', get_defined_vars()) ?>
</div>

<!-- MODAL ADD/EDIT -->
<div class="modal fade" id="modalBid" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formBid" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="bid_id">
      <div class="modal-header">
        <h5 class="modal-title">Bidang Ilmu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Kode <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="kode" required>
        </div>
        <div>
          <label class="form-label">Nama <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="nama" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-success" id="btnSave"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const $list = $('#listBox');
  const modalEl = document.getElementById('modalBid');
  const modal   = new bootstrap.Modal(modalEl);

  function buildListURL(){
    const base = '<?= site_url('admin/master/bid-ilmu') ?>';
    const qs = $('#filterForm').serialize();
    return base + (qs ? ('?'+qs) : '');
  }
  function loadList(url){
    const u = (url || buildListURL()) + ( (url||buildListURL()).includes('?') ? '&' : '?' ) + 'frag=list';
    if (window.Loader) Loader.show();
    $.get(u).done(function(html, status, xhr){
      const tok = xhr.getResponseHeader('X-CSRF-TOKEN');
      if (tok) window.__csrf = tok;
      $list.html(html);
    }).fail(function(xhr){
      Swal.fire('Gagal', xhr?.responseText || 'Tidak dapat memuat data', 'error');
    }).always(()=>{ if (window.Loader) Loader.hide(); });
  }

  // init
  loadList();

  // Filter submit
  $('#filterForm').on('submit', function(e){
    e.preventDefault(); loadList(buildListURL());
  });

  // Add
  $('#btnAdd').on('click', function(){
    $('#formBid')[0].reset();
    $('#bid_id').val('');
    modal.show();
  });

  // Edit
  $(document).on('click', '.btn-edit', function(){
    const id = $(this).data('id');
    if (window.Loader) Loader.show();
    $.get('<?= site_url('admin/master/bid-ilmu/get') ?>/'+id)
      .done(function(res){
        if (res.status === 'ok'){
          const d = res.data;
          $('#bid_id').val(d.id);
          $('#formBid [name="kode"]').val(d.kode);
          $('#formBid [name="nama"]').val(d.nama);
          modal.show();
        }else{
          Swal.fire('Gagal', res.message || 'Data tidak ditemukan', 'error');
        }
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat memuat', 'error'))
      .always(()=> { if (window.Loader) Loader.hide(); });
  });

  // Save (create/update)
  $('#formBid').on('submit', function(e){
    e.preventDefault();
    const id  = $('#bid_id').val();
    const url = id ? '<?= site_url('admin/master/bid-ilmu/update') ?>/'+id
                   : '<?= site_url('admin/master/bid-ilmu/create') ?>';

    const fd = $(this).serializeArray();
    // sisipkan csrf terbaru
    if (window.__csrf){
      const name = '<?= csrf_token() ?>';
      const ix = fd.findIndex(x => x.name === name);
      if (ix >= 0) fd[ix].value = window.__csrf;
      else fd.push({name, value: window.__csrf});
    }

    const $btn = $('#btnSave').prop('disabled',true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    if (window.Loader) Loader.show();

    $.post(url, $.param(fd))
      .done(function(res){
        if(res.csrf_token) window.__csrf = res.csrf_token;
        if (res.status === 'ok'){
          modal.hide();
          if (window.swalToast) swalToast(res.message || 'Tersimpan');
          loadList();
        } else {
          Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
        }
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menyimpan', 'error'))
      .always(()=>{ if (window.Loader) Loader.hide(); $btn.prop('disabled',false).html('<i class="bi bi-check2-circle me-1"></i> Simpan'); });
  });

  // Delete
  $(document).on('click', '.btn-del', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Hapus data ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'})
      .then((r)=>{
        if(!r.isConfirmed) return;
        if (window.Loader) Loader.show();
        const payload = {'<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>')};
        $.post('<?= site_url('admin/master/bid-ilmu/delete') ?>/'+id, payload)
          .done(function(res){
            if(res.csrf_token) window.__csrf = res.csrf_token;
            if (window.swalToast) swalToast(res.message || 'Data dihapus');
            loadList();
          })
          .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus', 'error'))
          .always(()=> { if (window.Loader) Loader.hide(); });
      });
  });

  // Paging
  $(document).on('click', '.js-page', function(e){
    e.preventDefault(); loadList($(this).attr('href'));
  });

})();
</script>
<?php $this->endSection(); ?>
