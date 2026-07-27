<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSyncedAtColumn extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE ujian_attempt ADD COLUMN synced_at DATETIME NULL");
        $this->db->query("ALTER TABLE jawaban_osce ADD COLUMN synced_at DATETIME NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE ujian_attempt DROP COLUMN synced_at");
        $this->db->query("ALTER TABLE jawaban_osce DROP COLUMN synced_at");
    }
}
