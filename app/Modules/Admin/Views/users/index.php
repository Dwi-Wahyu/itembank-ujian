<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); ?>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<?php $qs = $_SERVER['QUERY_STRING'] ?? ''; ?>
<div class="d-flex align-items-center justify-content-between mb-2">
  <h2 class="page-title"><?= esc($title) ?></h2>
  <div class="d-flex gap-2">
    <a class="btn btn-success"
       href="<?= site_url($routeBase.'/export'.($qs ? '?'.$qs : '')) ?>">
      <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
    </a>
    <button class="btn btn-primary" id="btnAdd">
      <i class="bi bi-plus-circle me-1"></i> Tambah
    </button>
  </div>
</div>


<div class="card mb-3">
  <div class="card-body">
    <form id="filterForm" class="row g-2">
      <div class="col-md-4">
        <input type="text" class="form-control" name="q" value="<?= esc($q) ?>" placeholder="Cari nama/username/email/blok/departemen">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        <a class="btn btn-link" href="<?= site_url($routeBase) ?>">Reset</a>
      </div>
    </form>
  </div>
</div>

<div id="userList">
  <?= view('\Modules\Admin\Views\users\partials\user_table', get_defined_vars()) ?>
</div>

<!-- MODAL ADD/EDIT -->
<div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formUser" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="user_id">
      <div class="modal-header">
        <h5 class="modal-title">Form Pengguna</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nama</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email">
          </div>

          <div class="col-md-3">
            <label class="form-label">Blok</label>
            <select class="form-select" name="blok">
              <option value="">-- Pilih Blok --</option>
              <?php foreach($bloks as $b): ?>
                <option value="<?= $b['id'] ?>"><?= esc($b['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Departemen</label>
            <select class="form-select" name="departemen">
              <option value="">-- Pilih Departemen --</option>
              <?php foreach($deps as $d): ?>
                <option value="<?= $d['id'] ?>"><?= esc($d['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Kordinator</label>
            <input class="form-control" name="kordinator">
          </div>
          <div class="col-md-3">
            <label class="form-label">Umur</label>
            <input type="number" class="form-control" name="old" min="0">
          </div>

          <div class="col-md-6">
            <label class="form-label">Avatar</label>
            <div class="d-flex align-items-center gap-2">
              <div id="avatarPreviewWrap">
                <img id="avatarPreview" src="<?= base_url('assets/img/default-avatar.png') ?>" alt="Preview" style="width:45px;height:45px;object-fit:cover;border-radius:50%;border:1px solid #ddd">
              </div>
              <input type="file" class="form-control" name="avatar_file" accept="image/*" id="inputAvatar">
            </div>
            <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
            <input type="password" class="form-control" name="password" autocomplete="new-password">
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-success" id="btnSave"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<button id="btnResetAll" class="btn btn-danger">
  <i class="bi bi-exclamation-triangle me-1"></i> Reset Semua Password
</button>



<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const CSRF_NAME  = '<?= csrf_token() ?>'; // contoh: 'csrf_test_name'
  let   CSRF_VALUE = document.querySelector('meta[name="csrf-token"]').content;

  // Tambahkan token pada setiap request POST secara otomatis via ajaxSetup
  $.ajaxSetup({
    beforeSend: function(xhr, settings) {
      if (settings.type === 'POST') {
        // Jika data berupa FormData (untuk upload), kita tidak bisa menambahkannya di sini dengan mudah,
        // tapi untuk $.post biasa atau $.ajax dengan object data, ini akan bekerja.
        if (!(settings.data instanceof FormData)) {
          let data = settings.data || "";
          if (typeof data === 'string') {
            if (data.indexOf(CSRF_NAME) === -1) {
              data += (data.length > 0 ? '&' : '') + encodeURIComponent(CSRF_NAME) + '=' + encodeURIComponent(CSRF_VALUE);
            }
          } else {
            data[CSRF_NAME] = CSRF_VALUE;
          }
          settings.data = data;
        }
      }
    }
  });

  function postCSRF(url, data) {
    return $.ajax({url, method:'POST', data: data});
  }

  // Setiap response: ambil token baru dari header dan simpan
  $(document).ajaxComplete(function(_e, xhr){
    const t = xhr.getResponseHeader('X-CSRF-TOKEN');
    if (t) {
      CSRF_VALUE = t;
      document.querySelector('meta[name="csrf-token"]').setAttribute('content', t);
    }
  });

  // Tombol reset-all
  $('#btnResetAll').on('click', function(){
    if (!confirm('Yakin reset SEMUA password menjadi "admin1234"?')) return;
    postCSRF('<?= site_url('admin/master/users/reset-all') ?>', {})
      .done(res => alert('OK: '+res.affected+' user direset. Password: admin1234'))
      .fail(xhr => alert('Gagal: '+(xhr?.responseJSON?.message || 'error')));
  });
})();
</script>
<script>
(function(){
  const base = <?= json_encode(site_url($routeBase)) ?>;
  const $list = $('#userList');
  const modalEl = document.getElementById('modalUser');
  const modal   = new bootstrap.Modal(modalEl);

  function buildURL(){
    return base + '?' + $('#filterForm').serialize();
  }
  function loadList(url){
    const u = (url || buildURL()) + ((url||buildURL()).includes('?')?'&':'?') + 'frag=list';
    $list.css('opacity', .6);
    $.get(u).done(function(html){
      $list.html(html).css('opacity', 1);
    }).fail(function(xhr){
      alert(xhr.responseText || 'Gagal memuat data');
      $list.css('opacity', 1);
    });
  }

  $('#filterForm').on('submit', function(e){ e.preventDefault(); loadList(buildURL()); });

  $('#btnAdd').on('click', function(){
    $('#formUser')[0].reset();
    $('#user_id').val('');
    $('#avatarPreview').attr('src', '<?= base_url('assets/img/default-avatar.png') ?>');
    modal.show();
  });

  // Preview Avatar saat pilih file
  $('#inputAvatar').on('change', function(){
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e){ $('#avatarPreview').attr('src', e.target.result); }
      reader.readAsDataURL(file);
    }
  });

  $(document).on('click','.btn-edit', function(){
    const id = $(this).data('id');
    $.get(base+'/get/'+id).done(function(res){
      if(res.status!=='ok') return swalToast('Data tidak ditemukan', 'error');
      const d = res.data;
      $('#user_id').val(d.id);
      $('[name="name"]').val(d.name);
      $('[name="username"]').val(d.username);
      $('[name="email"]').val(d.email);
      $('[name="blok"]').val(d.blok);
      $('[name="departemen"]').val(d.departemen);
      $('[name="old"]').val(d.old);
      $('[name="kordinator"]').val(d.kordinator);
      $('[name="password"]').val('');
      
      const avatar = d.thumb_avatar || '<?= base_url('assets/img/default-avatar.png') ?>';
      $('#avatarPreview').attr('src', avatar);
      
      modal.show();
    }).fail(()=> swalToast('Gagal memuat data', 'error'));
  });

  $('#formUser').on('submit', function(e){
    e.preventDefault();
    const fd  = new FormData(this);
    const $btn= $('#btnSave').prop('disabled', true)
          .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    $.ajax({url: base+'/save', method:'POST', data:fd, processData:false, contentType:false})
      .done(function(res){
        if(res.status==='ok'){
          swalToast(res.message || 'Berhasil disimpan');
          modal.hide();
          loadList();
        } else {
          Swal.fire('Gagal', res.message || 'Gagal menyimpan', 'error');
        }
      })
      .fail(function(xhr){
        Swal.fire('Gagal', xhr?.responseJSON?.message || 'Gagal menyimpan', 'error');
      })
      .always(function(){
        $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Simpan');
      });
  });

  $(document).on('click','.btn-del', function(){
    const id = $(this).data('id');
    Swal.fire({
      title: 'Hapus pengguna?',
      text: "Data yang dihapus tidak dapat dikembalikan!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(base+'/delete/'+id, {})
          .done(function(res){
            if(res.status==='ok'){ 
              swalToast('Data berhasil dihapus');
              loadList(); 
            } else {
              swalToast(res.message || 'Gagal menghapus', 'error');
            }
          })
          .fail(function(xhr){ swalToast(xhr?.responseJSON?.message || 'Gagal menghapus', 'error'); });
      }
    });
  });

  $(document).on('click','.btn-reset', function(){
    const id = $(this).data('id');
    Swal.fire({
      title: 'Reset Password?',
      text: "Password akan direset ke default (123456)",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, reset!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(base+'/reset/'+id, {})
          .done(function(res){
            if(res.status==='ok'){
              Swal.fire('Berhasil', 'Password baru: '+ (res.new_password || '123456'), 'success');
            } else {
              swalToast(res.message || 'Gagal reset password', 'error');
            }
          })
          .fail(function(xhr){ swalToast(xhr?.responseJSON?.message || 'Gagal reset password', 'error'); });
      }
    });
  });

})();
</script>
<?php $this->endSection(); ?>
