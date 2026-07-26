<?php $this->extend('\Modules\Admin\Views\layouts\admin'); ?>
<?php $this->section('content'); $r = $row ?? []; $m = $map ?? []; ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= site_url('admin/soal/praktek') ?>">Soal Praktek</a></li>
      <li class="breadcrumb-item active">Review #<?= (int)$r['id'] ?></li>
    </ol>
  </nav>
  <div>
    <a href="<?= site_url('admin/soal/praktek') ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
     <?php if ($me['role_id']==0 || $me['role_id']==4) {?>
    <button class="btn btn-primary" id="btnOpenReview"><i class="bi bi-clipboard-check"></i> Tambah Telaah</button>
   <?php }?>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">No. Register</label>
        <div class="form-control-plaintext border rounded px-2 py-2 bg-light"><?= esc($r['register']) ?></div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Kompetensi Utama</label>
        <div class="form-control-plaintext border rounded px-2 py-2 bg-light"><?= esc($m['t1'][$r['t1']] ?? '-') ?></div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Penyakit/Kelainan</label>
        <div class="form-control-plaintext border rounded px-2 py-2 bg-light"><?= esc($m['t2'][$r['t2']] ?? '-') ?></div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Ranah Teknis</label>
        <div class="form-control-plaintext border rounded px-2 py-2 bg-light"><?= esc($m['t3'][$r['t3']] ?? '-') ?></div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Bidang Ilmu</label>
        <div class="form-control-plaintext border rounded px-2 py-2 bg-light"><?= esc($m['t4'][$r['t4']] ?? '-') ?></div>
      </div>

      <div class="col-12">
        <label class="form-label mb-1">Tujuan</label>
        <div class="border rounded p-2 bg-light"><?= $r['tujuan'] ?></div>
      </div>
      <div class="col-12">
        <label class="form-label mb-1">Skenario</label>
        <div class="border rounded p-2 bg-light"><?= $r['skenario'] ?></div>
      </div>
      <div class="col-12">
        <label class="form-label mb-1">Tugas Peserta</label>
        <div class="border rounded p-2 bg-light"><?= $r['tugas_k'] ?></div>
      </div>
      <div class="col-12">
        <label class="form-label mb-1">Tugas Penguji</label>
        <div class="border rounded p-2 bg-light"><?= $r['tugas_p'] ?></div>
      </div>
      <div class="col-12">
        <label class="form-label mb-1">Instruksi</label>
        <div class="border rounded p-2 bg-light"><?= $r['intruksi'] ?></div>
      </div>
      <div class="col-12">
        <label class="form-label mb-1">Peralatan</label>
        <div class="border rounded p-2 bg-light"><?= $r['peralatan'] ?></div>
      </div>
      <div class="col-12">
        <label class="form-label mb-1">Referensi</label>
        <div class="border rounded p-2 bg-light"><?= $r['referensi'] ?></div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Status Saat Ini</label>
        <?php
          $lbl = strtolower($r['status_label'] ?? 'draft');
          $cls = $lbl==='publish'?'success':($lbl==='review'?'info':($lbl==='reject'?'danger':'secondary'));
        ?>
        <div><span class="badge bg-<?= $cls ?>"><?= strtoupper($lbl) ?></span></div>
      </div>
    </div>
  </div>
</div>

<!-- HISTORY -->
<div class="card">
  
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Riwayat Telaah</strong>
  <button class="btn btn-sm btn-outline-primary" id="btnReloadHistory"
        data-soal="<?= (int)$r['id'] ?>">
  <i class="bi bi-arrow-clockwise"></i> Muat Ulang
</button>

  </div>

    <div class="card-body" >
    
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead class="table-light">
        <tr> 
          <th style="width:200px">Creator</th>
          <th style="width:200px">Tanggal</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
   
        <tr class="js-rev" data-id="<?= (int)$r['id'] ?>" role="button" style="cursor:pointer">
          <td><?= esc($r['dosen']) ?></td>
             <td><?= esc($r['created_at']) ?></td>
          <td>
            <?php
              $lbl = strtolower($r['status_label'] ?? 'draft');
              $cls = $lbl==='publish'?'success':($lbl==='review'?'info':($lbl==='reject'?'danger':'secondary'));
            ?>
            <span class="badge bg-<?= $cls ?>"><?= strtoupper($lbl) ?></span>
          </td>
        </tr>
   
      </tbody>
    </table>
  </div>
   
  </div>
  
  <div class="card-body" id="revHistory">
    
 
    <?= view('\Modules\Admin\Views\praktek\partials\rev_history', ['rows'=>$history ?? []]) ?>
  </div>
</div>




<!-- MODAL REVISI -->
<div class="modal fade" id="modalReview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content" id="formReview" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="soal_id" value="<?= (int)$r['id'] ?>">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Telaah & Perbarui Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <hr>
<hr>
<div class="row g-3">
  <!-- T1–T12: YA/TIDAK -->
  <div class="col-md-6">
    <label class="form-label d-block">1) Kesesuaian CPL/Sub-CPL & kompetensi</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t1" id="t1ya" value="ya">
      <label class="form-check-label" for="t1ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t1" id="t1tdk" value="tidak">
      <label class="form-check-label" for="t1tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">2) Relevansi klinik & ranah keterampilan</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t2" id="t2ya" value="ya">
      <label class="form-check-label" for="t2ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t2" id="t2tdk" value="tidak">
      <label class="form-check-label" for="t2tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">3) Tujuan station jelas & terukur</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t3" id="t3ya" value="ya">
      <label class="form-check-label" for="t3ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t3" id="t3tdk" value="tidak">
      <label class="form-check-label" for="t3tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">4) Skenario & konteks pasien realistis</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t4" id="t4ya" value="ya">
      <label class="form-check-label" for="t4ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t4" id="t4tdk" value="tidak">
      <label class="form-check-label" for="t4tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">5) Instruksi untuk peserta jelas</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t5" id="t5ya" value="ya">
      <label class="form-check-label" for="t5ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t5" id="t5tdk" value="tidak">
      <label class="form-check-label" for="t5tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">6) Brief/panduan penguji memadai</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t6" id="t6ya" value="ya">
      <label class="form-check-label" for="t6ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t6" id="t6tdk" value="tidak">
      <label class="form-check-label" for="t6tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">7) Kecukupan waktu station</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t7" id="t7ya" value="ya">
      <label class="form-check-label" for="t7ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t7" id="t7tdk" value="tidak">
      <label class="form-check-label" for="t7tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">8) Peralatan & keselamatan memadai</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t8" id="t8ya" value="ya">
      <label class="form-check-label" for="t8ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t8" id="t8tdk" value="tidak">
      <label class="form-check-label" for="t8tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">9) Checklist observable & berbobot jelas</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t9" id="t9ya" value="ya">
      <label class="form-check-label" for="t9ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t9" id="t9tdk" value="tidak">
      <label class="form-check-label" for="t9tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">10) Checklist sesuai tujuan & tugas</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t10" id="t10ya" value="ya">
      <label class="form-check-label" for="t10ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t10" id="t10tdk" value="tidak">
      <label class="form-check-label" for="t10tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">11) Level kesulitan sesuai</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t11" id="t11ya" value="ya">
      <label class="form-check-label" for="t11ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t11" id="t11tdk" value="tidak">
      <label class="form-check-label" for="t11tdk">Tidak</label>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label d-block">12) Bahasa & tata tulis baik</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t12" id="t12ya" value="ya">
      <label class="form-check-label" for="t12ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="t12" id="t12tdk" value="tidak">
      <label class="form-check-label" for="t12tdk">Tidak</label>
    </div>
  </div>

  <!-- T13–T14: catatan -->
  <div class="col-md-6">
    <label class="form-label">13) Referensi</label>
    <textarea class="form-control" name="t13" rows="2" placeholder="Referensi mutakhir & relevan"></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label">14) Rekomendasi & keputusan</label>
    <textarea class="form-control" name="t14" rows="2" placeholder="Rekomendasi perbaikan & keputusan akhir"></textarea>
  </div>
</div>

       <?php
// mapping nilai angka -> label
$statuses = [0=>'draft', 1=>'review', 2=>'publish', 3=>'reject'];

// tentukan status saat ini (pakai angka bila ada; kalau tidak, konversi dari label)
$curVal = isset($r['status'])
  ? (int)$r['status']
  : (isset($r['status_label']) ? array_search(strtolower($r['status_label']), $statuses, true) : 0);
if ($curVal === false) $curVal = 0;
?>
<div class="row g-2">
  <div class="col-md-4">
    <label class="form-label">Status</label>
    <select class="form-select" name="status" required>
      <?php foreach ($statuses as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($curVal === $val ? 'selected' : '') ?>>
          <?= ucfirst($label) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

        <hr>
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-success" id="btnSaveRev"><i class="bi bi-check2-circle me-1"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL DETAIL REVISI (READ-ONLY) -->
<div class="modal fade" id="modalRevDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Telaah</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <label class="form-label">Tanggal</label>
            <div id="dt-created" class="form-control-plaintext border rounded px-2 py-2 bg-light"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <div><span id="dt-status" class="badge bg-secondary">-</span></div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Reviewer</label>
            <div id="dt-reviewer" class="form-control-plaintext border rounded px-2 py-2 bg-light"></div>
          </div>
        </div>
        <hr>
        <div class="row g-3">
          <?php for($i=1;$i<=14;$i++): ?>
          <div class="col-md-6">
            <label class="form-label mb-0">T<?= $i ?></label>
            <div id="dt-t<?= $i ?>" class="form-control-plaintext border rounded p-2 bg-light prewrap">-</div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>.prewrap{white-space:pre-wrap}</style>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
(function(){
  const modalEl = document.getElementById('modalReview');
  const modal   = new bootstrap.Modal(modalEl);

  // buka modal
  $('#btnOpenReview').on('click', function(){
    $('#formReview')[0].reset();
    modal.show();
  });

  // simpan telaah
  $('#formReview').on('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    if (window.__csrf) fd.append('<?= csrf_token() ?>', window.__csrf);

    const $btn = $('#btnSaveRev').prop('disabled',true)
      .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
    Loader && Loader.show();

    $.ajax({
      url: '<?= site_url('admin/soal/praktek/review/save') ?>',
      method: 'POST', data: fd, processData:false, contentType:false
    })
    .done(function(res,_t,xhr){
      window.refreshCsrfFrom && window.refreshCsrfFrom(res, xhr);
      if(res.status==='ok'){
        modal.hide();
        swalToast && swalToast('Telaah disimpan');
        // reload history
        $('#btnReloadHistory').trigger('click');
        // opsional: refresh badge status di header tanpa reload halaman
        // (biarkan sederhana dulu)
      }else{
        Swal.fire('Gagal', res.message || 'Tidak dapat menyimpan', 'error');
      }
    })
    .fail(function(xhr){
      window.refreshCsrfFrom && window.refreshCsrfFrom(null, xhr);
      Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan', 'error');
    })
    .always(function(){
      Loader && Loader.hide();
      $btn.prop('disabled',false).html('<i class="bi bi-check2-circle me-1"></i> Simpan');
    });
  });

  // reload history

  $('#btnReloadHistory').on('click', function(){
    // Ambil ID soal dari data attribute (lebih aman & reusable)
    const soalId = $(this).data('soal') || <?= (int)($r['id'] ?? 0) ?>;

    const url = '<?= site_url('admin/soal/praktek/review/history') ?>/' + soalId;

    if (window.Loader && typeof Loader.show === 'function') Loader.show();
    $.get(url)
      .done(function(html){
        $('#revHistory').html(html);
      })
      .fail(function(xhr){
        Swal.fire('Gagal', xhr?.responseText || 'Tidak dapat memuat history', 'error');
      })
      .always(function(){
        if (window.Loader && typeof Loader.hide === 'function') Loader.hide();
      });
  });


    const modalDetailEl = document.getElementById('modalRevDetail');
  const modalDetail   = new bootstrap.Modal(modalDetailEl);

  function setStatusBadge($el, label){
    const lbl = String(label||'draft').toLowerCase();
    const cls = lbl==='publish'?'success':(lbl==='review'?'info':(lbl==='reject'?'danger':'secondary'));
    $el.removeClass('bg-success bg-info bg-danger bg-secondary').addClass('bg-'+cls).text(lbl.toUpperCase());
  }

  // delegasi klik baris history
 function renderYN($el, val){
  const v = String(val||'').toLowerCase();
  if (v === 'ya')  { $el.removeClass().addClass('badge bg-success').text('YA'); }
  else if (v === 'tidak') { $el.removeClass().addClass('badge bg-secondary').text('TIDAK'); }
  else { $el.removeClass().addClass('').text('-'); }
}

$(document).on('click', '#revHistory .js-rev', function(){
  const id = parseInt($(this).data('id')||'0',10);
  if(!id) return;
  Loader && Loader.show();
  $.get('<?= site_url('admin/soal/praktek/review/get') ?>/'+id)
    .done(function(res,_t,xhr){
      window.refreshCsrfFrom && window.refreshCsrfFrom(res, xhr);
      if(res.status==='ok'){
        const d = res.data||{};
        $('#dt-created').text(d.created_at || '-');
        setStatusBadge($('#dt-status'), d.status_label || 'draft');
        $('#dt-reviewer').text(d.reviewer_name || ('User #'+(d.reviewer_name||'-')));

        // T1–T12 badge YA/TIDAK
        for(let i=1;i<=12;i++){
          const $box = $('#dt-t'+i);
          $box.html('<span class="badge"></span>');
          renderYN($box.find('.badge'), d['t'+i]);
        }
        // T13–T14 text biasa
        for(let i=13;i<=14;i++){
          $('#dt-t'+i).text(d['t'+i] || '-');
        }

        modalDetail.show();
      } else {
        Swal.fire('Gagal', res.message || 'Tidak dapat memuat detail', 'error');
      }
    })
    .fail(function(xhr){
      window.refreshCsrfFrom && window.refreshCsrfFrom(null, xhr);
      Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Tidak dapat memuat detail', 'error');
    })
    .always(()=> Loader && Loader.hide());
});

})();
</script>
<?php $this->endSection(); ?>
