<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ResetPasswordSeeder extends Seeder
{
    public function run()
    {
        // Tentukan password baru
        $plainPassword = env('SUPERADMIN_PASSWORD', 'admin1234');
        
        // Buat hash password (menggunakan BCRYPT secara default)
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $existing = $this->db->table('users')->where('username', 'superadmin')->get()->getRowArray();
        if ($existing) {
            $data = [
                'password' => $hashedPassword,
            ];
            $this->db->table('users')
                     ->where('username', 'superadmin') 
                     ->update($data);

            echo "User 'superadmin' sudah ada, password berhasil direset menjadi: " . $plainPassword . PHP_EOL;
        } else {
            $data = [
                'name'       => 'Superadmin',
                'username'   => 'superadmin',
                'email'      => 'superadmin@example.test',
                'password'   => $hashedPassword,
                'role_id'    => 0,
                'blok'       => '0',
                'departemen' => '0',
                'old'        => 0,
                'kordinator' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->db->table('users')->insert($data);
            echo "User 'superadmin' berhasil dibuat dengan password: " . $plainPassword . PHP_EOL;
        }
    }
}