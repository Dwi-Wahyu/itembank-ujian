<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnalisisUjianTables extends Migration
{
    public function up()
    {
        // 1. Tabel analisis_ujian (Ringkasan Sesi Ujian)
        if (!$this->db->tableExists('analisis_ujian')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'id_ujian' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                ],
                'kode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'total_peserta' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'total_selesai' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'nilai_tertinggi' => [
                    'type'    => 'INT',
                    'default' => 0,
                ],
                'nilai_terendah' => [
                    'type'    => 'INT',
                    'default' => 0,
                ],
                'nilai_rata_rata' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 0.00,
                ],
                'nilai_median' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 0.00,
                ],
                'standar_deviasi' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 0.00,
                ],
                'total_lulus' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'persen_kelulusan' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                ],
                'processed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('id_ujian');
            $this->forge->addKey('kode');
            $this->forge->createTable('analisis_ujian', true);
        }

        // 2. Tabel analisis_soal (Analisis per Butir Soal)
        if (!$this->db->tableExists('analisis_soal')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'id_ujian' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                ],
                'kode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'soal_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                ],
                'nomor_urut' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 1,
                ],
                'kunci' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => '',
                ],
                'total_peserta' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'total_benar' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'total_salah' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'total_kosong' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'tingkat_kesukaran' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                ],
                'kategori_kesukaran' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'sedang',
                ],
                'pilihan_a' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'pilihan_b' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'pilihan_c' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'pilihan_d' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'pilihan_e' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'pilihan_kosong' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 0,
                ],
                'daya_pembeda' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('id_ujian');
            $this->forge->addKey('kode');
            $this->forge->addKey('soal_id');
            $this->forge->addUniqueKey(['id_ujian', 'soal_id']);
            $this->forge->createTable('analisis_soal', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('analisis_soal', true);
        $this->forge->dropTable('analisis_ujian', true);
    }
}
