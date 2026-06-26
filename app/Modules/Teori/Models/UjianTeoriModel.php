<?php
namespace Modules\Teori\Models;
use CodeIgniter\Model;

class UjianTeoriModel extends Model
{
    protected $table='ujian_teori';
    protected $returnType='array';
    protected $allowedFields=[
        't1','t2','t3','vignette','pertanyaan','file','a','b','c','d','e','kunci','register',
        'departemen','blok','alasan','referensi','insert_by','status','subcpl','revisi_by',
        'revisi_by2','revisi_status','revisi_status2','id_paket','bobot_a','bobot_b','bobot_c',
        'bobot_d','bobot_e','created_at','updated_at'
    ];

    public function listPublishedByPaket(int $paketId): array
    {
        return $this->select('id,vignette,pertanyaan,file,a,b,c,d,e,bobot_a,bobot_b,bobot_c,bobot_d,bobot_e')
            ->where('id_paket',$paketId)->where('status',2)->orderBy('id','ASC')->findAll();
    }
}
