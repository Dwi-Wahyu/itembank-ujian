<?php

namespace Modules\Admin\Models;

use CodeIgniter\Model;

class AnalisisUjianModel extends Model
{
    protected $table            = 'analisis_ujian';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'id_ujian',
        'kode',
        'total_peserta',
        'total_selesai',
        'nilai_tertinggi',
        'nilai_terendah',
        'nilai_rata_rata',
        'nilai_median',
        'standar_deviasi',
        'total_lulus',
        'persen_kelulusan',
        'processed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
