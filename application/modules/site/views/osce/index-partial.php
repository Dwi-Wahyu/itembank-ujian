<?php
if($tab=='upcoming') {
  ?>
  <p id="tbl-btn-add" class="text-right mb-2 d-none">
    <a href="<?=site_url('site/osce/add')?>" class="btn btn-sm bg-gradient-primary btn-popup btn-add"><i class="fas fa-plus-circle"></i>&nbsp;TAMBAH SESI</a>
  </p>
  <?php
}
?>

<table id="datalist" class="table table-bordered table-striped table-sm">
  <thead>
    <tr>
      <th style="width: 10px; white-space: nowrap">Opsi</th>
      <th>Nama Ujian</th>
      <th>Departemen</th>
      <th>Blok</th>
      <th>Tanggal</th>
      <th>Station</th>
      <th>Peserta</th>
    </tr>
  </thead>
  <tbody>
    <?php
    foreach($data as $d) {
      ?>
      <tr>
        <td class="text-center" style="width: 10px; white-space: nowrap">
          <a href="<?=site_url('site/osce/detail/'.$d[COL_UNIQ])?>" class="btn btn-xs bg-gradient-primary"><i class="fas fa-search"></i></a>
          <a href="<?=site_url('site/osce/edit/'.$d[COL_UNIQ])?>" class="btn btn-xs bg-gradient-success btn-edit"><i class="fas fa-edit"></i></a>
          <a href="<?=site_url('site/osce/report/'.$d[COL_UNIQ])?>" class="btn btn-xs bg-gradient-info"><i class="fas fa-clipboard"></i></a>
          <a href="<?=site_url('site/osce/delete/'.$d[COL_UNIQ])?>" class="btn btn-xs bg-gradient-danger btn-confirm"><i class="fas fa-times-circle"></i></a>
        </td>
        <td><?=$d[COL_OSCETITLE]?></td>
        <td><?=!empty($d[COL_DEPARTMENTNAMA])?$d[COL_DEPARTMENTNAMA]:'Semua Departemen'?></td>
        <td><?=!empty($d[COL_BLOCKNAMA])?$d[COL_BLOCKNAMA]:'Semua Blok'?></td>
        <td style="width: 100px" class="white-space: nowrap">
          <?=date('Y-m-d', strtotime($d[COL_OSCEDATE]))?>
        </td>
        <td class="text-center" style="width: 10px; white-space: nowrap"><?=number_format($d['NumStation'])?></td>
        <td class="text-center" style="width: 10px; white-space: nowrap"><?=number_format($d['NumParticipant'])?></td>
      </tr>
      <?php
    }
    ?>
  </tbody>
</table>
<div class="modal fade" id="modal-popup" role="dialog">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <span class="modal-title font-weight-bold">Ujian Praktek</span>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm bg-gradient-danger" data-dismiss="modal"><i class="fas fa-times-circle"></i>&nbsp;BATAL</button>
        <button type="submit" class="btn btn-sm bg-gradient-success"><i class="fas fa-check-circle"></i>&nbsp;SIMPAN</button>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
  var dt = $('#datalist').DataTable({
    "paging": true,
    "lengthChange": false,
    "oLanguage": {
      "sSearch": "<strong>Pencarian<strong> "
    },
    "searching": true,
    "info": true,
    "autoWidth": false,
    "dom":"<'row'<'col-sm-12 d-flex align-items-right'f<'btn-add'>>>rtip",
    "ordering": false,
    //"order": [[1, 'asc']],
    "columnDefs": [{ targets: 0, orderable: false }],
    "createdRow": function(row, data, dataIndex) {
      $('.btn-confirm', $(row)).click(function() {
        var url = $(this).attr('href');
        if(confirm('Apakah anda yakin?')) {
          $.get(url, function(res) {
            if(res.error != 0) {
              toastr.error(res.error);
            } else {
              toastr.success(res.success);
            }
          }, "json").done(function() {
            setTimeout(function(){
              $('.nav-link[data-url="<?=current_url().'?tab='.$tab?>"]').click();
            }, 1000);
          }).fail(function() {
            toastr.error('SERVER ERROR');
          });
        }
        return false;
      });

      $('.btn-edit', $(row)).unbind('click').click(function() {
        var url = $(this).attr('href');
        var modal = $('#modal-popup');

        $('.modal-body', modal).html('<p class="text-center">Memuat...</p>');
        modal.modal('show');
        $('.modal-body', modal).load(url, function(){
          $(".uang", modal).number(true, 0, '.', ',');
          $('.datepicker', modal).daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            maxYear: parseInt(moment().add(10, 'year').format('YYYY'),10),
            locale: {
                format: 'Y-MM-DD'
            }
          });
          $('.time-mask', modal).inputmask({
            alias: "datetime",
            inputFormat: "HH:MM"
          });
          $("select", modal).not('.no-select2, .custom-select').select2({ width: 'resolve', theme: 'bootstrap4' });

          $('form', modal).validate({
            ignore: "input[type='file']",
            submitHandler: function(form) {
              //var modal = $(form).closest('.modal');
              if(modal) {
                var btnSubmit = $('button[type=submit]', modal);
                var txtSubmit = btnSubmit.innerHTML;
                btnSubmit.html('<i class="fad fa-circle-notch fa-spin"></i>');
                btnSubmit.attr('disabled', true);
              }

              $(form).ajaxSubmit({
                dataType: 'json',
                type : 'post',
                success: function(res) {
                  if(res.error != 0) {
                    toastr.error(res.error);
                  } else {
                    modal.modal('hide');
                    toastr.success(res.success);
                    setTimeout(function(){
                      //location.reload();
                      $('.nav-link.active', $('#card-main')).trigger('click');
                    }, 1000);

                  }
                },
                error: function() {
                  toastr.error('SERVER ERROR');
                },
                complete: function() {
                  btnSubmit.html(txtSubmit);
                  btnSubmit.attr('disabled', false);
                }
              });
              return false;
            }
          });

          $('button[type=submit]', modal).unbind('click').click(function(){
            $('form', modal).submit();
          });
        });
        return false;
      });
    }
  });
  $("div.btn-add").html($('#tbl-btn-add').html()).addClass('ml-auto');

  $('.btn-popup').unbind('click').click(function() {
    var url = $(this).attr('href');
    var modal = $('#modal-popup');

    $('.modal-body', modal).html('<p class="text-center">Memuat...</p>');
    modal.modal('show');
    $('.modal-body', modal).load(url, function(){
      $(".uang", modal).number(true, 0, '.', ',');
      $('.datepicker', modal).daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        maxYear: parseInt(moment().add(10, 'year').format('YYYY'),10),
        locale: {
            format: 'Y-MM-DD'
        }
      });
      $('.time-mask', modal).inputmask({
        alias: "datetime",
        inputFormat: "HH:MM"
      });
      $("select", modal).not('.no-select2, .custom-select').select2({ width: 'resolve', theme: 'bootstrap4' });

      $('form', modal).validate({
        ignore: "input[type='file']",
        submitHandler: function(form) {
          //var modal = $(form).closest('.modal');
          if(modal) {
            var btnSubmit = $('button[type=submit]', modal);
            var txtSubmit = btnSubmit.innerHTML;
            btnSubmit.html('<i class="fad fa-circle-notch fa-spin"></i>');
            btnSubmit.attr('disabled', true);
          }

          $(form).ajaxSubmit({
            dataType: 'json',
            type : 'post',
            success: function(res) {
              if(res.error != 0) {
                toastr.error(res.error);
              } else {
                modal.modal('hide');
                toastr.success(res.success);
                setTimeout(function(){
                  //location.reload();
                  $('.nav-link.active', $('#card-main')).trigger('click');
                }, 1000);

              }
            },
            error: function() {
              toastr.error('SERVER ERROR');
            },
            complete: function() {
              btnSubmit.html(txtSubmit);
              btnSubmit.attr('disabled', false);
            }
          });
          return false;
        }
      });

      $('button[type=submit]', modal).unbind('click').click(function(){
        $('form', modal).submit();
      });
    });
    return false;
  });
});
</script>
