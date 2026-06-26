<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3 class="m-0 text-dark font-weight-light"><?=$title?></h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=site_url('site/user/dashboard')?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?=site_url('site/osce/index')?>">Ujian Praktek</a></li>
          <li class="breadcrumb-item active"><?=$title?></li>
        </ol>
      </div>
    </div>
  </div>
</div>
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-12">
        <div class="card card-default">
          <div class="card-body p-0">
            <ul class="nav flex-column">
              <!--<li class="nav-item">
                <span class="nav-link">Nama Ujian <span class="float-right font-weight-bold font-italic"><?=$data[COL_PKGNAME]?></span></span>
              </li>-->
              <li class="nav-item">
                <span class="nav-link">Departemen <span class="float-right font-weight-bold font-italic"><?=!empty($data[COL_DEPARTMENTNAMA])?$data[COL_DEPARTMENTNAMA]:'Semua Departemen'?></span></span>
              </li>
              <li class="nav-item">
                <span class="nav-link">Blok <span class="float-right font-weight-bold font-italic"><?=!empty($data[COL_BLOCKNAMA])?$data[COL_BLOCKNAMA]:'Semua Blok'?></span></span>
              </li>
              <li class="nav-item">
                <span class="nav-link">Tanggal <span class="float-right font-weight-bold font-italic"><?=date('d-m-Y', strtotime($data[COL_OSCEDATE]))?></span></span>
              </li>
              <li class="nav-item">
                <span class="nav-link">Jlh. Peserta <span class="float-right font-italic"><strong><?=number_format($data['NumParticipant'])?></strong></span></span>
              </li>
              <li class="nav-item">
                <span class="nav-link">Jlh. Soal / Station <span class="float-right font-italic"><strong><?=number_format($data['NumStation'])?></strong></span></span>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-sm-12">
        <div id="card-main" class="card card-default bg-transparent" style="box-shadow: 0 0 0 rgba(0,0,0,.125),0 1px 0 rgba(0,0,0,.2) !important">
          <div class="card-header d-flex p-0" style="border-bottom: 0 !important">
            <ul class="nav nav-tabs" style="border-bottom: 0 !important;">
              <li class="nav-item"><a class="nav-link active" href="#tab-detail" data-tab="detail" data-toggle="tab" style="margin-left: 0 !important">Soal / Station</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-participant" data-tab="participant" data-toggle="tab">Peserta</a></li>
            </ul>
          </div>
          <div class="card-body bg-white">
            <div class="tab-content">
              <div id="tab-detail" class="tab-pane active">
                <div class="row">
                  <div class="col-12 text-right">
                    <button id="btn-add-detail" data-url="<?=site_url('site/osce/detail-add/'.$data[COL_UNIQ])?>" class="btn btn-sm bg-gradient-primary btn-popup mb-3"><i class="fas fa-plus-circle"></i>&nbsp;TAMBAH</button>
                  </div>
                </div>
                <table id="dt-detail" class="table table-bordered table-striped table-sm">
                  <thead>
                    <tr>
                      <th style="vertical-align: middle; width: 10px; white-space: nowrap; text-align: center; padding-right: 1.5rem !important">Opsi</th>
                      <th style="vertical-align: middle">No. Soal</th>
                      <th style="vertical-align: middle">Nama Station</th>
                      <th style="vertical-align: middle">Pengawas</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if(!empty($rdetail)) {
                      foreach($rdetail as $r) {
                        ?>
                        <tr>
                          <td style="vertical-align: middle; width: 10px; white-space: nowrap; text-align: center; padding-right: 1.5rem !important">
                            <a href="<?=site_url('site/osce/detail-delete/'.$r[COL_UNIQ])?>" class="btn btn-xs bg-gradient-danger btn-confirm"><i class="fas fa-times-circle"></i></a>
                          </td>
                          <td style="vertical-align: middle"><?=$r[COL_OSCE_REGNUM]?></td>
                          <td style="vertical-align: middle"><?=$r[COL_OSCE_STATION]?></td>
                          <td style="vertical-align: middle"><?=$r[COL_LECTURERNAME]?></td>
                        </tr>
                        <?php
                      }
                    } else {
                      ?>
                      <tr>
                        <td colspan="4" class="text-center font-italic">Belum ada data peserta untuk saat ini.</td>
                      </tr>
                      <?php
                    }
                    ?>
                  </tbody>
                </table>
              </div>
              <div id="tab-participant" class="tab-pane">
                <div class="row">
                  <div class="col-12 text-right">
                    <button id="btn-add-participant" data-url="<?=site_url('site/osce/participant-browse/'.$data[COL_UNIQ])?>" class="btn btn-sm bg-gradient-primary btn-popup mb-3"><i class="fas fa-user-plus"></i>&nbsp;TAMBAH</button>
                  </div>
                </div>
                <table id="dt-main" class="table table-bordered table-striped table-sm">
                  <thead>
                    <tr>
                      <th style="vertical-align: middle; width: 10px; white-space: nowrap; text-align: center; padding-right: 1.5rem !important">Opsi</th>
                      <th style="vertical-align: middle">Username / NIM</th>
                      <th style="vertical-align: middle">Nama</th>
                      <th style="vertical-align: middle">Kelas</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if(!empty($rparticipant)) {
                      foreach($rparticipant as $r) {
                        ?>
                        <tr>
                          <td style="vertical-align: middle; width: 10px; white-space: nowrap; text-align: center; padding-right: 1.5rem !important">
                            <a href="<?=site_url('site/osce/participant-delete/'.$r[COL_UNIQ])?>" class="btn btn-xs bg-gradient-danger btn-confirm"><i class="fas fa-times-circle"></i></a>
                          </td>
                          <td style="vertical-align: middle"><?=$r[COL_USERNAME]?></td>
                          <td style="vertical-align: middle"><?=$r[COL_STUDENTNAME]?></td>
                          <td style="vertical-align: middle"><?=$r[COL_STUDENTCLASS]?></td>
                        </tr>
                        <?php
                      }
                    } else {
                      ?>
                      <tr>
                        <td colspan="4" class="text-center font-italic">Belum ada data peserta untuk saat ini.</td>
                      </tr>
                      <?php
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<div class="modal fade" id="modal-popup" role="dialog">
  <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <span class="modal-title font-weight-bold">Peserta Ujian</span>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>
<script type="text/javascript">
$(document).ready(function(){
  $('.btn-confirm', $('#dt-main')).click(function() {
    var url = $(this).attr('href');
    if(confirm('Apakah anda yakin?')) {
      $.get(url, function(res) {
        if(res.error != 0) {
          toastr.error(res.error);
        } else {
          toastr.success(res.success);
          setTimeout(function(){
            location.reload();
          }, 1000);
        }
      }, "json").done(function() {

      }).fail(function() {
        toastr.error('SERVER ERROR');
      });
    }
    return false;
  });

  $('#btn-add-participant').click(function() {
    var url = $(this).data('url');
    var modal = $('#modal-popup');

    $('.modal-body', modal).html('<p class="text-center">Memuat...</p>');
    modal.modal('show');
    $('.modal-body', modal).load(url, function(){
      setTimeout(function(){
        $('#dt-participant', modal).DataTable().columns.adjust();
      }, 1000);
    });

    return false;
  });

  $('#modal-popup').on('hidden.bs.modal', function (e) {
    setTimeout(function() {
      location.reload();
    }, 1000);
  });
});

</script>
