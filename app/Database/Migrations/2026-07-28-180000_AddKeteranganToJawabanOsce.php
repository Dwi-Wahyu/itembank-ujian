<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKeteranganToJawabanOsce extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('jawaban_osce');
        if (!in_array('keterangan', $fields)) {
            $this->db->query("ALTER TABLE jawaban_osce ADD COLUMN keterangan TEXT NULL");
        }
    }

    public function down()
    {
        $fields = $this->db->getFieldNames('jawaban_osce');
        if (in_array('keterangan', $fields)) {
            $this->db->query("ALTER TABLE jawaban_osce DROP COLUMN keterangan");
        }
    }
}
