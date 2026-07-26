<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); ?>
<style>
  /* wrapper khusus halaman ini (opsional, cuma buat grouping) */
  .osce-soal-wrapper{
    width:100%;
  }

  /* wrapper tabel supaya kalau lebar, bisa scroll kiri–kanan */
  .osce-table-scroll{
    width:100%;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }

  /* tabel minimal segini, kalau lebih lebar dari layar -> muncul scroll */
  .osce-table-scroll > table{
    width:100%;
    min-width: 1300px; /* boleh kamu adjust 1100 / 1400 sesuai kebutuhan */
  }
</style>

<div class="osce-soal-wrapper">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <h2 class="page-title">OSCE – Soal</h2>
    <button class="btn btn-primary" id="btnAdd">
      <i class="bi bi-plus-circle me-1"></i> Tambah
    </button>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form id="filterForm" class="row g-2">
        <div class="col-md-3">
          <input type="text" name="q" class="form-control"
                 placeholder="Cari kode/register/pengawas/station">
        </div>
        <div class="col-md-3">
          <select name="osce_id" class="form-select js-osce-filter"
                  data-placeholder="Pilih OSCE"></select>
        </div>
        <div class="col-md-3">
          <select name="soal_id" class="form-select js-soal-filter"
                  data-placeholder="Pilih Soal"></select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-primary">
            <i class="bi bi-search"></i>
          </button>
          <a class="btn btn-link" href="<?= site_url('admin/osce-soal') ?>">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div id="osceSoalList">
    <?= view('\Modules\Admin\Views\osce_soal\partials\osce_soal_table', get_defined_vars()) ?>
  </div>
</div>


<!-- MODAL ADD/EDIT -->
<div class="modal fade" id="modalOsceSoal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formOsceSoal" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="oscesoal_id">
      <div class="modal-header">
        <h5 class="modal-title">OSCE – Soal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">OSCE</label>
            <select class="form-select js-osce" name="osce_id" required></select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Soal (Register)</label>
            <select class="form-select js-soal" name="soal_id" required></select>
          </div>
         <div class="col-md-6">
  <label class="form-label">Pengawas</label>
  <select class="form-select js-pengawas" name="pengawas" data-placeholder="Cari pengawas" required></select>
  <input type="hidden" name="nip_pengawas">
  <input type="hidden" name="nama_pengawas">
</div>

          <div class="col-md-6">
            <label class="form-label">Nama Station</label>
            <input class="form-control" name="nama_station" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kode</label>
            <input class="form-control" name="kode" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Waktu (menit)</label>
            <input type="number" min="1" class="form-control" name="waktu" required>
          </div>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<script>
(function(){

  // simpan token awal dari server
  window.__csrf = '<?= csrf_hash() ?>';

  const $list   = $('#osceSoalList');
  const modalEl = document.getElementById('modalOsceSoal');
  const modal   = new bootstrap.Modal(modalEl);

  // helper: update CSRF dari response (kalau ada header X-CSRF-TOKEN)
  function updateCsrfFromXhr(xhr){
    if (!xhr) return;
    const tok = xhr.getResponseHeader && xhr.getResponseHeader('X-CSRF-TOKEN');
    if (tok) window.__csrf = tok;
  }

  // ===================== SELECT2 BUILDERS =====================
  function buildOsceSelect($el, parent){
    $el.select2({
      width: '100%',
      placeholder: $el.data('placeholder') || 'Pilih OSCE',
      allowClear: true,
      dropdownParent: $(parent || document.body),
      ajax: {
        url: '<?= site_url('admin/options/osce') ?>',
        dataType: 'json', delay: 200,
        data: params => ({ q: params.term || '' }),
        processResults: data => ({
          results: (data.results || []).map(r => ({
            id: r.id, text: r.text, kode: r.kode
          }))
        })
      }
    });
  }

  function buildSoalSelect($el, parent){
    $el.select2({
      width: '100%',
      placeholder: $el.data('placeholder') || 'Pilih Soal',
      allowClear: true,
      dropdownParent: $(parent || document.body),
      ajax: {
        url: '<?= site_url('admin/options/soal') ?>',
        dataType: 'json', delay: 200,
        data: params => ({ q: params.term || '' }),
        processResults: data => ({ results: data.results || [] })
      }
    });
  }

  function buildPengawasSelect($el, parent){
    $el.select2({
      width: '100%',
      placeholder: $el.data('placeholder') || 'Cari pengawas (NIP/Nama)',
      allowClear: true,
      dropdownParent: $(parent || document.body),
      ajax: {
        url: '<?= site_url('admin/options/pengawas') ?>',
        dataType: 'json', delay: 200,
        data: params => ({ q: params.term || '' }),
        processResults: data => ({
          results: (data.results || []).map(r => ({
            id: r.nip, text: r.nip+' - '+r.nama, data: r
          }))
        })
      }
    })
    .on('select2:select', function(e){
      const d = e.params.data.data || {};
      $('[name="nip_pengawas"]').val(d.nip || e.params.data.id);
      $('[name="nama_pengawas"]').val(d.nama || (e.params.data.text || '').split(' - ').slice(1).join(' - '));
    })
    .on('select2:clear', function(){
      $('[name="nip_pengawas"], [name="nama_pengawas"]').val('');
    });
  }

  // ===================== HELPERS =====================
  function buildURL(){
    const base = '<?= site_url('admin/osce-soal') ?>';
    return base + '?' + $('#filterForm').serialize();
  }

  function loadList(url){
    const u = (url || buildURL()) + ((url||buildURL()).includes('?')?'&':'?') + 'frag=list';
    Loader && Loader.show();
    $list.css('opacity', .6);
    $.get(u).done(function(html, _s, xhr){
      updateCsrfFromXhr(xhr);          // 🔁 update token dari response GET list
      $list.html(html).css('opacity', 1);
    }).fail(function(xhr){
      Swal.fire('Gagal', xhr?.responseText || 'Tidak dapat memuat data', 'error');
    }).always(()=> Loader && Loader.hide());
  }

  // ===================== FILTER + PAGING =====================
  $(document).on('click','.js-page', function(e){
    e.preventDefault(); loadList($(this).attr('href'));
  });
  $('#filterForm').on('submit', function(e){
    e.preventDefault(); loadList(buildURL());
  });

  // ===================== ADD =====================
  $('#btnAdd').on('click', function(){
    $('#formOsceSoal')[0].reset();
    $('#oscesoal_id').val('');

    const $osce = $('.js-osce').empty();
    const $soal = $('.js-soal').empty();
    const $peng = $('.js-pengawas').empty();

    buildOsceSelect($osce,  '#modalOsceSoal');
    buildSoalSelect($soal,  '#modalOsceSoal');
    buildPengawasSelect($peng,'#modalOsceSoal');

    $('[name="nip_pengawas"], [name="nama_pengawas"]').val('');

    modal.show();
  });

  // ===================== EDIT =====================
  $(document).on('click','.btn-edit', function(){
    const id = $(this).data('id');
    Loader && Loader.show();
    $.get('<?= site_url('admin/osce-soal/get') ?>/' + id)
      .done(function(res, _t, xhr){
        updateCsrfFromXhr(xhr);        // 🔁 token baru kalau ada
        if(res.status==='ok'){
          const d = res.data;
          $('#oscesoal_id').val(d.id);

          const $osce = $('.js-osce').empty();
          const $soal = $('.js-soal').empty();
          const $peng = $('.js-pengawas').empty();

          buildOsceSelect($osce,  '#modalOsceSoal');
          buildSoalSelect($soal,  '#modalOsceSoal');
          buildPengawasSelect($peng,'#modalOsceSoal');

          if (d.osce_id){
            $osce.append(new Option(d.osce_kode || ('ID '+d.osce_id), d.osce_id, true, true)).trigger('change');
          }
          if (d.soal_id){
            $soal.append(new Option(d.soal_register || ('ID '+d.soal_id), d.soal_id, true, true)).trigger('change');
          }
          if (d.nip_pengawas || d.nama_pengawas){
            const t = (d.nip_pengawas||'')+' - '+(d.nama_pengawas||'');
            $peng.append(new Option(t, d.nip_pengawas||'', true, true)).trigger('change');
            $('[name="nip_pengawas"]').val(d.nip_pengawas||'');
            $('[name="nama_pengawas"]').val(d.nama_pengawas||'');
          }

          $('[name="nama_station"]').val(d.nama_station);
          $('[name="kode"]').val(d.kode);
          $('[name="waktu"]').val(d.waktu);

          modal.show();
        } else {
          Swal.fire('Gagal', res.message || 'Data tidak ditemukan', 'error');
        }
      })
      .fail(xhr=>{
        updateCsrfFromXhr(xhr);
        Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat memuat data', 'error');
      })
      .always(()=> Loader && Loader.hide());
  });

  // ===================== SAVE (CREATE / UPDATE) =====================
  $('#formOsceSoal').on('submit', function(e){
    e.preventDefault();
    const id  = $('#oscesoal_id').val();
    const url = id ? '<?= site_url('admin/osce-soal/update') ?>/'+id
                   : '<?= site_url('admin/osce-soal/create') ?>';

    const fd  = new FormData(this);
    // kirim token yang terbaru
    fd.append('<?= csrf_token() ?>', window.__csrf);

    const $btn = $('#btnSave').prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    Loader && Loader.show();

    $.ajax({url, method:'POST', data:fd, processData:false, contentType:false})
      .done(function(res, _t, xhr){
        updateCsrfFromXhr(xhr);
        if(res.status==='ok'){
          modal.hide();
          swalToast && swalToast('Data tersimpan');
          loadList(location.pathname + location.search.replace(/(&?frag=list)/,''));
        } else {
          Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
        }
      })
      .fail(function(xhr){
        updateCsrfFromXhr(xhr);
        Swal.fire('Gagal', (xhr?.responseJSON?.message) || 'Tidak dapat menyimpan', 'error');
      })
      .always(function(){
        Loader && Loader.hide();
        $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Simpan');
      });
  });

  // ===================== DELETE (NO REFRESH NEEDED) =====================
  $(document).on('click','.btn-del', function(){
    const url = $(this).data('url');
    Swal.fire({
      title:'Hapus data ini?', icon:'warning',
      text:'Tindakan tidak dapat dibatalkan.',
      showCancelButton:true,
      confirmButtonText:'Ya, hapus',
      cancelButtonText:'Batal'
    }).then((r)=>{
      if(!r.isConfirmed) return;
      Loader && Loader.show();

      $.ajax({
        url: url,
        method: 'POST',
        data: {
          '<?= csrf_token() ?>': window.__csrf    // selalu pakai token terbaru
        },
      })
      .done(function(res, _t, xhr){
        updateCsrfFromXhr(xhr);                  // 🔁 ambil token baru
        if(res.status==='ok'){
          swalToast && swalToast('Data dihapus');
          loadList(location.pathname + location.search.replace(/(&?frag=list)/,''));
        }else{
          Swal.fire('Gagal', res.message || 'Tidak dapat menghapus', 'error');
        }
      })
      .fail(function(xhr){
        updateCsrfFromXhr(xhr);
        Swal.fire('Gagal', (xhr?.responseJSON?.message) || 'Tidak dapat menghapus', 'error');
      })
      .always(()=> Loader && Loader.hide());
    });
  });
  // ===================== CHECKBOX & MULTI DELETE =====================
  // check/uncheck semua
  $(document).on('change', '#checkAll', function () {
    $('.row-check').prop('checked', this.checked);
  });

  // kalau salah satu uncheck, header ikut uncheck
  $(document).on('change', '.row-check', function () {
    if (!this.checked) {
      $('#checkAll').prop('checked', false);
    } else {
      const all     = $('.row-check').length;
      const checked = $('.row-check:checked').length;
      if (all && all === checked) {
        $('#checkAll').prop('checked', true);
      }
    }
  });

  // tombol hapus terpilih
  $(document).on('click', '#btnDelSelected', function () {
    const ids = $('.row-check:checked').map(function () {
      return $(this).val();
    }).get();

    if (!ids.length) {
      Swal.fire('Informasi', 'Belum ada data yang dipilih.', 'info');
      return;
    }

    Swal.fire({
      title: 'Hapus data terpilih?',
      icon: 'warning',
      text: 'Tindakan tidak dapat dibatalkan.',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then((r) => {
      if (!r.isConfirmed) return;
      Loader && Loader.show();

      $.ajax({
        url: '<?= site_url('admin/osce-soal/delete-multiple') ?>',
        method: 'POST',
        data: {
          ids: ids,
          // kirim token CSRF terbaru (SAMA seperti di delete single)
          '<?= csrf_token() ?>': window.__csrf
        },
        // kirim juga via header, jaga-jaga filter pakai header
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-CSRF-TOKEN', window.__csrf);
        }
      })
      .done(function (res, _t, xhr) {
        updateCsrfFromXhr(xhr);  // ambil token baru dari header (kalau ada)
        if (res.status === 'ok') {
          swalToast && swalToast('Data terpilih dihapus');
          // reload list
          loadList(location.pathname + location.search.replace(/(&?frag=list)/, ''));
        } else {
          Swal.fire('Gagal', res.message || 'Tidak dapat menghapus data terpilih', 'error');
        }
      })
      .fail(function (xhr) {
        updateCsrfFromXhr(xhr);
        Swal.fire('Gagal', (xhr?.responseJSON?.message) || 'Tidak dapat menghapus data terpilih', 'error');
      })
      .always(() => Loader && Loader.hide());
    });
  });

})();
</script>
<?php $this->endSection(); ?>
