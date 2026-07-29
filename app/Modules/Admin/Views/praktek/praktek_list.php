<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); ?>

<div class="d-flex align-items-center justify-content-between mb-2">
  <h2 class="page-title">Soal Praktek</h2>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <?php if ($me['role_id']==0 || $me['role_id']==1): ?>
    <a href="<?= site_url('admin/soal/praktek/add') ?>" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Tambah Soal
    </a>
    <button type="button" class="btn btn-outline-primary" id="btnOpenImport">
      <i class="bi bi-upload"></i> Import Excel
    </button>
  <?php endif; ?>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="formImport" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Import Soal Praktek & Aspek</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ol class="small mb-3">
          <li>Download template, isi Sheet <b>SOAL</b> & <b>ASPEK</b>.</li>
          <li>Minimal SOAL: <code>register</code>, <code>skenario</code>.</li>
          <li>Minimal ASPEK: <code>soal_register</code>, <code>aspek</code>.</li>
        </ol>
        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/soal/praktek/import/template') ?>">
          <i class="bi bi-download"></i> Download Template
        </a>
        <hr>
        <input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button class="btn btn-primary" id="btnImport"><i class="bi bi-upload"></i> Import</button>
      </div>
    </form>
  </div>
</div>

<!-- FILTER DATA -->
<div class="accordion mb-3" id="fltBox">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#flt" aria-expanded="true">
        <i class="bi bi-funnel-fill me-2"></i> FILTER DATA
      </button>
    </h2>
    <div id="flt" class="accordion-collapse collapse show">
      <div class="accordion-body">
        <?php
          $req = service('request');
          $getT1         = $req->getGet('t1');
          $getDepartemen = $req->getGet('departemen');
          $getT2         = $req->getGet('t2');
          $getBlok       = $req->getGet('blok');
          $getT3         = $req->getGet('t3');
          $getStatus     = $req->getGet('status');
          $getQ          = $req->getGet('q');
        ?>
        <form id="filterForm" class="row g-2 align-items-end">
          <div class="col-md-6">
            <select name="t1" class="form-select">
              <option value="">- Semua Kompetensi Utama -</option>
              <?php foreach($komp as $x): ?><option value="<?= $x['id'] ?>" <?= (string)$getT1===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="departemen" class="form-select">
              <option value="">- Semua Departemen -</option>
              <?php foreach($departemen as $x): ?><option value="<?= $x['id'] ?>" <?= (string)$getDepartemen===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="t2" class="form-select">
              <option value="">- Semua Penyakit / Kelainan -</option>
              <?php foreach($sakit as $x): ?><option value="<?= $x['id'] ?>" <?= (string)$getT2===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="blok" class="form-select">
              <option value="">- Semua Blok -</option>
              <?php foreach($blok as $x): ?><option value="<?= $x['id'] ?>" <?= (string)$getBlok===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="t3" class="form-select">
              <option value="">- Semua Bidang Ilmu -</option>
              <?php foreach($bidang as $x): ?><option value="<?= $x['id'] ?>" <?= (string)$getT3===(string)$x['id']?'selected':'' ?>><?= esc($x['nama']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <select name="status" class="form-select">
              <option value="">- Semua Status -</option>
              <?php foreach(['draft','review','publish','reject'] as $s): ?>
                <option value="<?= $s ?>" <?= (string)$getStatus===(string)$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="small mb-0">Pencarian</label>
            <input class="form-control" name="q" value="<?= esc($getQ ?? '') ?>" placeholder="Keyword">
          </div>
          <div class="col-12 col-md-6 text-md-end">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Terapkan</button>
            <a href="<?= site_url('admin/soal/praktek') ?>" class="btn btn-link">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- LIST -->
<div id="praktekList">
  <?= view('\Modules\Admin\Views\praktek\partials\praktek_table', get_defined_vars()) ?>
</div>

<!-- MODAL ASPEK -->
<div class="modal fade" id="modalAspek" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Daftar OSCE (Aspek) – Soal #<span id="mSoalId"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="small text-muted">Klik Hapus untuk menghapus baris.</div>
          <?php if (($me['role_id'] ?? -1) != 6): ?>
          <a id="btnTambahOsce" class="btn btn-primary btn-sm" href="#" target="_self">
            <i class="bi bi-plus-circle me-1"></i> Tambah Data
          </a>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle" id="tblOsce">
            <thead class="table-light">
              <tr>
                <?php if (($me['role_id'] ?? -1) != 6): ?>
                <th style="width:72px">Aksi</th>
                <?php endif; ?>
                <th>Aspek</th><th>Keterangan</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  (function(){
    // ---------- Export (opsional) ----------
    function qsFilter(){ return ($('#filterForm').length ? $('#filterForm').serialize() : ''); }
    $('#btnExportAll').on('click', function(e){
      e.preventDefault();
      location.href = '<?= site_url('admin/soal/praktek/export/all') ?>' + (qsFilter()?('?'+qsFilter()):'');
    });
    $('#btnExportZip').on('click', function(e){
      e.preventDefault();
      location.href = '<?= site_url('admin/soal/praktek/export/zip') ?>' + (qsFilter()?('?'+qsFilter()):'');
    });

  // ---------- CSRF helpers ----------
    function setCSRFfromXHR(xhr){
      const tok = xhr?.getResponseHeader && xhr.getResponseHeader('X-CSRF-TOKEN');
      if (tok) window.__csrf = tok;
    }
    function csrfParam(){
      const p = {}; p['<?= csrf_token() ?>'] = (window.__csrf || '<?= csrf_hash() ?>'); return p;
    }
 
    const modalAspekEl  = document.getElementById('modalAspek');
    const modalAspek    = modalAspekEl ? bootstrap.Modal.getOrCreateInstance(modalAspekEl) : null;

    document.getElementById('btnOpenImport')?.addEventListener('click', () => modalImport && modalImport.show());

    const $list = $('#praktekList');

    function buildListURL(){
      const base = '<?= site_url('admin/soal/praktek') ?>';
      return base + '?' + ($('#filterForm').length ? $('#filterForm').serialize() : '');
    }

    function loadList(url, pushState = true){
      const targetUrl = url || buildListURL();
      const u = targetUrl + (targetUrl.includes('?') ? '&' : '?') + 'frag=list';
      Loader && Loader.show();
      $.get(u)
      .done(function(html, status, xhr){
        setCSRFfromXHR(xhr);
        $list.html(html);
        if (pushState && history.pushState && targetUrl !== location.href) {
          history.pushState(null, '', targetUrl);
        }
      })
      .fail(function(xhr){
        Swal.fire('Gagal', xhr?.responseText || 'Tidak dapat memuat data', 'error');
      })
      .always(()=> Loader && Loader.hide());
    }

    window.addEventListener('popstate', function() {
      loadList(location.href, false);
    });

  // Filter submit
    $('#filterForm').on('submit', function(e){
      e.preventDefault(); loadList(buildListURL());
    });

  // Paging delegasi
    $(document).on('click','.js-page', function(e){
      e.preventDefault(); loadList($(this).attr('href'));
    });

  // Tombol Import
    const modalImportEl = document.getElementById('modalImport');
    const modalImport   = modalImportEl ? new bootstrap.Modal(modalImportEl) : null;
    document.getElementById('btnOpenImport')?.addEventListener('click', ()=> modalImport && modalImport.show());

  // Upload Import
    $('#formImport').on('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this);
      const $btn = $('#btnImport').prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Mengimpor...');
      Loader && Loader.show();

      $.ajax({
        url: '<?= site_url('admin/soal/praktek/import/upload') ?>',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      })
      .done(function(res,_,xhr){
        setCSRFfromXHR(xhr);
        if(res.status==='ok'){
          Swal.fire('Berhasil','Import selesai','success').then(()=>{ modalImport && modalImport.hide(); loadList(); });
        }else{
          Swal.fire('Gagal', res.message || 'Import gagal','error');
        }
      })
      .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Import gagal','error'))
      .always(()=>{ $btn.prop('disabled',false).html('<i class="bi bi-upload"></i> Import'); Loader && Loader.hide(); });
    });

  // Delete
    $(document).on('click','.btn-del', function(){
      const url = $(this).data('url');
      Swal.fire({title:'Hapus data ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'})
      .then(r=>{
        if(!r.isConfirmed) return;
        Loader && Loader.show();
        $.post(url, {'<?= csrf_token() ?>': (window.__csrf || '<?= csrf_hash() ?>') })
        .done(function(res,_,xhr){
          setCSRFfromXHR(xhr);
          swalToast && swalToast('Data dihapus'); loadList();
        })
        .fail(xhr=> Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus','error'))
        .always(()=> Loader && Loader.hide());
      });
    });
 // ---------- Buka modal OSCE + load list ----------
    $(document).on('click', '.js-aspek', function(){
      const soalId = parseInt(this.dataset.soalId || '0', 10);
      if (!soalId) return;

      $('#mSoalId').text(soalId);
      $('#btnTambahOsce').attr('href', '<?= base_url('admin/aspek/add/') ?>' + soalId);

      Loader && Loader.show();
      $.get('<?= base_url('admin/praktek/aspek/list') ?>', {soal_id: soalId})
      .done(function(res, _t, xhr){
        setCSRFfromXHR(xhr);
        if (res.status === 'ok'){
          renderOsceRows(res.items || []);
          modalAspek && modalAspek.show();
        } else {
          Swal.fire('Gagal', res.message || 'Tidak bisa memuat data', 'error');
        }
      })
      .fail(function(xhr){
        Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat', 'error');
      })
      .always(()=> Loader && Loader.hide());
    });
 // ---------- Render OSCE rows ----------
    function renderOsceRows(items){
      const tb = document.querySelector('#tblOsce tbody');
      tb.innerHTML = '';
      if (!items || !items.length){
        tb.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-3">Belum ada data.</td></tr>';
        return;
      }
      const frag = document.createDocumentFragment();
      items.forEach(x=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
      <td>${x.aspek ?? ''}</td>
      <td class="text-wrap"><div class="clamp-2">${x.keterangan ?? ''}</div></td>
      `;
      frag.appendChild(tr);
    });
      tb.appendChild(frag);
    }
    // ---------- Hapus OSCE ----------
    function withIdInUrl(url, id){
      if (!url) return url;
      if (url.includes('{id}')) return url.replace('{id}', id);
      if (url.includes(':id'))  return url.replace(':id', id);
    // jika sudah /123 di akhir, biarkan; jika tidak, tambahkan /id
      if (!/\/\d+($|\?)/.test(url)) url = url.replace(/\/$/, '') + '/' + id;
      return url;
    }

    $(document).on('click', '.btn-del-aspek', function(){
      const id  = parseInt(this.dataset.id || '0', 10);
      let   url = this.dataset.url || '<?= base_url('admin/aspek/delete') ?>';
      if (!id) return;

      Swal.fire({
        title:'Hapus data ini?',
        text:'Tindakan tidak bisa dibatalkan.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Ya, hapus',
        cancelButtonText:'Batal'
      }).then((r)=>{
        if (!r.isConfirmed) return;

        Loader && Loader.show();

      // Coba kirim dengan body id (umum di CI), tapi URL juga kita siapkan untuk pola /delete/{id}
        const urlWithId = withIdInUrl(url, String(id));

        $.ajax({
          url: urlWithId,
          method: 'POST',
          data: Object.assign({ id: id }, csrfParam()),
          dataType: 'json'
        })
        .done((res, _t, xhr)=>{
          setCSRFfromXHR(xhr);
          if (res.csrf_token) window.__csrf = res.csrf_token;

          if (res.status === 'ok'){
          // Hapus row di modal
            const btn = document.querySelector(`.btn-del-aspek[data-id="${id}"]`);
            if (btn){ const tr = btn.closest('tr'); if (tr) tr.remove(); }

          // Update badge jumlah (jika API mengembalikan info)
            if (typeof res.soal_id !== 'undefined'){
              const badge = document.querySelector(`.js-aspek[data-soal-id="${res.soal_id}"]`);
              if (badge){
                const j = (typeof res.jlh !== 'undefined')
                ? res.jlh
                : Math.max(0, parseInt(badge.dataset.jlh||'0',10) - 1);
                badge.dataset.jlh = j;
                badge.textContent = j;
              }
            }

            window.swalToast && swalToast('Data dihapus');
          } else {
            Swal.fire('Gagal', res.message || 'Gagal menghapus', 'error');
          }
        })
        .fail((xhr)=>{
          const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus';
          Swal.fire('Gagal', msg, 'error');
        })
        .always(()=> Loader && Loader.hide());
      });
    });
// ---- HAPUS SOAL PRAKTEK ----
    $(document).on('click', '.btn-del', function(){
      const $btn = $(this);
      const url  = $btn.data('url');
      const id   = parseInt($btn.data('id') || '0', 10);
      if (!url || !id) return;

      Swal.fire({
        title: 'Hapus soal ini?',
        text: 'Tindakan tidak bisa dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((r)=>{
        if (!r.isConfirmed) return;

        const oldHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        Loader && Loader.show();

        $.ajax({
          url: url,
          method: 'POST',
      // Kirim 3 hal: id, _method (fallback jika controller pakai DELETE), dan CSRF
          data: Object.assign({ id: id, _method: 'DELETE' }, (typeof csrfParam==='function' ? csrfParam() : {})),
          dataType: 'json'
        })
        .done(function(res, _t, xhr){
      // refresh CSRF kalau ada
          if (typeof setCSRFfromXHR === 'function') setCSRFfromXHR(xhr);
          if (res && res.csrf_token) window.__csrf = res.csrf_token;

          if (res && res.status === 'ok'){
        // Cara 1: reload tabel partial (aman untuk pagination/filter)
            if (typeof loadTable === 'function') {
              loadTable(location.pathname + location.search.replace(/(&?frag=list)/,''));
            } else {
          // Cara 2 (fallback): hapus baris langsung
              const tr = $btn.closest('tr'); if (tr.length) tr.remove();
            }
            window.swalToast && swalToast('Data dihapus');
          } else {
            Swal.fire('Gagal', (res && res.message) || 'Gagal menghapus', 'error');
          }
        })
        .fail(function(xhr){
          const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus';
          Swal.fire('Gagal', msg, 'error');
        })
        .always(function(){
          $btn.prop('disabled', false).html(oldHtml);
          Loader && Loader.hide();
        });
      });
    });
  })();
</script>
<?php $this->endSection(); ?>
