<!-- Modules/Admin/Views/osce_soal/detail.php -->
<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); ?>
<style>
  .pill{display:inline-block;padding:.25rem .6rem;border-radius:999px;font-weight:600;font-size:.85rem}
  .pill-ok{background:#e6f7ed;color:#107a42}      /* Lulus */
  .pill-no{background:#fdeaea;color:#b42318}      /* Tidak Lulus */
  .pill-mid{background:#fff7ed;color:#9a3412}     /* Borderline */
  .card-table{border-radius:12px;overflow:hidden}
  .table-clean thead th{background:#f8fafc;border-bottom:1px solid #e5e7eb}
  .table-clean td,.table-clean th{vertical-align:middle}
  #modalHistoryStation .modal-header{
    align-items: center;        /* judul & tombol sejajar tengah */
  }
  #modalHistoryStation .hs-actions{
    display: flex;
    align-items: center;
    gap: .5rem;                 /* jarak antara tombol */
  }
  #modalHistoryStation .btn-export-pdf{
    padding: .25rem .6rem;
    font-size: .8rem;
    border-radius: 999px;       /* pill */
  }
</style>


<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="page-title mb-0">Detail Station</h2>
  <a href="<?= site_url('admin/osce-soal') ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Kembali
  </a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <table class="table table-sm mb-0">
          <tr><th style="width:160px">OSCE</th><td><?= esc($station['osce_nama'] ?? '-') ?></td></tr>
          <tr><th>Kode OSCE</th><td><span class="badge bg-primary"><?= esc($station['osce_kode'] ?? '-') ?></span></td></tr>
          <tr><th>Tanggal</th><td><?= esc($station['osce_tanggal'] ?? '-') ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm mb-0">
          <tr><th style="width:160px">Nama Station</th><td><?= esc($station['nama_station'] ?? '-') ?></td></tr>
          <tr><th>Kode Station</th><td><?= esc($station['kode'] ?? '-') ?></td></tr>
          <tr><th>Waktu (menit)</th><td><?= (int)($station['waktu'] ?? 0) ?></td></tr>
          <tr><th>Pengawas</th><td><?= esc(($station['nip_pengawas']??'').' - '.($station['nama_pengawas']??'')) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Mahasiswa Terdaftar (Kode: <?= esc($station['osce_kode']) ?>)</strong>
    <span class="badge bg-secondary"><?= count($mhs) ?> peserta</span>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th style="min-width:140px">NIM</th>
          <th style="min-width:260px">Nama</th>
          <th style="min-width:120px">Kelas</th>
          <th style="width:120px">Aksi</th>
          
        </tr>
      </thead>
      <tbody>
        <?php if(empty($mhs)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Belum ada mahasiswa terdaftar.</td></tr>
        <?php else: $i=1; foreach($mhs as $row): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= esc($row['nim']) ?></td>
            <td><?= esc($row['nama']) ?></td>
            <td><?= esc($row['kelas'] ?? '-') ?></td>
            <td>
              <button
              class="btn btn-sm btn-outline-info btn-history"
              data-station="<?= (int)$station['id'] ?>"
              data-mid="<?= (int)($row['mahasiswa_id'] ?? $row['id']) // pastikan controller alias m.id AS mahasiswa_id ?>">
              <i class="bi bi-clock-history me-1"></i> History
            </button>
          </td>

        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</div>
<div class="modal fade" id="modalHistoryStation" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div class="d-flex flex-column">
          <h5 class="modal-title mb-0">Ujian OSCE</h5>
          <div class="text-muted small">— <span id="hsNamaHeader">-</span></div>
        </div>

        <div class="hs-actions">
          <input type="hidden" id="hsMahasiswaId" value="">
          <button type="button"
          class="btn btn-sm btn-outline-danger btn-export-pdf"
          id="btnHsExportPdf">
          <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </button>
        <button type="button"
        class="btn-close"
        data-bs-dismiss="modal"
        aria-label="Close"></button>
      </div>
    </div>



    <div class="modal-body">
      <!-- Toolbar: cari + sort -->
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <div class="flex-grow-1">
          <input id="hsSearch" class="form-control" placeholder="Cari Mahasiswa / NIM / Nama Station">
        </div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Sort by</button>
          <ul class="dropdown-menu dropdown-menu-end" id="hsSortMenu">
            <li><a class="dropdown-item" data-sort="recent">Terbaru</a></li>
            <li><a class="dropdown-item" data-sort="nama">Nama</a></li>
            <li><a class="dropdown-item" data-sort="nilai">Nilai</a></li>
            <li><a class="dropdown-item" data-sort="station">Nama Station</a></li>
          </ul>
        </div>
      </div>

      <div class="card card-table">
        <div class="table-responsive">
          <table class="table table-clean align-middle mb-0" id="tblHistoryStation">
            <thead>
              <tr>
                <th style="width:36px">
                  <input type="checkbox" id="hsCheckAll" disabled> <!-- dekoratif -->
                </th>
                <th style="width:56px">No</th>
                <th style="min-width:120px">NIM</th>
                <th style="min-width:240px">Nama</th>
                <th style="min-width:200px">Nama Station</th>
                <th style="min-width:120px">Status</th>
                <th style="min-width:110px">GPS</th>  
                <th style="min-width:80px">Nilai</th>
                <th style="min-width:180px">Tanggal Ujian</th>
                <th style="min-width:180px">Waktu Pengerjaan</th>

              </tr>
            </thead>
            <tbody><!-- diisi via JS --></tbody>
          </table>
        </div>
      </div>

      <!-- pager -->
      <div class="d-flex justify-content-center align-items-center gap-2 mt-3" id="hsPager"></div>
      <div class="small text-muted mt-1" id="hsInfo"></div>
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
    const modalEl = document.getElementById('modalHistoryStation');
    const modal   = new bootstrap.Modal(modalEl);

    const $tblBody = $('#tblHistoryStation tbody');
    const $search  = $('#hsSearch');
    const $pager   = $('#hsPager');
    const $info    = $('#hsInfo');

  let RAW = [];       // data dari server (array history semua station utk 1 mahasiswa)
  let MHS = {};       // identitas mahasiswa
  let STATE = { q:'', sort:'recent', page:1, per:5 };
  function mapGPS(v){
    if (v === null || v === undefined || v === '') return {text:'-', cls:''};
    const n = Number(v);
    if (n === 0) return {text:'Tidak Lulus', cls:'pill-no'};
    if (n === 1) return {text:'Borderline',  cls:'pill-mid'};
    if (n === 2) return {text:'Lulus',       cls:'pill-ok'};
    // fallback kalau ada nilai lain
    return {text:String(v), cls:''};
  }
  function esc(s){ if(s==null) return '-'; return String(s)
  .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
  .replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

  function compute(list){
    // search
    let q = STATE.q.trim().toLowerCase();
    let arr = list.map(r => {
      // status by global_skor (null => belum)
      r._statusText = (r.global_skor==null ? 'Belum Ujian' : 'Sudah Ujian');
      r._nim  = MHS.nim || r.nim || '';
      r._nama = MHS.nama || r.mahasiswa_nama || '';
      return r;
    });

    if(q){
      arr = arr.filter(r =>
        (r._nim||'').toLowerCase().includes(q) ||
        (r._nama||'').toLowerCase().includes(q) ||
        (r.nama_station||'').toLowerCase().includes(q)||
        (r._gpsText||'').toLowerCase().includes(q)      
        );
    }

    // sort
    switch(STATE.sort){
    case 'nama':
      arr.sort((a,b)=> (a._nama||'').localeCompare(b._nama||'')); break;
    case 'nilai':
      arr.sort((a,b)=> (b.global_skor??-1) - (a.global_skor??-1)); break;
    case 'station':
      arr.sort((a,b)=> (a.nama_station||'').localeCompare(b.nama_station||'')); break;
      default: // recent
        arr.sort((a,b)=> new Date(b.created_at||0) - new Date(a.created_at||0));
      }

      return arr;
    }

    function render(){
      const list = compute(RAW);
      const total = list.length;
      const pages = Math.max(1, Math.ceil(total / STATE.per));
      STATE.page = Math.min(Math.max(1, STATE.page), pages);

    // slice
      const start = (STATE.page-1)*STATE.per;
      const view  = list.slice(start, start+STATE.per);

    // table rows
      $tblBody.empty();
      if(view.length===0){
        $tblBody.append('<tr><td colspan="8" class="text-center text-muted py-4">Belum ada data.</td></tr>');
      }else{
        view.forEach((r,idx)=>{
          const no = start + idx + 1;
          const done = (r.global_skor!=null);
          const pill = done ? '<span class="pill pill-ok">Sudah Ujian</span>'
          : '<span class="pill pill-no">Belum Ujian</span>';
          const nilai = done ? esc(r.global_skor) : '<span class="text-muted">Belum ada Nilai</span>';
          const gpsPill = r.gps_text
          ? `<span class="pill ${r.gps_text}">${esc(r._gpsText)}</span>`
          : esc(r._gpsText);

        // link detail ke halaman detail station (perlu station_id dari server)
          const detailHref = r.station_id
          ? '<?= site_url('admin/osce-soal/detail') ?>/' + r.station_id
          : 'javascript:void(0)';

          const detailBtn = '<a class="btn btn-sm btn-outline-primary"'
          + (r.station_id ? '' : ' aria-disabled="true" tabindex="-1"')
          + ' href="'+ detailHref +'">Detail</a>';

          $tblBody.append(
            '<tr>'
            + '<td><input type="checkbox" disabled></td>'
            + '<td>'+ no +'.</td>'
            + '<td>'+ esc(r._nim) +'</td>'
            + '<td>'+ esc(r._nama) +'</td>'
            + '<td>'+ esc(r.nama_station || '-') +'</td>'
            + '<td>'+ pill +'</td>'
            + '<td>'+ esc(r.gps_text) +'</td>'  
            + '<td>'+ nilai +'</td>'
            + '<td>'+ esc(r.tanggal_jam_ujian  || '-') +'</td>' 
            + '<td>'+ esc(r.waktu || '-') +'</td>' 
            + '</tr>'
            );
        });
      }

    // pager
      $pager.empty();
      if (pages > 1){
        const prevDis = (STATE.page<=1) ? 'disabled' : '';
        const nextDis = (STATE.page>=pages) ? 'disabled' : '';
        $pager.append('<button class="btn btn-sm btn-outline-secondary hs-prev" '+prevDis+'>‹</button>');
        $pager.append('<span class="btn btn-sm btn-primary disabled mx-2">'+STATE.page+'</span>');
        $pager.append('<button class="btn btn-sm btn-outline-secondary hs-next" '+nextDis+'>›</button>');
      }
      $info.text(`Menampilkan ${view.length ? (start+1) : 0}–${start+view.length} dari ${total} data`);
    }

  // events
    $(document).on('input', '#hsSearch', function(){ STATE.q=this.value; STATE.page=1; render(); });
    $(document).on('click', '#hsSortMenu .dropdown-item', function(){
      STATE.sort = $(this).data('sort') || 'recent';
      render();
    });
    $(document).on('click', '.hs-prev', function(){ STATE.page--; render(); });
    $(document).on('click', '.hs-next', function(){ STATE.page++; render(); });

  // open modal
    $(document).on('click', '.btn-history', function(){
      const station = $(this).data('station');
      const mid = $(this).data('mid');
    // id mahasiswa


      $('#hsMahasiswaId').val(mid);

    // reset UI

      $tblBody.html('<tr><td colspan="8" class="text-center py-4">Memuat...</td></tr>');
      $search.val(''); STATE = { q:'', sort:'recent', page:1, per:5 };

      $.get('<?= site_url('admin/osce-soal/history-mahasiswa') ?>/'+mid)
      .done(function(res){
        if(res.status!=='ok') throw new Error(res.message || 'Gagal');
        RAW = res.list || [];
        MHS = res.mahasiswa || {};
        $('#hsNamaHeader').text((MHS.nama||'-') + (MHS.nim ? ' — '+MHS.nim : ''));
        render();
        modal.show();
      })
      .fail(function(xhr){
        const msg = xhr?.responseJSON?.message || 'Tidak dapat memuat history';
        if (window.Swal) Swal.fire('Gagal', msg, 'error'); else alert(msg);
      });
    });
  // tombol Export PDF di modal history
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

  })();
</script>

<?php $this->endSection(); ?>
