<?php
namespace Modules\Admin\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes= false;

    protected $allowedFields = [
        'name','username','email','password','role_id','blok','departemen',
        'thumb_avatar','remember_token','remember_expires_at','old','kordinator',
        'created_at','updated_at'
    ];

    protected $useTimestamps = false;

    public function listByRole(int $roleId, ?string $q = null): array
    {
        $b = $this->db->table($this->table . ' u')
            ->select('u.*, b.nama as blok_nama, d.nama as dep_nama')
            ->join('blok b', 'b.id = u.blok', 'left')
            ->join('departemen d', 'd.id = u.departemen', 'left')
            ->where('u.role_id', $roleId);

        if ($q) {
            $q = trim($q);
            $b->groupStart()
                ->like('u.name', $q)
                ->orLike('u.username', $q)
                ->orLike('u.email', $q)
                ->orLike('b.nama', $q)
                ->orLike('d.nama', $q)
              ->groupEnd();
        }
        return $b->orderBy('u.name','asc')->get()->getResultArray();
    }

    public function getInRole(int $id, int $roleId): ?array
    {
        return $this->where('id', $id)->where('role_id', $roleId)->first();
    }
}
