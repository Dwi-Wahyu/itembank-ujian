<!-- app/Modules/Admin/Views/soal/soal_teori_list.php -->
<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); 
$f = $filters ?? []; ?>

<div class="d-flex align-items-center justify-content-between mb-2">
  <h2 class="page-title">Soal Teori</h2>

</div>
<div class="d-flex align-items-center justify-content-between mb-2">
 
  <div class="d-flex gap-2">
    <?php if ($me['role_id']==0 || $me['role_id']==1) : ?>
      <a href="<?= site_url('admin/soal/teori/tambah') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Soal
      </a>
    <?php endif; ?>
    <?php if (($me['role_id'] ?? -1) != 6) : ?>
      <a href="<?= site_url('admin/soal/teori/import/template') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-download me-1"></i> Template Excel
      </a>
      <button type="button" class="btn btn-outline-success" id="btnOpenImport">
        <i class="bi bi-upload me-1"></i> Import Excel
      </button>
    <?php endif; ?>

    <!-- <a href="<?= site_url('admin/soal/teori/export/zip') . (
      ($g = service('request')->getGet()) ? ('?' . http_build_query($g)) : ''
   ) ?>" class="btn btn-warning">
  <i class="bi bi-file-earmark-zip me-1"></i> Export ZIP (Word per Paket)
</a> -->

  </div>
</div>

<div class="accordion mb-3" id="filterBox">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#flt" aria-expanded="true">
        <i class="bi bi-funnel-fill me-2"></i> FILTER DATA
      </button>
    </h2>
    <div id="flt" class="accordion-collapse collapse show">
      <div class="accordion-body">
        <form id="filterForm" class="row g-2 align-items-end">
          <div class="col-md-6">
            <select name="t1" class="form-select">
              <option value="">- Semua Kompetensi Utama -</option>
              <?php foreach ($komp as $x): ?>
                <option value="<?= $x['id'] ?>" <?= (int)($f['kId']??'')===$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="departemen" class="form-select">
              <option value="">- Semua Departemen -</option>
              <?php foreach ($departemen as $x): ?>
                <option value="<?= $x['id'] ?>" <?= (int)($f['dep']??'')===$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <select name="t2" class="form-select">
              <option value="">- Semua Penyakit / Kelainan -</option>
              <?php foreach ($sakit as $x): ?>
                <option value="<?= $x['id'] ?>" <?= (int)($f['pId']??'')===$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="blok" class="form-select">
              <option value="">- Semua Blok -</option>
              <?php foreach ($blok as $x): ?>
                <option value="<?= $x['id'] ?>" <?= (int)($f['blk']??'')===$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <select name="t3" class="form-select">
              <option value="">- Semua Bidang Ilmu -</option>
              <?php foreach ($bidang as $x): ?>
                <option value="<?= $x['id'] ?>" <?= (int)($f['bId']??'')===$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="status" class="form-select">
              <option value="">- Semua Status -</option>
              <?php foreach (['draft','review','publish','reject'] as $s): ?>
                <option value="<?= $s ?>" <?= ($f['st']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label mb-0 small">Pencarian</label>
            <input type="text" name="q" class="form-control" value="<?= esc($f['q']??'') ?>" placeholder="Keyword">
          </div>
          <div class="col-12 col-md-6 text-md-end">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Terapkan</button>
            <a class="btn btn-link" href="<?= site_url('admin/soal/teori') ?>">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="soalList">
  <?= view('\Modules\Admin\Views\soal\partials\soal_teori_table', get_defined_vars()) ?>
</div>

<!-- MODAL ADD/EDIT -->
<div class="modal fade" id="modalSoal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formSoal" enctype="multipart/form-data" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="soal_id">
      <div class="modal-header">
        <h5 class="modal-title">Soal Teori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6"><input class="form-control" name="register" placeholder="No. Register"></div>
          <div class="col-md-6">
            <select class="form-select" name="status">
              <?php foreach(['draft','review','publish','reject'] as $s): ?>
                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <select class="form-select" name="departemen">
              <option value="">- Departemen -</option>
              <?php foreach($departemen as $x): ?><option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select class="form-select" name="blok">
              <option value="">- Blok -</option>
              <?php foreach($blok as $x): ?><option value="<?= $x['id'] ?>"><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-12"><textarea class="form-control" name="vignette" rows="2" placeholder="Vignette"></textarea></div>
          <div class="col-12"><textarea class="form-control" name="pertanyaan" rows="2" placeholder="Pertanyaan" required></textarea></div>

          <div class="col-12"><label class="form-label small mb-0">Pilihan Jawaban</label></div>
          <div class="col-md-6"><textarea class="form-control" name="a" rows="2" placeholder="A"></textarea></div>
          <div class="col-md-6"><textarea class="form-control" name="b" rows="2" placeholder="B"></textarea></div>
          <div class="col-md-6"><textarea class="form-control" name="c" rows="2" placeholder="C"></textarea></div>
          <div class="col-md-6"><textarea class="form-control" name="d" rows="2" placeholder="D"></textarea></div>
          <div class="col-md-6"><textarea class="form-control" name="e" rows="2" placeholder="E"></textarea></div>
          <div class="col-md-6">
            <select class="form-select" name="kunci">
              <option value="">- Kunci -</option>
              <?php foreach(['A','B','C','D','E'] as $k): ?><option><?= $k ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6"><input class="form-control" name="subcpl" placeholder="Sub CPL"></div>
          <div class="col-md-6"><input class="form-control" name="id_paket" placeholder="ID Paket"></div>

          <div class="col-12"><textarea class="form-control" name="alasan" rows="2" placeholder="Alasan"></textarea></div>
          <div class="col-12"><textarea class="form-control" name="referensi" rows="2" placeholder="Referensi"></textarea></div>
          <div class="col-12"><input type="file" class="form-control" name="file" accept=".pdf,.jpg,.png,.doc,.docx"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-success" id="btnSave"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<!-- MODAL IMPORT -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <form class="modal-content" id="formImport" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Import Soal dari Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ol class="small mb-3">
          <li>Download terlebih dulu <b>Template Excel</b> untuk format kolom.</li>
          <li>Isi minimal: <code>id_paket/kode_ujian</code>, <code>t1_id</code>, <code>t2_id</code>, <code>t3_id</code>, <code>pertanyaan</code>, <code>kunci</code>.</li>
          <li><em>no_register</em> boleh dikosongkan — sistem akan membuat otomatis.</li>
        </ol>
        <input class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success" id="btnImport"><i class="bi bi-upload"></i> Import</button>
      </div>
    </form>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const $list = $('#soalList');

  // ===== Utils =====
  function setCSRFfromXHR(xhr){
    const tok = xhr && xhr.getResponseHeader ? xhr.getResponseHeader('X-CSRF-TOKEN') : null;
    if (tok) window.__csrf = tok;
  }
  function buildListURL(){
    const base = '<?= site_url('admin/soal/teori') ?>';
    return base + '?' + $('#filterForm').serialize();
  }
  function loadList(url){
    const u = (url || buildListURL()) + ((url || buildListURL()).includes('?') ? '&' : '?') + 'frag=list';
    Loader && Loader.show();
    $.get(u)
      .done(function(html, status, xhr){
        setCSRFfromXHR(xhr);
        $list.html(html);
        swalToast && swalToast('Data diperbarui');
      })
      .fail(function(xhr){
        Swal.fire('Gagal', xhr?.responseText || 'Tidak dapat memuat data', 'error');
      })
      .always(()=> Loader && Loader.hide());
  }

  // ====== Modal instances (Bootstrap 5, bukan jQuery) ======
  const modalSoalEl   = document.getElementById('modalSoal');
  const modalImportEl = document.getElementById('modalImport');
  const modalSoal     = modalSoalEl   ? new bootstrap.Modal(modalSoalEl)   : null;
  const modalImport   = modalImportEl ? new bootstrap.Modal(modalImportEl) : null;

  // Matikan data-API pada tombol import (jika masih ada) dan pakai JS open
  (function attachImportTrigger(){
    const btn = document.getElementById('btnOpenImport') ||
                document.querySelector('[data-bs-target="#modalImport"]');
    if (!btn || !modalImport) return;
    btn.removeAttribute('data-bs-toggle');
    btn.removeAttribute('data-bs-target');
    btn.addEventListener('click', () => modalImport.show());
  })();

  // ===== Init list =====
  loadList();

  // ===== Filter =====
  $('#filterForm').on('submit', function(e){
    e.preventDefault(); loadList(buildListURL());
  });

  // ===== Paging (delegasi) =====
  $(document).on('click','.js-page', function(e){
    e.preventDefault(); loadList($(this).attr('href'));
  });

  // ===== Tambah =====
  $('#btnAdd').on('click', function(){
    if (!modalSoal) return;
    $('#formSoal')[0].reset();
    $('#soal_id').val('');
    modalSoal.show();
  });

  // ===== Edit =====
  $(document).on('click', '.btn-edit', function(){
    const id = $(this).data('id');
    Loader && Loader.show();
    $.get('<?= site_url('admin/soal/teori/get') ?>/'+id)
      .done(function(res,_,xhr){
        setCSRFfromXHR(xhr);
        if(res.status==='ok'){
          const d = res.data || {};
          for (const k in d){
            const $el = $('#formSoal [name="'+k+'"]');
            if($el.length) $el.val(d[k]);
          }
          $('#soal_id').val(d.id);
          modalSoal && modalSoal.show();
          swalToast && swalToast('Data dimuat');
        } else {
          Swal.fire('Gagal', res.message || 'Data tidak ditemukan', 'error');
        }
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat memuat', 'error'))
      .always(()=> Loader && Loader.hide());
  });

  // ===== Simpan (create / update) =====
  $('#formSoal').on('submit', function(e){
    e.preventDefault();
    const id  = $('#soal_id').val();
    const url = id ? '<?= site_url('admin/soal/teori/update') ?>/'+id
                   : '<?= site_url('admin/soal/teori/create') ?>';

    const fd = new FormData(this);
    if (window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);

    const $btn = $('#btnSave').prop('disabled',true)
                  .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    Loader && Loader.show();

    $.ajax({url, method:'POST', data:fd, processData:false, contentType:false})
      .done(function(res,_,xhr){
        setCSRFfromXHR(xhr);
        if(res.csrf_token) window.__csrf = res.csrf_token;
        if(res.status==='ok'){
          modalSoal && modalSoal.hide();
          swalToast && swalToast(id ? 'Perubahan disimpan' : 'Soal tersimpan');
          loadList();
        }else{
          Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
        }
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menyimpan', 'error'))
      .always(()=>{ Loader && Loader.hide(); $btn.prop('disabled',false).html('<i class="bi bi-check2-circle me-1"></i> Simpan'); });
  });

  // ===== Hapus =====
  $(document).on('click','.btn-del', function(){
    const url = $(this).data('url');
    Swal.fire({title:'Hapus soal ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'})
      .then((r)=>{
        if(!r.isConfirmed) return;
        Loader && Loader.show();
        $.post(url, {'<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>') })
          .done(function(res,_,xhr){
            setCSRFfromXHR(xhr);
            if(res.csrf_token) window.__csrf = res.csrf_token;
            swalToast && swalToast('Data dihapus'); loadList();
          })
          .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus', 'error'))
          .always(()=> Loader && Loader.hide());
      });
  });

  // ===== Import Excel =====
  $('#formImport').on('submit', function(e){
    e.preventDefault();

    const fd = new FormData(this);
    if (window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);

    const $btn = $('#btnImport').prop('disabled', true)
                  .html('<span class="spinner-border spinner-border-sm me-2"></span>Mengimpor...');
    Loader && Loader.show();

    const renderErrors = (arr) => {
      if (!arr || !arr.length) return '';
      const esc = (s) => $('<div>').text(String(s)).html();
      const html = arr.map(esc).join('<br>');
      return `<hr><div class="text-start small" style="max-height:260px;overflow:auto">${html}</div>`;
    };
    const buildLogFooter = (arr) => {
      if (!arr || !arr.length) return '';
      const blob = new Blob([arr.join('\n')], {type:'text/plain'});
      const url  = URL.createObjectURL(blob);
      return `<a href="${url}" download="import-errors.txt">Download log error</a>`;
    };

    $.ajax({
      url: '<?= site_url('admin/soal/teori/import/upload') ?>',
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json'
    })
    .done(function(res,_,xhr){
      setCSRFfromXHR(xhr);
      if (res.csrf_token) window.__csrf = res.csrf_token;

      if (res.status === 'ok') {
        const failed   = Number(res.failed || 0);
        const inserted = Number(res.inserted || 0);
        const errors   = res.errors || [];
        const info = `
          Berhasil: <b>${inserted}</b> baris<br>
          Gagal: <b>${failed}</b> baris
          ${renderErrors(errors)}
        `;

        Swal.fire({
          icon: failed > 0 ? 'warning' : 'success',
          title: 'Import selesai',
          html: info,
          width: 720,
          footer: buildLogFooter(errors),
          allowOutsideClick: false,
          allowEscapeKey: false,
          confirmButtonText: 'OK',
          focusConfirm: true
        }).then(() => {
          if (failed === 0 && modalImport) modalImport.hide(); // BS5 API seperti modal Tambah Sesi
          loadList();
        });
      } else {
        const info = (res.message || 'Import gagal.') + renderErrors(res.errors);
        Swal.fire({ icon:'error', title:'Gagal', html: info, width: 720 });
      }
    })
    .fail(function(xhr){
      let msg = 'Import gagal.';
      let errs = [];
      try {
        const r = xhr.responseJSON || JSON.parse(xhr.responseText);
        if (r) {
          msg  = r.message || msg;
          errs = r.errors || [];
          if (r.csrf_token) window.__csrf = r.csrf_token;
        }
      } catch(_) {}
      Swal.fire({
        icon:'error',
        title:'Gagal',
        html: (msg || 'Import gagal.') + renderErrors(errs),
        width: 720,
        footer: buildLogFooter(errs)
      });
    })
    .always(function(){
      $btn.prop('disabled', false).html('<i class="bi bi-upload"></i> Import');
      Loader && Loader.hide();
    });
  });

  // Delete Soal Teori
  $(document).on('click', '.btn-del', function() {
    const url = $(this).data('url');
    Swal.fire({
      title: 'Hapus soal ini?',
      text: 'Data soal teori akan dihapus permanen.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then(r => {
      if (!r.isConfirmed) return;
      Loader && Loader.show();
      $.post(url, { '<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>') })
      .done(function(res, _, xhr) {
        if (typeof setCSRFfromXHR === 'function') setCSRFfromXHR(xhr);
        if (res.csrf_token) window.__csrf = res.csrf_token;
        swalToast && swalToast('Soal dihapus');
        loadList();
      })
      .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus', 'error'))
      .always(() => Loader && Loader.hide());
    });
  });

})();
</script>

<?php $this->endSection(); ?>
