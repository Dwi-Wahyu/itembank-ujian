<?php
namespace Modules\Teori\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class AdminCbtModel extends Model
{
    protected $table         = 'admin_cbt';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['kode','id_mahasiswa','created_at','updated_at'];

    public function findByKodeAndMahasiswa(string $kode, int $idMhs): ?array
    {
        $now  = Time::now(config('App')->appTimezone);
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        return $this->select('admin_cbt.*, bt.nama AS nama_ujian, bt.tanggal, bt.mulai, bt.selesai, bt.jumlah_soal, d.nama AS departemen_nama, b.nama AS blok_nama')
                    ->join('buat_teori bt', 'bt.kode = admin_cbt.kode', 'inner')
                    ->join('departemen d', 'd.id = bt.dapertemen_id', 'left')
                    ->join('blok b',       'b.id = bt.blok',          'left')
                    ->where('admin_cbt.kode', $kode)
                    ->where('admin_cbt.id_mahasiswa', $idMhs)
                    ->where('bt.tanggal', $date)
                    ->where('bt.mulai <=', $time)
                    ->where('bt.selesai >=', $time)
                    ->first();
    }

       /**
     * NEW: dipakai saat login dengan NIM + NO UJIAN
     */
    public function findByMahasiswaAndNoUjian(int $idMhs, string $noUjian): ?array
    {
        // 1. Cari record registrasi dulu tanpa filter waktu
        $cbt = $this->db->table($this->table)
            ->select('admin_cbt.*, bt.nama AS nama_ujian, bt.tanggal, bt.mulai, bt.selesai, bt.jumlah_soal,
                      d.nama AS departemen_nama, b.nama AS blok_nama')
            ->join('buat_teori bt', 'bt.kode = admin_cbt.kode', 'inner')
            ->join('departemen d', 'd.id = bt.dapertemen_id', 'left')
            ->join('blok b', 'b.id = bt.blok', 'left')
            ->where('admin_cbt.id_mahasiswa', $idMhs)
            ->where('admin_cbt.no_ujian', $noUjian)
            ->get()->getRowArray();

        if (!$cbt) {
            return null; // Memang tidak terdaftar atau No. Ujian salah
        }

        // 2. Cek waktu (Gunakan timezone dari config)
        $tz   = config('App')->appTimezone;
        $now  = Time::now($tz);
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        // Tambahkan flag status waktu
        $cbt['time_status'] = 'ok';

        if ($cbt['tanggal'] !== $date) {
            $cbt['time_status'] = 'wrong_date';
        } elseif ($time < $cbt['mulai']) {
            $cbt['time_status'] = 'not_started';
        } elseif (!empty($cbt['selesai']) && $time > $cbt['selesai']) {
            $cbt['time_status'] = 'ended';
        }

        return $cbt;
    }

    /**
     * OPSIONAL: generator no_ujian per KODE, format: KODE-0001, KODE-0002, ...
     * Pastikan DB punya UNIQUE (kode, no_ujian).
     */
    public function generateNoUjian(string $kode): string
    {
        // ambil no_ujian terakhir untuk kode tsb, ambil angka di ujung
        $row = $this->select('no_ujian')
                    ->where('kode', $kode)
                    // kalau format KODE-####, casting bagian kanan ke INT agar benar urut
                    ->orderBy('CAST(SUBSTRING_INDEX(no_ujian, "-", -1) AS UNSIGNED)', 'DESC')
                    ->first();

        $last = 0;
        if ($row && preg_match('/(\d+)$/', (string)$row['no_ujian'], $m)) {
            $last = (int)$m[1];
        }
        $next = $last + 1;
        return $kode . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    
}
