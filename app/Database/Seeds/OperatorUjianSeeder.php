<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OperatorUjianSeeder extends Seeder
{
    public function run()
    {
        $username = env('OPERATOR_UJIAN_USERNAME', 'operator_ujian');
        $email    = env('OPERATOR_UJIAN_EMAIL', 'operator.ujian@example.test');
        $password = env('OPERATOR_UJIAN_PASSWORD', 'operator1234');

        $existing = $this->db->table('users')->where('username', $username)->get()->getRowArray();
        if ($existing) {
            echo "User '{$username}' sudah ada, dilewati." . PHP_EOL;
            return;
        }

        $data = [
            'name'       => 'Operator Ujian',
            'username'   => $username,
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role_id'    => 6,
            'blok'       => '0',
            'departemen' => '0',
            'old'        => 0,
            'kordinator' => '0',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($data);

        echo "User Operator Ujian berhasil dibuat." . PHP_EOL;
        echo "Username: {$username}" . PHP_EOL;
        echo "Password: {$password}" . PHP_EOL;
    }
}
