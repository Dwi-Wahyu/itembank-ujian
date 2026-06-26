<?php
namespace Modules\Teori\Models;

use CodeIgniter\Model;

class BuatTeoriModel extends Model
{
    protected $table         = 'buat_teori';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'dapertemen_id','blok','status','nama','tanggal','mulai','selesai',
        'kode','jumlah_soal','created_at','updated_at'
    ];

    public function findByKodeWithJoin(string $kode): ?array
    {
        return $this->select('
                    buat_teori.*,
                    d.nama AS departemen_nama,
                    b.nama AS blok_nama
                ')
                ->join('departemen d','d.id = buat_teori.dapertemen_id','left')
                ->join('blok b','b.id = buat_teori.blok','left')
                ->where('buat_teori.kode', $kode)
                ->first();
    }
       public function byKodeWithJoin(string $kode): ?array
    {
        return $this->select('buat_teori.*, d.nama AS departemen_nama, b.nama AS blok_nama')
            ->join('departemen d','d.id=buat_teori.dapertemen_id','left')
            ->join('blok b','b.id=buat_teori.blok','left')
            ->where('buat_teori.kode',$kode)->first();
    }
}
