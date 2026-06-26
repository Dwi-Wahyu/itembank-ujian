<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3 class="m-0 text-dark font-weight-light"><?=$title?></h3>
      </div>
    </div>
  </div>
</div>
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-12">
        <div id="card-main" class="card card-default bg-transparent" style="box-shadow: 0 0 0 rgba(0,0,0,.125),0 1px 0 rgba(0,0,0,.2) !important">
          <div class="card-header d-flex p-0" style="border-bottom: 0 !important">
            <ul class="nav nav-tabs" style="border-bottom: 0 !important;">
              <li class="nav-item"><a class="nav-link active" href="#tab-main" data-url="<?=current_url().'?tab=upcoming'?>" data-tab="info" data-toggle="tab" style="margin-left: 0 !important">Mendatang</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-main" data-url="<?=current_url().'?tab=current'?>" data-tab="konteks" data-toggle="tab">Berlangsung</a></li>
              <li class="nav-item"><a class="nav-link" href="#tab-main" data-url="<?=current_url().'?tab=past'?>" data-tab="register" data-toggle="tab">Selesai</a></li>
            </ul>
          </div>
          <div class="card-body bg-white">
            <div class="tab-content">
              <div id="tab-main" class="tab-pane active">

              </div>
            </div>
          </div>
          <div class="overlay">
            <i class="fas fa-2x fa-sync-alt fa-spin"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script type="text/javascript">
var autoRefresh = true;
$(document).ready(function(){
  $('.nav-link', $('#card-main')).click(function(){
    var url = $(this).data('url');

    $('div.overlay', $('#card-main')).show();
    $('#tab-main', $('#card-main')).load(url, function(){
      $('div.overlay', $('#card-main')).hide();
      $('.modal', $('#card-main')).on('shown.bs.modal', function () {
        autoRefresh = false;
      });
      $('.modal', $('#card-main')).on('hidden.bs.modal', function () {
        autoRefresh = true;
      });
    });
  });

  $('.nav-link.active', $('#card-main')).trigger('click');
  <?php
  if(!empty($_GET['view'])) {
    ?>
    $('.nav-link[data-tab="<?=$_GET['view']?>"]', $('#card-main')).trigger('click');
    <?php
  }
  ?>

  setInterval(function(){
    if(autoRefresh) {
      $('.nav-link.active', $('#card-main')).trigger('click');
    }
  }, 10000);
});
</script>
