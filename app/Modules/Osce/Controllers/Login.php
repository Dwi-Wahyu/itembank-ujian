<?php
namespace Modules\Osce\Controllers;

use App\Controllers\BaseController;
use Config\Database;
use DateTimeZone;
use DateTime;

class Login extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index()
    {
        // Kalau sudah ada sesi pengawas, langsung ke panel
        if (session()->has('osce') && !empty(session('osce.nip'))) {
            return redirect()->to(site_url('e-osce/panel'));
        }

        return view('\Modules\Osce\Views\login');
    }

    public function auth()
    {
        // Sudah login? Kirim redirect JSON
        if (session()->has('osce') && !empty(session('osce.nip'))) {
            return $this->response->setJSON([
                'status' => 'ok',
                'redirect' => site_url('e-osce/panel'),
                'csrf_token' => csrf_hash(),
            ]);
        }

        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON([
                'status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()
            ]);
        }

        $nip  = trim((string)$this->request->getPost('nip'));
        $kode = trim((string)$this->request->getPost('kode'));
        if ($nip === '' || $kode === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'NIP dan Kode wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }

        // Filter tanggal hari ini (kolom o.tanggal = DATE)
        $today = (new DateTime('now', new DateTimeZone(config('App')->appTimezone)))->format('Y-m-d');

        $row = $this->db->table('osce_soal s')
            ->select('s.*, o.kode AS osce_kode, o.nama_ujian, o.tanggal, up.register AS soal_register')
            ->join('osce o', 'o.id = s.osce_id', 'left')
            ->join('ujian_praktek up', 'up.id = s.soal_id', 'left')
            ->where('s.nip_pengawas', $nip)
            ->where('s.kode', $kode)
            ->where('o.tanggal', $today)      // jika kolomnya DATETIME, ganti dengan range >= & <
            ->orderBy('s.created_at', 'DESC')
            ->get()->getRowArray();

        if (!$row) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'=>'error','message'=>'NIP atau Kode tidak cocok','csrf_token'=>csrf_hash()
            ]);
        }

        // Buat sesi OSCE (khusus pengawas)
        $claims = [
            'nip'          => (string)$row['nip_pengawas'],
            'nama'         => (string)($row['nama_pengawas'] ?? ''),
            'osce_id'      => (int)$row['osce_id'],
            'osce_kode'    => (string)($row['osce_kode'] ?? ''),
            'osce_nama'    => (string)($row['nama_ujian'] ?? ''),
            'osce_tanggal' => (string)($row['tanggal'] ?? ''),
            'station_id'   => (int)$row['id'],
            'station_nama' => (string)($row['nama_station'] ?? ''),
            'soal_id'      => (int)($row['soal_id'] ?? 0),
            'soal_register'=> (string)($row['soal_register'] ?? ''),
            'waktu'        => (int)($row['waktu'] ?? 0),
            'role'         => 'pengawas',
            'logged_at'    => time(),
            'ua'           => sha1($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'ip'           => $this->request->getIPAddress(),
        ];

        session()->regenerate(true);
        session()->set(['osce' => $claims]);

        return $this->response->setJSON([
            'status'=>'ok',
            'redirect'=> site_url('e-osce/panel'),
            'csrf_token'=>csrf_hash()
        ]);
    }

    public function logout()
    {
        session()->remove('osce');
        session()->destroy();
        return redirect()->to(site_url('e-osce/login'));
    }
}
