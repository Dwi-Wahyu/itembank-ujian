<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content');
$station_id=(int)$uji['id']
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= site_url('admin/ujian/praktek') ?>">Ujian praktek</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($uji['nama_ujian']) ?></li>
    </ol>
  </nav>
</div>

<div class="card mb-3">
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <tr><th class="w-25">Departemen</th><td class="text-end"><?= esc($dep) ?></td></tr>
      <tr><th>Blok</th><td class="text-end"><?= esc($blok) ?></td></tr>
      <tr><th>Tanggal</th><td class="text-end"><?= tgl_id($uji['tanggal']) ?></td></tr>
      <tr><th>Jlh. Peserta</th><td class="text-end" id="jmlPeserta"><?= (int)$jumlah ?></td></tr>
    </table>
  </div>
</div>

<div class="card mb-3 border-warning shadow-sm">
  <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-cloud-arrow-down me-1"></i> Sinkronisasi OSCE</h6>
  </div>
  <div class="card-body d-flex gap-2">
    <button id="btn-pull-osce" class="btn btn-success btn-sm">
      <i class="bi bi-download me-1"></i> 1. Fetch Data OSCE dari VPS
    </button>
    <button id="btn-push-osce" class="btn btn-warning btn-sm">
      <i class="bi bi-upload me-1"></i> 2. Push Hasil OSCE ke VPS
    </button>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <ul class="nav nav-tabs mb-3" id="osceTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="peserta-tab" data-bs-toggle="tab" data-bs-target="#peserta" type="button" role="tab">Peserta</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="station-tab" data-bs-toggle="tab" data-bs-target="#station" type="button" role="tab">Station</button>
      </li>
    </ul>

    <div class="tab-content" id="osceTabsContent">
      <!-- Tab Peserta -->
      <div class="tab-pane fade show active" id="peserta" role="tabpanel">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar Peserta</strong>
            <button id="btnTambahPeserta" class="btn btn-sm btn-primary">
              <i class="bi bi-person-plus-fill me-1"></i> Tambah Peserta
            </button>
          </div>
          <div class="card-body p-0" id="wrapPeserta">
            <?= view('\Modules\Admin\Views\ujian\partials\peserta_osce_table', [
              'kode'=>$uji['kode'],
              'rows'=>[]
            ]) ?>
          </div>
        </div>
      </div>

      <!-- Tab Station -->
      <div class="tab-pane fade" id="station" role="tabpanel">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar Station</strong>
            <button id="btnTambahStation" class="btn btn-sm btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Tambah Station
            </button>
          </div>
          <div class="card-body p-0" id="wrapStation">
            <!-- diisi ajax -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: pilih mahasiswa -->
<div class="modal fade" id="modalMahasiswa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Peserta Ujian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBodyMahasiswa">
        <!-- diisi ajax -->
      </div>
    </div>
  </div>
</div>

<!-- MODAL ADD/EDIT STATION -->
<div class="modal fade" id="modalOsceSoal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="formOsceSoal" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="oscesoal_id">
      <input type="hidden" name="osce_id" value="<?= $uji['id'] ?>">
      <div class="modal-header">
        <h5 class="modal-title">Station – OSCE</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-12">
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
            <label class="form-label">Kode Station</label>
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
        <button type="submit" class="btn btn-success" id="btnSaveStation"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: History Station (per mahasiswa) -->
<div class="modal fade" id="modalHistoryStation" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex flex-column">
          <h5 class="modal-title mb-0">Ujian OSCE</h5>
          <div class="text-muted small">— <span id="hsNamaHeader">-</span></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0" id="tblHistoryStation">
            <thead class="table-light">
              <tr>
                <th style="width:60px">#</th>
                <th style="min-width:240px">Nama Station</th>
                <th style="min-width:120px">Kode Station</th>
                <th style="min-width:120px">Global Skor</th>
                <th style="min-width:100px">GPS</th>
                <th style="min-width:120px">Status</th>
                <th style="min-width:180px">Waktu / Created</th>
              </tr>
            </thead>
            <tbody><!-- diisi via JS --></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer justify-content-between">
        <div>
          <input type="hidden" id="hsMahasiswaId" value="">
          <button type="button" class="btn btn-outline-danger btn-export-pdf" id="btnHsExportPdf">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
          </button>
        </div>
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>


<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  (function(){

    const kode      = <?= json_encode($uji['kode']) ?>;
    const $wrap     = $('#wrapPeserta');
    const modalMEl  = document.getElementById('modalMahasiswa');
    const modalMhs  = new bootstrap.Modal(modalMEl);

  // ================== PESERTA OSCE ==================

    function loadPeserta(){
      Loader.show();
      $.get('<?= site_url('admin/ujian/praktek/peserta') ?>/' + encodeURIComponent(kode), function(html){
        $wrap.html(html);
        const count = $('#tblPeserta tbody tr').length;
        $('#jmlPeserta').text(count);
      }).always(() => Loader.hide());
    }
    loadPeserta();

    let mhsReq = null;
    function loadModalList(q = '', page = 1, silent = false){
      const url = '<?= site_url('admin/ujian/praktek/pilih-mahasiswa') ?>/'+encodeURIComponent(kode)
      + '?q=' + encodeURIComponent(q) + '&page=' + page;

      if (mhsReq && mhsReq.readyState !== 4) { try{ mhsReq.abort(); }catch(e){} }

      const target = $('#mhsListWrap').length ? $('#mhsListWrap') : $('#modalBodyMahasiswa');
      const run = () => (mhsReq = $.get(url).done(html => target.html(html)));

      return silent ? run() : withListLoading(target, run);
    }

  // klik tombol Tambah Peserta -> tampilkan modal list mahasiswa
    $('#btnTambahPeserta').on('click', function(){
      loadModalList('', 1, false);
      modalMhs.show();
    });

  // pencarian dalam modal
    $(document).on('input', '#mhsSearch', debounce(function(){
      loadModalList(this.value || '', 1, true);
    }, 250));

  // pagination dalam modal
    $(document).on('click', '.mhs-page', function(e){
      e.preventDefault();
      const page = $(this).data('page') || 1;
      const q    = $('#mhsSearch').val() || '';
      loadModalList(q, page, false);
    });

  // TAMBAH peserta OSCE
    $(document).on('click', '.btn-pilih', function(){
      const mid = $(this).data('id');
      Swal.fire({
        title: 'Tambahkan peserta ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, tambahkan',
        cancelButtonText: 'Batal'
      }).then((r) => {
        if (!r.isConfirmed) return;
        Loader.show();
        $.postCSRF('<?= site_url('admin/ujian/praktek/peserta-add') ?>/' + encodeURIComponent(kode) + '/' + mid)
        .done(function(res){
          if (res.status === 'ok') {
            swalToast('Ditambahkan');
            loadPeserta(); // refresh tabel peserta
            // refresh list di modal supaya mahasiswa yg baru terdaftar hilang (tidak ada tombol +)
            const q = $('#mhsSearch').val() || '';
            const p = $('.mhs-page.active').data('page') || 1;
            loadModalList(q, p, true);
          } else {
            Swal.fire('Gagal', res.message || 'Tidak dapat menambah', 'error');
          }
        })
        .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menambah', 'error'))
        .always(() => Loader.hide());
      });
    });

  // HAPUS peserta OSCE
    $(document).on('click', '.btn-hapus-peserta', function(){
      const mid = $(this).data('id');
      Swal.fire({
        title: 'Hapus peserta ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((r) => {
        if (!r.isConfirmed) return;
        Loader.show();
        $.postCSRF('<?= site_url('admin/ujian/praktek/peserta-del') ?>/' + encodeURIComponent(kode) + '/' + mid)
        .done(function(res){
          if (res.status === 'ok') {
            swalToast('Dihapus');
            loadPeserta();
            if ($('#modalMahasiswa').hasClass('show')) {
              const q = $('#mhsSearch').val() || '';
              const p = $('.mhs-page.active').data('page') || 1;
              loadModalList(q, p, true);
            }
          } else {
            Swal.fire('Gagal', res.message || 'Tidak dapat menghapus', 'error');
          }
        })
        .fail(xhr => Swal.fire('Gagal', xhr?.responseJSON?.message || 'Tidak dapat menghapus', 'error'))
        .always(() => Loader.hide());
      });
    });

  // ================== HISTORY STATION (kode lama) ==================

    const modalHEl = document.getElementById('modalHistoryStation');
    const modalH   = new bootstrap.Modal(modalHEl);

    function esc(s){
      if (s === null || s === undefined) return '-';
      return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    $(document).on('click', '.btn-history', function(){
      const sid = $(this).data('station-id');
    const mid = $(this).data('mhs-id'); // ⬅️ pakai data-station-id
    const $tb = $('#tblHistoryStation tbody').empty();
    $('#hsMahasiswaId').val(mid);

    $tb.append('<tr><td colspan="7" class="text-center py-4">Memuat...</td></tr>');

    $.get('<?= site_url('admin/osce-soal/history-mahasiswa') ?>/' + mid)
    .done(function(res){
      if(res.status !== 'ok'){ throw new Error(res.message || 'Gagal'); }

      const list    = res.list || [];
      const station = res.station || {};
       MHS = res.mahasiswa || {};
      $('#hsNamaHeader').text((MHS.nama||'-') + (MHS.nim ? ' — '+MHS.nim : ''));

        // Header sekarang pakai nama station + kode
    

      $tb.empty();
      if(list.length === 0){
        $tb.append('<tr><td colspan="7" class="text-center text-muted py-4">Belum ada history.</td></tr>');
        modalH.show();
        return;
      }

      list.forEach(function(r, i){
        const badge = (r.status === 'Sudah Ujian') ? 'success' : 'secondary';
        $tb.append(
          '<tr>'+
          '<td>'+ (i+1) +'</td>'+
              '<td>'+ esc(r.nama_station) +'</td>'+   // kalau mau tampil mahasiswa
              '<td>'+ esc(r.station_kode) +'</td>'+
              '<td>'+ esc(r.global_skor) +'</td>'+
              '<td>'+ (r.gps_text ?? '-') +'</td>'+
              '<td><span class="badge bg-'+badge+'">'+ esc(r.status) +'</span></td>'+

              '<td><small>'+ esc(r.tanggal_jam_ujian || '-') +'</small></td>'+
              '</tr>'
              );
      });

      modalH.show();
    })
    .fail(function(xhr){
      const msg = xhr?.responseJSON?.message || 'Tidak dapat memuat history';
      if (window.Swal) Swal.fire('Gagal', msg, 'error'); else alert(msg);
    });
  });

    $(document).on('click', '#btnHsExportPdf', function () {
      const mhsId = $('#hsMahasiswaId').val();
      if (!mhsId) {
        Swal.fire('Informasi', 'Mahasiswa belum dipilih.', 'info');
        return;
      }

  // buka PDF di tab / jendela baru
      const url = '<?= site_url('admin/osce/history-pdf') ?>/' + mhsId;
      window.open(url, '_blank');
    });

    // ================== STATION MANAGEMENT ==================
    const osceId = <?= json_encode($uji['id']) ?>;
    const $wrapStation = $('#wrapStation');
    const modalStationEl = document.getElementById('modalOsceSoal');
    const modalStation = new bootstrap.Modal(modalStationEl);

    function loadStation(url) {
      const u = (url || '<?= site_url('admin/osce-soal/table') ?>?osce_id=' + osceId) + ((url||'').includes('?') ? '&' : '') + '&frag=list';
      Loader.show();
      $.get(u, function(html) {
        $wrapStation.html(html);
        // Sembunyikan kolom "OSCE / Kode" karena sudah berada di detail OSCE tersebut
        $wrapStation.find('th:nth-child(3), td:nth-child(3)').hide();
      }).always(() => Loader.hide());
    }

    // Trigger load when tab shown
    $('#station-tab').on('shown.bs.tab', function() {
      loadStation();
    });

    function buildSoalSelect($el, parent){
      $el.select2({
        width: '100%',
        placeholder: 'Pilih Soal',
        allowClear: true,
        dropdownParent: $(parent),
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
        placeholder: 'Cari pengawas (NIP/Nama)',
        allowClear: true,
        dropdownParent: $(parent),
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
      });
    }

    $('#btnTambahStation').on('click', function() {
      $('#formOsceSoal')[0].reset();
      $('#oscesoal_id').val('');
      $('.js-soal, .js-pengawas').empty();
      buildSoalSelect($('.js-soal'), '#modalOsceSoal');
      buildPengawasSelect($('.js-pengawas'), '#modalOsceSoal');
      modalStation.show();
    });

    $(document).on('click', '.btn-edit', function() {
      const id = $(this).data('id');
      Loader.show();
      $.get('<?= site_url('admin/osce-soal/get') ?>/' + id).done(function(res) {
        if(res.status === 'ok') {
          const d = res.data;
          $('#oscesoal_id').val(d.id);
          
          const $soal = $('.js-soal').empty();
          const $peng = $('.js-pengawas').empty();
          buildSoalSelect($soal, '#modalOsceSoal');
          buildPengawasSelect($peng, '#modalOsceSoal');

          if (d.soal_id) {
            $soal.append(new Option(d.soal_register || ('ID '+d.soal_id), d.soal_id, true, true)).trigger('change');
          }
          if (d.nip_pengawas || d.nama_pengawas) {
            const t = (d.nip_pengawas||'')+' - '+(d.nama_pengawas||'');
            $peng.append(new Option(t, d.nip_pengawas||'', true, true)).trigger('change');
            $('[name="nip_pengawas"]').val(d.nip_pengawas||'');
            $('[name="nama_pengawas"]').val(d.nama_pengawas||'');
          }

          $('[name="nama_station"]').val(d.nama_station);
          $('[name="kode"]').val(d.kode);
          $('[name="waktu"]').val(d.waktu);
          modalStation.show();
        }
      }).always(() => Loader.hide());
    });

    $('#formOsceSoal').on('submit', function(e) {
      e.preventDefault();
      const id = $('#oscesoal_id').val();
      const url = id ? '<?= site_url('admin/osce-soal/update') ?>/' + id : '<?= site_url('admin/osce-soal/create') ?>';
      
      const $btn = $('#btnSaveStation').prop('disabled', true);
      Loader.show();

      $.ajax({
        url: url,
        method: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false
      }).done(function(res) {
        if(res.status === 'ok') {
          modalStation.hide();
          swalToast('Data tersimpan');
          loadStation();
        } else {
          Swal.fire('Gagal', res.message || 'Gagal menyimpan', 'error');
        }
      }).fail(xhr => {
        Swal.fire('Gagal', xhr?.responseJSON?.message || 'Gagal menyimpan', 'error');
      }).always(() => {
        Loader.hide();
        $btn.prop('disabled', false);
      });
    });

    $(document).on('click', '.btn-del', function(e) {
      e.preventDefault();
      const url = $(this).data('url');
      Swal.fire({
        title:'Hapus station ini?', icon:'warning',
        showCancelButton:true, confirmButtonText:'Ya, hapus'
      }).then(r => {
        if(!r.isConfirmed) return;
        Loader.show();
        $.postCSRF(url).done(res => {
          if(res.status==='ok') {
            swalToast('Dihapus');
            loadStation();
          }
        }).always(() => Loader.hide());
      });
    });

    $(document).on('click', '#wrapStation .js-page', function(e) {
      e.preventDefault();
      loadStation($(this).attr('href'));
    });

    const osceKode = <?= json_encode($uji['kode']) ?>;

    $('#btn-pull-osce').click(function() {
        Swal.fire({
            icon: 'question',
            title: 'Sinkronisasi OSCE?',
            html: `Fetch data sesi <strong>${osceKode}</strong> dari VPS ke database lokal.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Fetch',
            cancelButtonText: 'Batal',
        }).then(r => {
            if (!r.isConfirmed) return;
            const $btn = $('#btn-pull-osce').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Downloading...');
            $.get('<?= site_url('admin/ujian/praktek/pull') ?>/' + encodeURIComponent(osceKode))
                .done(res => {
                    const icon = res.status === 'success' ? 'success' : 'error';
                    Swal.fire({ icon, title: res.status === 'success' ? 'Berhasil' : 'Gagal', text: res.message })
                        .then(() => { if (res.status === 'success') location.reload(); });
                })
                .fail(() => Swal.fire({ icon: 'error', title: 'Network Error', text: 'Tidak dapat terhubung ke server.' }))
                .always(() => $btn.prop('disabled', false).html('<i class="bi bi-download me-1"></i> 1. Fetch Data OSCE dari VPS'));
        });
    });

    $('#btn-push-osce').click(function() {
        Swal.fire({
            icon: 'question',
            title: 'Kirim Hasil OSCE?',
            html: `Semua nilai OSCE lokal untuk <strong>${osceKode}</strong> akan dikirim ke server utama.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
        }).then(r => {
            if (!r.isConfirmed) return;
            const $btn = $('#btn-push-osce').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Uploading...');
            $.post('<?= site_url('admin/ujian/praktek/push') ?>/' + encodeURIComponent(osceKode), {
                [csrfTokenName]: csrfTokenValue
            })
                .done(res => Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.status === 'success' ? 'Berhasil' : 'Gagal', text: res.message }))
                .fail(() => Swal.fire({ icon: 'error', title: 'Network Error', text: 'Tidak dapat terhubung ke server.' }))
                .always(() => $btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> 2. Push Hasil OSCE ke VPS'));
        });
    });

  })();
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<?php $this->endSection(); ?>
