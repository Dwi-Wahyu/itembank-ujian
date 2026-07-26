<?php
namespace Modules\Admin\Models;

use CodeIgniter\Model;

class OsceSoalModel extends Model
{
    protected $table         = 'osce_soal';
    protected $primaryKey    = 'id';
protected $allowedFields = [
  'osce_id','soal_id','nip_pengawas','nama_pengawas',
  'nama_station','kode','waktu','created_by','created_at','updated_at'
];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
