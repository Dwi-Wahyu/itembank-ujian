<?php
namespace Modules\Teori\Models;

use CodeIgniter\Model;

class JawabanTeoriModel extends Model
{
    protected $table         = 'jawaban_teori';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['id_mahasiswa','kode','soal_id','jawaban'];

    /** Upsert berdasarkan (id_mahasiswa, kode, soal_id) */
    public function upsertByKode(array $data)
    {
        $row = $this->where([
            'id_mahasiswa' => $data['id_mahasiswa'],
            'kode'         => $data['kode'],
            'soal_id'      => $data['soal_id'],
        ])->first();

        if ($row) { $this->update($row['id'], $data); return $row['id']; }
        return $this->insert($data);
    }

    /** Map jawaban untuk resume: [soal_id => ['jawaban' => 'A']] */
    public function mapByKode(int $idMhs, string $kode): array
    {
        $rows = $this->select('soal_id, jawaban')
                     ->where('id_mahasiswa',$idMhs)
                     ->where('kode',$kode)
                     ->findAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['soal_id']] = ['jawaban' => $r['jawaban']];
        }
        return $out;
    }
    public function summarize(int $idMhs, string $kode, int $paketId): array
    {
        // ambil semua soal di paket + kunci
        $sql = "
            SELECT u.id AS soal_id, u.kunci, j.jawaban
            FROM ujian_teori u
            LEFT JOIN jawaban_teori j
              ON j.soal_id = u.id
             AND j.id_mahasiswa = ?
             AND j.kode = ?
            WHERE u.id_paket = ?
              AND u.status   = 2
            ORDER BY u.id ASC
        ";
        $rows = $this->db->query($sql, [$idMhs, $kode, $paketId])->getResultArray();

        $total=0; $benar=0; $salah=0; $kosong=0;
        foreach ($rows as $r) {
            $total++;
            $jaw = trim((string)($r['jawaban'] ?? ''));
            if ($jaw === '') { $kosong++; continue; }
            if (strtoupper($jaw) === strtoupper((string)$r['kunci'])) $benar++;
            else $salah++;
        }
        return compact('total','benar','salah','kosong');
    }
}
