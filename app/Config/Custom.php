<?php namespace Config;

use CodeIgniter\Config\BaseConfig;

class Custom extends BaseConfig
{
    // default yang diizinkan ke admin area
    public array $adminRoles = [0, 1, 2, 3, 4];

    // label (opsional, berguna untuk pesan/log)
    public array $roleLabels = [
        0 => 'Superadmin',
        1 => 'Admin',
        2 => 'Dekan',
        3 => 'Ketua',
        4 => 'Reviewer',
        5 => 'Dosen',
    ];

    public function __construct()
    {
        parent::__construct();
        // Bisa override via .env: ADMIN_ROLES=0,1,2,3
        $env = env('ADMIN_ROLES');
        if ($env) {
            $this->adminRoles = array_values(array_map('intval', explode(',', $env)));
        }
    }
}
