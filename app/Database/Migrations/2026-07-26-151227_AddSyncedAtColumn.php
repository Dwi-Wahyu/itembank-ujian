<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSyncedAtColumn extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('synced_at', 'ujian_attempt')) {
            $this->forge->addColumn('ujian_attempt', [
                'synced_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
        }
        if (!$this->db->fieldExists('synced_at', 'jawaban_osce')) {
            $this->forge->addColumn('jawaban_osce', [
                'synced_at' => ['type' => 'DATETIME', 'null' => true]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('synced_at', 'ujian_attempt')) {
            $this->forge->dropColumn('ujian_attempt', 'synced_at');
        }
        if ($this->db->fieldExists('synced_at', 'jawaban_osce')) {
            $this->forge->dropColumn('jawaban_osce', 'synced_at');
        }
    }
}
