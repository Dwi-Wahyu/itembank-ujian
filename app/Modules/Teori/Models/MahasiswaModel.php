<?php
namespace Modules\Teori\Models;

use CodeIgniter\Model;

class MahasiswaModel extends Model
{
    protected $table         = 'mahasiswa';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['nama','nim','kelas','old_angkatan','created_at','updated_at'];

    public function findByNim(string $nim): ?array
    {
        return $this->where('nim', $nim)->first();
    }
}
