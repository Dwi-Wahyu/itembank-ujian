<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content');
$min = (int)($uji['nilai_minimum'] ?? $uji['nilai_minimum'] ?? 0);
 ?>
<style>
  /* baris merah full + teks putih */
  .table .row-red > * {
    background-color: #dc3545 !important; /* merah */
    color: #fff !important;               /* teks putih */
    border-color: rgba(0,0,0,.1) !important; /* biar border tetap terlihat */
  }

  /* saat hover di table-hover */
  .table.table-hover .row-red:hover > * {
    background-color: #bb2d3b !important; /* sedikit lebih gelap saat hover */
    color: #fff !important;
  }

  /* ikon/link/tombol di dalam baris merah tetap putih */
  .table .row-red a,
  .table .row-red i {
    color: #fff !important;
  }
  .table .row-red .btn {
    color: #dc3545 !important;            /* tombol light tetap kontras */
    background-color: #fff !important;
    border-color: #fff !important;
  }
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/dashboard') ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= site_url('admin/ujian/teori') ?>">Ujian Teori</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($uji['nama'])." - ".esc($uji['kode']) ?></li>
    </ol>
  </nav>
</div>

<div class="card mb-3">
  <div class="card-body p-0">
    <div class="card-body d-flex gap-3">
            <button id="btn-push-results" class="btn btn-warning px-4">
                <i class="fa fa-upload"></i> Push Final Grades to VPS
            </button>
        </div>

    <table class="table table-sm mb-0">
      <tr><th class="w-25">Departemen</th><td class="text-end"><?= esc($dep) ?></td></tr>
      <tr><th>Blok</th><td class="text-end"><?= esc($blok) ?></td></tr>
      <tr><th>Tanggal</th><td class="text-end"><?= tgl_id($uji['tanggal']) ?></td></tr>
      <tr><th>Waktu</th><td class="text-end">
        <?= $uji['mulai'] ? substr($uji['mulai'],0,5) : '-' ?> s.d <?= $uji['selesai'] ? substr($uji['selesai'],0,5) : '-' ?>
      </td></tr>
      <tr><th>Jlh. Peserta</th><td class="text-end" id="jmlPeserta"><?= (int)$jumlah ?></td></tr>
        <tr><th>Passing Grade</th>
        <td class="text-end">
          <span class="badge bg-secondary"><?= $min ?></span>
        </td>
      </tr>
    </table>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
    <strong class="text-primary"><i class="bi bi-people-fill me-1"></i> Peserta Ujian</strong>
    <button id="btnTambahPeserta" class="btn btn-sm btn-primary">
      <i class="bi bi-person-plus-fill me-1"></i> Tambah Peserta
    </button>
  </div>
  <div class="card-body p-0" id="wrapPeserta" style="min-height: 100px;">
    <!-- diisi ajax -->
  </div>
</div>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
    <strong class="text-success"><i class="bi bi-journal-text me-1"></i> Daftar Soal dalam Paket</strong>
    <button id="btnTambahSoal" class="btn btn-sm btn-success">
      <i class="bi bi-plus-circle-fill me-1"></i> Pilih Soal dari Bank
    </button>
  </div>
  <div class="card-body p-0" id="wrapSoal" style="min-height: 100px;">
    <!-- diisi ajax -->
  </div>
</div>

<!-- MODAL: pilih mahasiswa -->
<div class="modal fade" id="modalMahasiswa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i> Pilih Mahasiswa</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" id="modalBodyMahasiswa" style="min-height: 300px;">
        <!-- diisi ajax -->
      </div>
    </div>
  </div>
</div>

<!-- MODAL: pilih soal -->
<div class="modal fade" id="modalSoal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-journal-plus me-1"></i> Pilih Soal dari Bank Soal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0" id="modalBodySoal" style="min-height: 300px;">
        <div class="p-5 text-center text-muted">Memuat soal...</div>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const kode   = <?= json_encode($uji['kode']) ?>;
  const paket_id = <?= (int)$uji['id'] ?>;
  const $wrapPeserta = $('#wrapPeserta');
  const $wrapSoal    = $('#wrapSoal');
  
  const modalMhsEl= document.getElementById('modalMahasiswa');
  const modalMhs  = new bootstrap.Modal(modalMhsEl);
  
  const modalSoalEl= document.getElementById('modalSoal');
  const modalSoal  = new bootstrap.Modal(modalSoalEl);

  // --- PESERTA LOGIC ---
  function loadPeserta(){
    $wrapPeserta.html('<div class="p-4 text-center text-muted">Memuat peserta...</div>');
    $.get('<?= site_url('admin/ujian/teori/peserta') ?>/'+encodeURIComponent(kode), function(html){
      $wrapPeserta.html(html);
      const count = $('#tblPeserta tbody tr').not(':has(td[colspan])').length;
      $('#jmlPeserta').text(count);
    });
  }
  loadPeserta();

  function loadModalMhs(q='', page=1){
    const $body = $('#modalBodyMahasiswa');
    $body.html('<div class="p-5 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Memuat data mahasiswa...</div>');
    
    const url = '<?= site_url('admin/ujian/teori/pilih-mahasiswa') ?>/'+encodeURIComponent(kode)
              + '?q=' + encodeURIComponent(q) + '&page=' + page;
    
    $.get(url, function(html){
      $body.html(html);
    }).fail(()=> $body.html('<div class="p-5 text-center text-danger">Gagal memuat data.</div>'));
  }

  $('#btnTambahPeserta').on('click', function(){
    loadModalMhs('', 1);
    modalMhs.show();
  });

  $(document).on('input', '#mhsSearch', debounce(function(){
    loadModalMhs(this.value || '', 1);
  }, 300));

  $(document).on('click', '.mhs-page', function(e){
    e.preventDefault();
    loadModalMhs($('#mhsSearch').val() || '', $(this).data('page') || 1);
  });

  $(document).on('click', '.btn-pilih', function(){
    const mid = $(this).data('id');
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    
    $.postCSRF('<?= site_url('admin/ujian/teori/peserta-add') ?>/'+encodeURIComponent(kode)+'/'+mid)
     .done(function(res){
        if(res.status==='ok'){
          swalToast('Berhasil menambahkan peserta');
          loadPeserta();
          loadModalMhs($('#mhsSearch').val()||'', $('.mhs-page.active').data('page')||1);
        } else {
          Swal.fire('Gagal', res.message || 'Error', 'error');
          loadModalMhs($('#mhsSearch').val()||'', $('.mhs-page.active').data('page')||1);
        }
     });
  });

  $(document).on('click','.btn-hapus-peserta', function(){
    const mid = $(this).data('id');
    Swal.fire({ title:'Hapus peserta?', text: "Peserta akan dikeluarkan dari sesi ini.", icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus' })
      .then((r)=>{
        if(!r.isConfirmed) return;
        Loader.show();
        $.postCSRF('<?= site_url('admin/ujian/teori/peserta-del') ?>/'+encodeURIComponent(kode)+'/'+mid)
         .done(function(res){
            if(res.status==='ok'){
              swalToast('Peserta dihapus');
              loadPeserta();
            }
         }).always(()=> Loader.hide());
      });
  });

  // --- SOAL LOGIC ---
  function loadSoal(){
    $wrapSoal.html('<div class="p-4 text-center text-muted">Memuat daftar soal...</div>');
    $.get('<?= site_url('admin/ujian/teori/soal-list') ?>/'+paket_id, function(html){
      $wrapSoal.html(html);
    });
  }
  loadSoal();

  function loadModalSoal(q='', page=1){
    const $body = $('#modalBodySoal');
    $body.html('<div class="p-5 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Memuat bank soal...</div>');
    
    const url = '<?= site_url('admin/ujian/teori/pilih-soal') ?>/'+paket_id
              + '?q=' + encodeURIComponent(q) + '&page=' + page;
              
    $.get(url, function(html){
      $body.html(html);
    }).fail(()=> $body.html('<div class="p-5 text-center text-danger">Gagal memuat bank soal.</div>'));
  }

  $('#btnTambahSoal').on('click', function(){
    loadModalSoal('', 1);
    modalSoal.show();
  });

  $(document).on('input', '#soalSearch', debounce(function(){
    loadModalSoal(this.value || '', 1);
  }, 300));

  $(document).on('click', '.soal-page', function(e){
    e.preventDefault();
    loadModalSoal($('#soalSearch').val() || '', $(this).data('page') || 1);
  });

  $(document).on('click', '.btn-pilih-soal', function(){
    const sid = $(this).data('id');
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    
    $.postCSRF('<?= site_url('admin/ujian/teori/soal-add') ?>/'+paket_id+'/'+sid)
     .done(function(res){
        if(res.status==='ok'){
          swalToast('Soal berhasil ditambahkan');
          loadSoal();
          loadModalSoal($('#soalSearch').val()||'', $('.soal-page.active').data('page')||1);
        } else {
          Swal.fire('Gagal', res.message || 'Error', 'error');
          loadModalSoal($('#soalSearch').val()||'', $('.soal-page.active').data('page')||1);
        }
     });
  });

  $(document).on('click', '.btn-hapus-soal', function(){
    const sid = $(this).data('id');
    Swal.fire({ title:'Hapus soal?', text: "Soal akan dilepas dari paket ujian ini.", icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus' })
      .then((r)=>{
        if(!r.isConfirmed) return;
        Loader.show();
        $.postCSRF('<?= site_url('admin/ujian/teori/soal-del') ?>/'+sid)
         .done(function(res){
            if(res.status==='ok'){
              swalToast('Soal dilepas');
              loadSoal();
            }
         }).always(()=> Loader.hide());
      });
  });

})();

$(document).ready(function() {
    const examId   = '<?= $ujian['id'] ?? 0; ?>';
    const examCode = '<?= esc($ujian['kode'] ?? ''); ?>';

    if (examId !== '0') {
        setInterval(reloadProctorGrid, 10000);
        reloadProctorGrid();
    }

    // ---- Pull ----
    $('#btn-pull-soal').click(function() {
        Swal.fire({
            title: 'Sinkronisasi Data Ujian',
            input: 'text',
            inputValue: examCode,
            inputLabel: 'Kode Ujian (dari VPS)',
            showCancelButton: true,
            confirmButtonText: 'Fetch',
            cancelButtonText: 'Batal',
            inputValidator: (v) => (!v.trim() ? 'Kode tidak boleh kosong' : null)
        }).then(result => {
            if (!result.isConfirmed) return;
            const code = result.value.trim();
            const $btn = $('#btn-pull-soal').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Downloading...');

            $.get('/admin/ujian/teori/pull/' + encodeURIComponent(code))
                .done(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                        $btn.prop('disabled', false).html('<i class="fa fa-download"></i> 1. Fetch Exam Data from VPS');
                    }
                })
                .fail(() => {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Pastikan server memiliki akses ke VPS.' });
                    $btn.prop('disabled', false).html('<i class="fa fa-download"></i> 1. Fetch Exam Data from VPS');
                });
        });
    });

    // ---- Push ----
    $('#btn-push-results').click(function() {
        Swal.fire({
            icon: 'question',
            title: 'Kirim Hasil Ujian?',
            html: `Semua nilai lokal untuk sesi <strong>${examCode}</strong> akan dikirim ke server utama.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
        }).then(result => {
            if (!result.isConfirmed) return;
            const $btn = $('#btn-push-results').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

            $.post('/admin/ujian/teori/push/' + encodeURIComponent(examCode), {
                [csrfTokenName]: csrfTokenValue
            })
                .done(res => {
                    const icon = res.status === 'success' ? 'success' : 'error';
                    Swal.fire({ icon, title: res.status === 'success' ? 'Berhasil' : 'Gagal', text: res.message });
                    $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> 2. Push Final Grades to VPS');
                })
                .fail(() => {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Gagal menghubungi server.' });
                    $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> 2. Push Final Grades to VPS');
                });
        });
    });
});

function reloadProctorGrid() {
    const examId = '<?= $ujian['id'] ?? 0; ?>';
    if (examId === '0') return;

    $.get('/admin/ujian/teori/live-status/' + examId, function(response) {
        let html = '';
        if (!response.students || response.students.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted">Belum ada peserta atau data belum disinkronkan.</td></tr>';
        } else {
            response.students.forEach(function(stu) {
                const badgeColor = stu.status_ujian === 'selesai' ? 'bg-success'
                    : stu.status_ujian === 'mengerjakan' ? 'bg-primary' : 'bg-secondary';
                const alertClass = stu.violations >= 2 ? 'text-danger fw-bold' : '';
                html += `<tr>
                    <td class="fw-bold">${stu.no_ujian}</td>
                    <td>${stu.nama_mahasiswa}</td>
                    <td class="text-center"><span class="badge ${badgeColor}">${stu.status_ujian.toUpperCase()}</span></td>
                    <td class="text-center">${stu.remaining_time} mnt</td>
                    <td class="text-center ${alertClass}">
                        ${stu.violations > 0 ? '<i class="fa fa-warning text-warning"></i> ' : ''}
                        ${stu.violations} / 3
                    </td>
                    <td class="text-center">
                        ${stu.status_ujian === 'mengerjakan'
                            ? `<button class="btn btn-sm btn-danger py-0 px-2" onclick="forceSubmit('${stu.attempt_id}')">Stop</button>`
                            : '-'}
                    </td>
                </tr>`;
            });
        }
        $('#live-student-rows').html(html);
    });
}

function forceSubmit(attemptId) {
    Swal.fire({
        icon: 'warning',
        title: 'Paksa Submit?',
        text: 'Ujian peserta ini akan langsung diakhiri.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Akhiri',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (!result.isConfirmed) return;
        $.post('/admin/ujian/teori/force-submit/' + attemptId, {
            [csrfTokenName]: csrfTokenValue
        }, function() {
            reloadProctorGrid();
        }).fail(() => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat mengakhiri ujian.' });
        });
    });
}
</script>


<?php $this->endSection(); ?>
