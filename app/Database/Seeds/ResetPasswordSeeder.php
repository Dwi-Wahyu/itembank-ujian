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

        $data = [
            'password' => $hashedPassword,
        ];

        // Ganti 'users' dengan nama tabel user Anda
        // Ganti 'id' atau 'username' sesuai user yang ingin direset (contoh ID 1)
        $this->db->table('users')
                 ->where('username', 'superadmin') 
                 ->update($data);

        echo "Password berhasil direset menjadi: " . $plainPassword . PHP_EOL;
    }
}