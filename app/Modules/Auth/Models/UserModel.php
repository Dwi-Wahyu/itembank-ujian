<?php
namespace Modules\Auth\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'name','username','email','password','role_id','blok','departemen',
        'thumb_avatar','remember_token','old','kordinator','created_at','updated_at'
    ];

    protected $useTimestamps    = false; // pakai manual di controller kalau perlu
}
