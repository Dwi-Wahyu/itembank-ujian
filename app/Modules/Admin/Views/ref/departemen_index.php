<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="page-title mb-0">Master Departemen</h2>
  <button class="btn btn-primary" id="btnAdd">
    <i class="bi bi-plus-circle me-1"></i> Tambah Departemen
  </button>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" id="frmSearch">
      <div class="col-md-6">
        <input type="text" class="form-control" name="q" value="<?= esc($q ?? '') ?>" placeholder="Cari nama departemen…">
      </div>
      <div class="col-md-6 text-md-end">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
        <a href="<?= site_url('admin/master/departemen') ?>" class="btn btn-link">Reset</a>
      </div>
    </form>
  </div>
</div>

<div id="listWrap">
  <?= view('\Modules\Admin\Views\ref\partials\departemen_table', get_defined_vars()) ?>
</div>

<!-- MODAL: Tambah/Edit -->
<div class="modal fade" id="modalDept" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formDept" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="id">
      <div class="modal-header">
        <h5 class="modal-title">Departemen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Departemen <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="nama" id="nama" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-success" id="btnSave">
          <i class="bi bi-check2-circle me-1"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const $wrap   = $('#listWrap');
  const modalEl = document.getElementById('modalDept');
  const modal   = new bootstrap.Modal(modalEl);

  function buildURL(){
    const base = '<?= site_url('admin/master/departemen') ?>';
    const q    = $('#frmSearch [name="q"]').val() || '';
    const qs   = $.param({q:q, frag:'list'});
    return base + '?' + qs;
  }

  function loadList(url){
    Loader && Loader.show();
    $.get(url || buildURL()).done(function(html, st, xhr){
      const tok = xhr.getResponseHeader('X-CSRF-TOKEN');
      if (tok) window.__csrf = tok;
      $wrap.html(html);
    }).always(()=> Loader && Loader.hide());
  }

  // init
  loadList();

  // cari
  $('#frmSearch').on('submit', function(e){
    e.preventDefault(); loadList(buildURL());
  });

  // paging
  $(document).on('click','.js-page', function(e){
    e.preventDefault(); loadList($(this).attr('href') + '&frag=list');
  });

  // tambah
  $('#btnAdd').on('click', function(){
    $('#formDept')[0].reset();
    $('#id').val('');
    modal.show();
  });

  // edit
  $(document).on('click','.btn-edit', function(){
    const id = $(this).data('id');
    Loader && Loader.show();
    $.get('<?= site_url('admin/master/departemen/get') ?>/'+id)
      .done(function(res){
        if(res.status==='ok'){
          $('#id').val(res.data.id);
          $('#nama').val(res.data.nama);
          modal.show();
          if(res.csrf_token) window.__csrf = res.csrf_token;
        }else{
          Swal.fire('Gagal', res.message || 'Data tidak ditemukan', 'error');
        }
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat memuat', 'error'))
      .always(()=> Loader && Loader.hide());
  });

  // simpan
  $('#formDept').on('submit', function(e){
    e.preventDefault();
    const fd   = new FormData(this);
    if (window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);
    const $btn = $('#btnSave').prop('disabled',true)
                  .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…');
    Loader && Loader.show();
    $.ajax({
      url: '<?= site_url('admin/master/departemen/save') ?>',
      method: 'POST', data: fd, processData:false, contentType:false
    })
    .done(function(res){
      if(res.csrf_token) window.__csrf = res.csrf_token;
      if(res.status==='ok'){
        modal.hide();
        swalToast && swalToast('Tersimpan');
        loadList();
      }else{
        Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
      }
    })
    .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menyimpan', 'error'))
    .always(()=> { Loader && Loader.hide(); $btn.prop('disabled',false).html('<i class="bi bi-check2-circle me-1"></i> Simpan'); });
  });

  // hapus
  $(document).on('click','.btn-del', function(){
    const url = $(this).data('url');
    Swal.fire({
      title: 'Hapus data ini?', icon:'warning', showCancelButton:true,
      confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'
    }).then((r)=>{
      if(!r.isConfirmed) return;
      Loader && Loader.show();
      $.post(url, {'<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>')})
       .done(function(res){
          if(res.csrf_token) window.__csrf = res.csrf_token;
          if(res.status==='ok'){ swalToast && swalToast('Dihapus'); loadList(); }
          else { Swal.fire('Gagal', res.message || 'Tidak dapat menghapus', 'error'); }
       })
       .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus', 'error'))
       .always(()=> Loader && Loader.hide());
    });
  });

})();
</script>
<?php $this->endSection(); ?>
