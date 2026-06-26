<?php
namespace Modules\Teori\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use Modules\Teori\Models\{BuatTeoriModel,UjianTeoriModel,JawabanTeoriModel,AttemptModel};

class Ujian extends BaseController
{
    private function guardWindow(array $exam): void
    {
        $tz = config('App')->appTimezone ?? 'Asia/Makassar';
        $now    = Time::now($tz);
        $mulai  = Time::parse($exam['tanggal'].' '.$exam['mulai'], $tz);
        $selesai= Time::parse($exam['tanggal'].' '.$exam['selesai'], $tz);

        if ($exam['tanggal'] !== $now->toDateString() || $now < $mulai || $now > $selesai) {
            // Keluar dari jadwal
            redirect()->to(base_url('teori/login'))->with('err','Di luar jadwal ujian.')->send(); exit;
        }
    }



   public function mulai()
    {
        $kode = (string)$this->request->getGet('kode');
        if (!$kode) {
            return redirect()->to(base_url('teori/login'));
        }

        // Ambil paket ujian dari kode
        $bt = (new BuatTeoriModel())->byKodeWithJoin($kode);
        if (!$bt) {
            return redirect()->to(base_url('teori/login'))->with('err','Ujian tidak ditemukan.');
        }

        // Ambil data mahasiswa dari session (sesuaikan key session milikmu)
        $mhs = session('mahasiswa');
        if (!$mhs || empty($mhs['id'])) {
            return redirect()->to(base_url('teori/login'));
        }

        $attemptM = new AttemptModel();

        // ===== Guard 1: Jika attempt existing SUDAH SELESAI, langsung ke hasil
        $existing = $attemptM->where([
                'id_mahasiswa' => (int)$mhs['id'],
                'id_paket'     => (int)$bt['id'],
                'kode'         => (string)$bt['kode'],
            ])->orderBy('id','DESC')->first();

        if ($existing && ($existing['status'] ?? '') === 'finished') {
            return redirect()->to(base_url('teori/ujian/hasil/'.$existing['id']));
        }

        // Pastikan window waktu (hanya bila belum selesai)
        $this->guardWindow($bt);

        // Buat/lanjut attempt (sekaligus set urutan soal acak per peserta)
        $attempt = $attemptM->createOrResume(
            (int)$mhs['id'],
            (int)$bt['id'],
            (string)$bt['kode'],
            (string)$bt['tanggal'],
            (string)$bt['mulai'],
            (string)$bt['selesai']
        );
// Modules/Teori/Controllers/Ujian.php (di method mulai(), setelah $attempt dibuat)
$noUjian = (string) (session('ujian')['no_ujian'] ?? '');

if ($noUjian === '') {
    // fallback: cari langsung di admin_cbt
    $ac = db_connect()->table('admin_cbt')
          ->select('no_ujian')
          ->where(['id_mahasiswa' => (int)$mhs['id'], 'kode' => $bt['kode']])
          ->get()->getRowArray();
    $noUjian = (string)($ac['no_ujian'] ?? '');
}

// tempel ke baris attempt bila masih kosong
$attemptM->attachNoUjian((int)$attempt['id'], (int)$mhs['id'], (string)$bt['kode'], $noUjian);

        // ===== Guard 2 (safety): kalau dari createOrResume ternyata status sudah 'done'
        if (($attempt['status'] ?? '') === 'done') {
            return redirect()->to(base_url('teori/ujian/hasil/'.$attempt['id']));
        }

        // Render halaman ujian
        return view('Modules\Teori\Views\ujian\mulai', [
            'mhs'     => $mhs,
            'exam'    => $bt,
            'attempt' => $attempt,
        ]);
    }

    // JSON: meta + soal + jawaban + remaining (server-authoritative)
// Modules/Teori/Controllers/Ujian.php (method init)
public function init()
{
    $kode = (string)$this->request->getGet('kode');
    $bt   = (new \Modules\Teori\Models\BuatTeoriModel())->byKodeWithJoin($kode);
    if (!$bt) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'=>'error','message'=>'Ujian tidak ditemukan','csrf_token'=>csrf_hash()
        ]);
    }

    $mhs     = session('mahasiswa');
    $attempt = (new \Modules\Teori\Models\AttemptModel())
        ->createOrResume((int)$mhs['id'], (int)$bt['id'], $bt['kode'], $bt['tanggal'], $bt['mulai'], $bt['selesai']);

    // Ambil soal
    $items = (new \Modules\Teori\Models\UjianTeoriModel())->listPublishedByPaket((int)$bt['id']);

    // === NORMALISASI FILE → URL PUBLIK ===
    $pubDir  = 'uploads/soal_teori/';                 // folder publik
    $pubPath = FCPATH . $pubDir;                      // path absolut
    foreach ($items as &$r) {
        $urls = [];

        $raw = $r['file'] ?? null;                    // bisa string/JSON/bisa null
        if ($raw) {
            // Ambil array nama file
            $arr = is_array($raw) ? $raw : json_decode((string)$raw, true);
            if (!is_array($arr)) $arr = [$raw];       // fallback: satu string

            foreach ($arr as $fn) {
                if (!$fn) continue;
                $fn = ltrim((string)$fn, '/');

                // Jika sudah mengandung 'uploads/...', ambil basename saja
                $base = basename($fn);

                // Cek lokasi aktual (prioritas folder baru)
                $try1 = $pubPath . $base;             // public/uploads/soal_teori/<base>
                $try2 = FCPATH . ltrim($fn, '/');     // kalau DB sudah simpan 'uploads/soal_teori/xxx' atau 'uploads/soal/xxx'

                if (is_file($try1)) {
                    $urls[] = base_url($pubDir . $base);
                } elseif (is_file($try2)) {
                    // kalau file memang ada sesuai path di DB, pakai apa adanya
                    $urls[] = base_url(ltrim($fn,'/'));
                } else {
                    // fallback: arahkan ke folder publik standar
                    $urls[] = base_url($pubDir . $base);
                }
            }
        }

        $r['file_urls'] = $urls;
        $r['file_url']  = $urls[0] ?? null;
        // opsional: hapus field mentah untuk memperkecil payload
        // unset($r['file']);
    }
    unset($r);
// Modules/Teori/Controllers/Ujian.php (di method init(), setelah $attempt dibuat)
$noUjian = (string) (session('ujian')['no_ujian'] ?? '');
if ($noUjian === '') {
    $ac = db_connect()->table('admin_cbt')
          ->select('no_ujian')
          ->where(['id_mahasiswa' => (int)$mhs['id'], 'kode' => $bt['kode']])
          ->get()->getRowArray();
    $noUjian = (string)($ac['no_ujian'] ?? '');
}
(new \Modules\Teori\Models\AttemptModel())
    ->attachNoUjian((int)$attempt['id'], (int)$mhs['id'], (string)$bt['kode'], $noUjian);

    // Ambil jawaban map
    $answers = (new \Modules\Teori\Models\JawabanTeoriModel())->mapByKode((int)$mhs['id'], $bt['kode']);

    return $this->response->setJSON([
        'status' => 'ok',
        'exam'   => [
            'id'          => (int)$bt['id'],
            'nama'        => $bt['nama'],
            'departemen'  => $bt['departemen_nama'],
            'blok'        => $bt['blok_nama'],
            'tanggal'     => $bt['tanggal'],
            'mulai'       => $bt['mulai'],
            'selesai'     => $bt['selesai'],
            'kode'        => $bt['kode'],
            'jumlah_soal' => (int)$bt['jumlah_soal'],
        ],
        'attempt'=> [
            'id'        => (int)$attempt['id'],
            'remaining' => (int)$attempt['remaining_seconds'],
            'violations'=> (int)$attempt['violations'],
            'status'    => $attempt['status'],
              'no_ujian'  => $noUjian,              // <— optional
      'kode'      => $bt['kode'],           // exam_code
        ],
        'items'     => $items,
        'answers'   => $answers,
        'csrf_token'=> csrf_hash(),
    ]);
}


public function jawab()
{
    if ($this->request->getMethod() !== 'POST') {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Method not allowed','csrf_token'=>csrf_hash()]);
    }

    $mhs    = $this->currentMahasiswa();
    if (!$mhs) {
        return $this->response->setStatusCode(401)
            ->setJSON(['status'=>'error','message'=>'Sesi habis','csrf_token'=>csrf_hash()]);
    }

    // >>> setelah membaca session, segera lepas lock
    $this->releaseSessionLock();

    $kode   = (string)$this->request->getPost('kode');
    $soalId = (int)$this->request->getPost('soal_id');
    $jwb    = strtoupper((string)$this->request->getPost('jawaban'));
    $jwb    = in_array($jwb, ['A','B','C','D','E'], true) ? $jwb : null;

    // Upsert cepat (lihat Model di bawah)
    (new JawabanTeoriModel())->upsertByKode([
        'id_mahasiswa' => (int)$mhs['id'],
        'kode'         => $kode,
        'soal_id'      => $soalId,
        'jawaban'      => $jwb,
    ]);

    return $this->response->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
}



    // heartbeat tiap 10–15 dtk: kurangi remaining di server dan catat pelanggaran jika ada
public function heartbeat()
{
    if ($this->request->getMethod() !== 'POST') {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Method not allowed']);
    }

    // Tidak perlu session user di sini → langsung lepas lock
    $this->releaseSessionLock();

    $attemptId = (int)$this->request->getPost('attempt_id');
    $delta     = max(1, (int)$this->request->getPost('delta'));
    $reason    = (string)$this->request->getPost('reason'); // optional

    $am = new AttemptModel();
    $attempt = $am->find($attemptId);
    if (!$attempt) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Attempt tidak ditemukan']);

    if ($attempt['status'] !== 'ongoing') {
        return $this->response->setJSON([
            'status'=>'done','state'=>$attempt['status'],
            'remaining'=>(int)$attempt['remaining_seconds'],
            'violations'=>(int)$attempt['violations'],
            'csrf_token'=>csrf_hash(),
        ]);
    }

    $updated = $am->heartbeat($attempt, $delta, $reason ?: null, 3);

    return $this->response->setJSON([
        'status'=> $updated['status'] === 'ongoing' ? 'ok' : 'done',
        'state' => $updated['status'],
        'remaining'=>(int)$updated['remaining_seconds'],
        'violations'=>(int)$updated['violations'],
        'csrf_token'=>csrf_hash()
    ]);
}

public function csrf()
{
    return $this->response->setJSON(['csrf_token' => csrf_hash()]);
}

    /** Dipanggil dari JS: POST /teori/ujian/finish */
    public function finish()
    {
        $attemptId = (int)($this->request->getPost('attempt_id') ?? 0);
        $mhs = $this->currentMahasiswa();
        if (!$attemptId || !$mhs) {
            return $this->response->setJSON([
                'status' => 'error',
                'message'=> 'Tidak valid',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $attempt = (new AttemptModel())->find($attemptId);
        if (!$attempt || (int)$attempt['id_mahasiswa'] !== (int)$mhs['id']) {
            return $this->response->setJSON([
                'status'=>'error','message'=>'Attempt tidak ditemukan',
                'csrf_token'=> csrf_hash(),
            ]);
        }

        // set selesai
        (new AttemptModel())->finish($attemptId);

        return $this->response->setJSON([
            'status' => 'ok',
            'redirect' => base_url('teori/ujian/hasil/'.$attemptId),
            'csrf_token' => csrf_hash(),
        ]);
    }
protected function currentMahasiswa(): ?array
{
    $m = session()->get('teori_mhs');
    if (!$m) $m = session()->get('mahasiswa');   // fallback legacy
    // kalau ada schema lain:
    // if (!$m) $m = session()->get('osce_mhs');

    return (is_array($m) && !empty($m['id'])) ? $m : null;
}


       /** GET: /teori/ujian/hasil/(:num) */
public function hasil($attemptId)
{
    $mhs = $this->currentMahasiswa();
    if (!$mhs) return redirect()->to(base_url('teori/login'));

    $attemptM = new AttemptModel();
    $attempt  = $attemptM->find((int)$attemptId);
    if (!$attempt || (int)$attempt['id_mahasiswa'] !== (int)$mhs['id']) {
        return redirect()->to(base_url('teori/login'));
    }

    if (($attempt['status'] ?? '') !== 'finished') {
        return redirect()->to(base_url('teori/ujian/mulai?kode='.$attempt['kode']));
    }

    $sum = (new JawabanTeoriModel())->summarize(
        (int)$mhs['id'],
        (string)$attempt['kode'],
        (int)$attempt['id_paket']
    );

    // ⬇️ Ambil passing grade dari tabel ujian (berdasarkan kode ujian)
    $ujian = (new BuatTeoriModel())->where('kode', $attempt['kode'])->first();
  
    $min   = (int)($ujian['nilai_minimum'] ?? 0);
    $lulus = ((int)$sum['benar'] >= $min);

    $data = [
        'mhs'     => $mhs,
        'attempt' => $attempt,
        'sum'     => $sum,
        'tanggal' => Time::parse($attempt['start_at'] ?? 'now')->toDateString(),
        'min'     => $min,
        'lulus'   => $lulus,
    ];

    (new AttemptModel())->nilai($attemptId, (int)$sum['benar'], (int)$sum['salah'], (int)$sum['kosong']);

    return $this->response
        ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->setHeader('Pragma', 'no-cache')
        ->setHeader('Expires', '0')
        ->setBody(view('Modules\Teori\Views\ujian_hasil', $data));
}
    private function releaseSessionLock(): void
{
    // Aman untuk file- & redis-session driver
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }
}
public function attemptStatus()
{
    // Hanya tolak method selain GET/POST
    if (!in_array($this->request->getMethod(), ['GET','POST'], true)) {
        return $this->response->setStatusCode(405)->setJSON([
            'status' => 'error',
            'message'=> 'Method not allowed',
            'csrf_token' => csrf_hash(),
        ]);
    }

    $mhs = $this->currentMahasiswa();
    if (!$mhs) {
        return $this->response->setStatusCode(401)->setJSON([
            'status'=>'error','message'=>'Unauthorized','csrf_token'=>csrf_hash(),
        ]);
    }

    // getVar() akan ambil dari GET/POST
    $attemptId = (int) ($this->request->getVar('id') ?? 0);
    $kode      = (string) ($this->request->getVar('kode') ?? '');

    $am = new \Modules\Teori\Models\AttemptModel();
    if ($attemptId > 0) {
        $attempt = $am->find($attemptId);
    } elseif ($kode !== '') {
        $attempt = $am->where([
            'id_mahasiswa' => (int)$mhs['id'],
            'kode'         => $kode,
        ])->orderBy('id','DESC')->first();
    } else {
        return $this->response->setStatusCode(400)->setJSON([
            'status'=>'error','message'=>'Parameter tidak lengkap (attempt_id atau kode)','csrf_token'=>csrf_hash(),
        ]);
    }

    if (!$attempt || (int)$attempt['id_mahasiswa'] !== (int)$mhs['id']) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'=>'error','message'=>'Attempt tidak ditemukan','csrf_token'=>csrf_hash(),
        ]);
    }

    return $this->response->setJSON([
        'status'     => 'ok',
        'state'      => (string)$attempt['status'],
        'remaining'  => (int)($attempt['remaining_seconds'] ?? 0),
        'violations' => (int)($attempt['violations'] ?? 0),
        'csrf_token' => csrf_hash(),
    ]);
}


}
