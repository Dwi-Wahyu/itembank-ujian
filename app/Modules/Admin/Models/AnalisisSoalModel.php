<?php

namespace Modules\Admin\Models;

use CodeIgniter\Model;

class AnalisisSoalModel extends Model
{
    protected $table            = 'analisis_soal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'id_ujian',
        'kode',
        'soal_id',
        'nomor_urut',
        'kunci',
        'total_peserta',
        'total_benar',
        'total_salah',
        'total_kosong',
        'tingkat_kesukaran',
        'kategori_kesukaran',
        'pilihan_a',
        'pilihan_b',
        'pilihan_c',
        'pilihan_d',
        'pilihan_e',
        'pilihan_kosong',
        'daya_pembeda',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
