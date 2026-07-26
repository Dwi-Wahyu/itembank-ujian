<?php
namespace Modules\Admin\Models;

use CodeIgniter\Model;

class AspekModel extends Model
{
    protected $table         = 'aspek';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'soal_id','t1','t2','t3','aspek','keterangan','file','insert_by','created_at','updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
