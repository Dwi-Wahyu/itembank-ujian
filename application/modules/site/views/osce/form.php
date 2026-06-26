<form id="form-package" action="<?=current_url()?>" enctype="multipart/form-data">
  <div class="form-group">
    <div class="row">
      <div class="col-12 col-sm-8">
        <label>Nama Ujian</label>
        <input type="text" class="form-control" name="<?=COL_OSCETITLE?>" value="<?=!empty($data)?$data[COL_OSCETITLE]:''?>" required />
      </div>
      <div class="col-12 col-sm-4">
        <label>Tanggal</label>
        <input type="text" class="form-control datepicker" name="<?=COL_OSCEDATE?>" value="<?=!empty($data)?date('Y-m-d', strtotime($data[COL_OSCEDATE])):''?>" required />
      </div>
    </div>

  </div>
  <div class="form-group">
    <label>Departemen</label>
    <select class="form-control" name="<?=COL_IDDEPARTMENT?>" style="width: 100% !important">
      <?=GetCombobox("select * from mdepartment order by DepartmentNama", COL_UNIQ, COL_DEPARTMENTNAMA, (!empty($data)?$data[COL_IDDEPARTMENT]:null), true, false, '- Semua Departemen -')?>
    </select>
  </div>
  <div class="form-group">
    <label>Blok</label>
    <select class="form-control" name="<?=COL_IDBLOCK?>" style="width: 100% !important">
      <?=GetCombobox("select * from mblock order by BlockNama", COL_UNIQ, COL_BLOCKNAMA, (!empty($data)?$data[COL_IDBLOCK]:null), true, false, '- Semua Blok -')?>
    </select>
  </div>
</form>
