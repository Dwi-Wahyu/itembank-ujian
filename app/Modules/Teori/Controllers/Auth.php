<?php
namespace Modules\Teori\Controllers;

use App\Controllers\BaseController;
use Modules\Teori\Models\MahasiswaModel;
use Modules\Teori\Models\AdminCbtModel;
use Modules\Teori\Models\BuatTeoriModel;
class Auth extends BaseController
{
    public function index()
    {
        return view('Modules\Teori\Views\auth\login');
    }

public function login()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
        }

        // sekarang ambil NIM dan NO UJIAN dari form
        $nim      = trim((string)$this->request->getPost('nim'));
        $noUjian  = trim((string)$this->request->getPost('no_ujian'));

        $mhsM = new MahasiswaModel();
        $cbtM = new AdminCbtModel();
        $btM  = new BuatTeoriModel();

        // cek mahasiswa
        $mhs = $mhsM->findByNim($nim);
        if (!$mhs) {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Mahasiswa tidak ditemukan.',
                'csrf_token'=>csrf_hash()
            ]);
        }

        // cari baris admin_cbt berdasarkan id_mahasiswa + no_ujian
        $cbt = $cbtM->findByMahasiswaAndNoUjian((int)$mhs['id'], $noUjian);
        if (!$cbt) {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'No. Ujian tidak cocok dengan mahasiswa / belum terdaftar.',
                'csrf_token'=>csrf_hash()
            ]);
        }

        // CEK STATUS WAKTU
        if ($cbt['time_status'] === 'wrong_date') {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Ujian dijadwalkan pada tanggal ' . date('d-m-Y', strtotime($cbt['tanggal'])),
                'csrf_token'=>csrf_hash()
            ]);
        }
        if ($cbt['time_status'] === 'not_started') {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Ujian belum dimulai. Silakan tunggu hingga pukul ' . $cbt['mulai'],
                'csrf_token'=>csrf_hash()
            ]);
        }
        if ($cbt['time_status'] === 'ended') {
            return $this->response->setJSON([
                'status'=>'error',
                'message'=>'Waktu ujian telah berakhir.',
                'csrf_token'=>csrf_hash()
            ]);
        }

        // ambil kode ujian dari admin_cbt
        $examCode = (string)($cbt['kode'] ?? '');

        // detail ujian (opsional untuk modal)
        $exam = $btM->findByKodeWithJoin($examCode);

        // SESSION
        session()->regenerate(true);
        session()->set([
            'teori_logged_in' => true,
            'teori_mhs'       => [
                'id'   => (int) ($mhs['id']   ?? 0),
                'nim'  => (string) ($mhs['nim']  ?? ''),
                'nama' => (string) ($mhs['nama'] ?? ''),
            ],
            'mahasiswa'       => $mhs,   // legacy
            'ujian'           => $cbt,   // termasuk no_ujian & kode
        ]);

        return $this->response->setJSON([
            'status'     => 'ok',
            'message'    => 'Login berhasil.',
            'mhs'        => [
                'nama' => $mhs['nama'] ?? '',
                'nim'  => $mhs['nim']  ?? '',
            ],
            // kirim metadata ujian (untuk modal)
            'exam' => $exam ? [
                'nama_ujian'   => $exam['nama'] ?? '',
                'departemen'   => $exam['departemen_nama'] ?? '',
                'blok'         => $exam['blok_nama'] ?? '',
                'tanggal'      => $exam['tanggal'] ?? '',
                'mulai'        => $exam['mulai'] ?? '',
                'selesai'      => $exam['selesai'] ?? '',
                'jumlah_soal'  => (int)($exam['jumlah_soal'] ?? 0),
                'kode'         => $examCode,      // <- penting
            ] : null,

            // ikutkan ini sesuai permintaan
            'exam_code' => $examCode,           // dari admin_cbt.kode
            'no_ujian'  => $noUjian,            // dari input
            'csrf_token'=> csrf_hash(),
        ]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('teori/login'));
    }
}
