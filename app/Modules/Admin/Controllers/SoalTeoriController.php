<?php

// app/Modules/Admin/Controllers/SoalTeoriController.php
namespace Modules\Admin\Controllers;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Auth\Libraries\Auth;
use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class SoalTeoriController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
            $this->response = service('response');
        helper(['text','date_id','ajax']); // date_id utk tgl_id(), ajax utk jsToken()
    }
 private function kodeOf(string $table, $id): ?string
    {
        if (empty($id)) return null;
        return $this->db->table($table)->select('kode')->where('id', $id)->get()->getRow('kode');
    }

    /** next id untuk ujian_teori (preview No. Register) */
    private function nextSoalId(): int
    {
        $row = $this->db->table('ujian_teori')->select('IFNULL(MAX(id),0)+1 AS n', false)->get()->getRowArray();
        return (int) ($row['n'] ?? 1);
    }

    /** rakit No. Register */
    private function buildRegister($t1,$t2,$t3): string
    {
        $next = $this->nextSoalId();
        $k1 = $this->kodeOf('kom_utama',   $t1) ?? 'X';
        $k2 = $this->kodeOf('penyakit',    $t2) ?? 'X';
        $k3 = $this->kodeOf('bid_ilmu', $t3) ?? 'X';
        return $next . '/' . $k1 . '/' . $k2 . '/' . $k3.'/'.date('d/m/Y');
    }

    /** AJAX: generate preview No. Register */
    public function regGenerate()
    {
        $t1 = $this->request->getGet('t1');
        $t2 = $this->request->getGet('t2');
        $t3 = $this->request->getGet('t3');

        if (!$t1 || !$t2 || !$t3) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error', 'message'=>'Lengkapi Kompetensi, Penyakit, dan Bidang Ilmu',
                'csrf_token'=>csrf_hash()
            ]);
        }

        $reg = $this->buildRegister($t1,$t2,$t3);

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok', 'register'=>$reg, 'csrf_token'=>csrf_hash()]);
    }
  private function baseQuery()
{
    // anggap t1=kompetensi, t2=penyakit, t3=bidang (ref_* tabel optional)
    return $this->db->table('ujian_teori t')
        ->select("t.id,
                  t.register,
                  t.vignette,
                  t.pertanyaan,
                  t.kunci,
                  CASE t.status
                      WHEN 0 THEN 'draft'
                      WHEN 1 THEN 'review'
                      WHEN 2 THEN 'publish'
                      WHEN 3 THEN 'reject'
                      ELSE 'draft'
                  END AS status,
                  t.a, t.b, t.c, t.d, t.e,
                  t.departemen, d.nama AS dep_nama,
                  t.blok, b.nama AS blok_nama,
                  t.t1, ku.nama AS komp_nama,
                  t.t2, py.nama AS sakit_nama,
                  t.t3, bi.nama AS bidang_nama,
                  t.created_at, t.updated_at")
        ->join('departemen d', 'd.id=t.departemen', 'left')
        ->join('blok b', 'b.id=t.blok', 'left')
        ->join('kom_utama ku', 'ku.id=t.t1', 'left')
        ->join('penyakit py', 'py.id=t.t2', 'left')
        ->join('bid_ilmu bi', 'bi.id=t.t3', 'left');
}


    public function index()
    {
        $r   = $this->request;
        $q   = trim((string)$r->getGet('q'));
        $kId = $r->getGet('t1');     // kompetensi
        $pId = $r->getGet('t2');     // penyakit
        $bId = $r->getGet('t3');     // bidang
        $dep = $r->getGet('departemen');
        $blk = $r->getGet('blok');
        $st  = $r->getGet('status');
        $page= max(1,(int)$r->getGet('page'));
        $per = 20;

        $b = $this->baseQuery();
        if ($q  !== '') $b->groupStart()->like('t.register',$q)->orLike('t.vignette',$q)->orLike('t.pertanyaan',$q)->groupEnd();
        if ($kId!=='')  $b->where('t.t1', (int)$kId);
        if ($pId!=='')  $b->where('t.t2', (int)$pId);
        if ($bId!=='')  $b->where('t.t3', (int)$bId);
        if ($dep!=='')  $b->where('t.departemen', (int)$dep);
        if ($blk!=='')  $b->where('t.blok', (int)$blk);
        if ($st !== '' && $st !== null) $b->where('t.status', $st);

        // total
        $bc    = clone $b;
        $total = (int)$bc->countAllResults();

        // rows
        $rows = $b->orderBy('t.id','DESC')
                  ->limit($per, ($page-1)*$per)
                  ->get()->getResultArray();

        // dropdown refs (optional: kosongkan jika belum ada tabelnya)
        $komp   = $this->db->table('kom_utama')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
        $sakit  = $this->db->table('penyakit')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
        $bidang = $this->db->table('bid_ilmu')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
        $deps   = $this->db->table('departemen')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
        $blks   = $this->db->table('blok')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();

        $data = [
            'title'      => 'Soal Teori',
            'menuActive' => 'soal_teori',
            'rows'       => $rows,
            'page'       => $page,
            'per'        => $per,
            'total'      => $total,
            'filters'    => compact('q','kId','pId','bId','dep','blk','st'),
            'komp'       => $komp,
            'sakit'      => $sakit,
            'bidang'     => $bidang,
            'departemen' => $deps,
            'blok'       => $blks,
        ];

        // fragment (AJAX)
        if ($r->isAJAX() || $r->getGet('frag')==='list') {
            $html = view('\Modules\Admin\Views\soal\partials\soal_teori_table', $data);
            return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
                                   ->setContentType('text/html')
                                   ->setBody($html);
        }

        return view('\Modules\Admin\Views\soal\soal_teori_list', $data);
    }
   public function teoriNew()
    {
        $db = db_connect();
        // dropdown
        $departemen = $db->table('departemen')->orderBy('nama','asc')->get()->getResultArray();
        $blok       = $db->table('blok')->orderBy('nama','asc')->get()->getResultArray();
        $komp       = $db->table('kom_utama')->orderBy('nama','asc')->get()->getResultArray();      // sesuaikan nama tabel referensi
        $sakit      = $db->table('penyakit')->orderBy('nama','asc')->get()->getResultArray();
        $bidang     = $db->table('bid_ilmu')->orderBy('nama','asc')->get()->getResultArray();

        // sumber register/kode soal -> dari buat_teori
        $sesi = $db->table('buat_teori')
                  ->select('id,kode,nama,tanggal')
                  ->orderBy('tanggal','desc')->get()->getResultArray();

        return view('\Modules\Admin\Views\soal\soal_teori_form', [
            'title'      => 'Tambah Soal Teori',
            'menuActive' => 'soal_teori',
            'departemen' => $departemen,
            'blok'       => $blok,
            'komp'       => $komp,
            'sakit'      => $sakit,
            'bidang'     => $bidang,
            'sesi'       => $sesi,
        ]);
    }
    public function get($id)
    {
        $row = $this->db->table('ujian_teori')->where('id',$id)->get()->getRowArray();
        if (!$row) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Data tidak ditemukan']);
        return $this->response->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
    }
    /** Simpan form */
    public function teoriStore()
    {

    $isAjax = $this->request->isAJAX();

    // Helper untuk balas JSON saat AJAX, atau redirect saat non-AJAX
    $fail = function(int $code, string $msg) use ($isAjax) {
        if ($isAjax) {
            return $this->response->setStatusCode($code)
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON([
                    'status'     => 'error',
                    'message'    => $msg,
                    'csrf_token' => csrf_hash(),
                ]);
        }
        return redirect()->back()->withInput()->with('error', $msg);
    };

    if ($this->request->getMethod() !== 'POST') {
      
         return $this->response->setStatusCode(405)
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }

    $db   = db_connect();
    $now  = date('Y-m-d H:i:s');
    $uid  = (int) (Auth::user()['uid'] ?? 0);

    // ----- Input -----
    // Di form: Select2 memakai name="id_paket" utk 'kode ujian'. Tetap dukung 'register' bila ada.
    $register   = trim((string) ($this->request->getPost('no_register') ?? $this->request->getPost('no_register') ?? ''));
    $departemen = $this->request->getPost('departemen') ?: '';
    $blok       = $this->request->getPost('blok') ?: '';
$id_paket= $this->request->getPost('id_paket');
    // Validasi minimum
    $rules = [
        'pertanyaan' => 'required',
        'kunci'      => 'required|in_list[A,B,C,D,E]',
    ];
    if ($register === '') {
        return $fail(422, 'Kode ujian wajib diisi.');
    }
    if (! $this->validate($rules)) {
        return $fail(422, implode("\n", $this->validator->getErrors()));
    }

    // ----- Cek kode ujian ada di buat_teori atau osce -----
    $sesiTeori = $db->table('buat_teori')->select('id,jumlah_soal')->where('id', $id_paket)->get()->getRowArray();
    $sesiPrak  = $db->table('osce')->select('id')->where('id', $id_paket)->get()->getRowArray();

    if (!$sesiTeori && !$sesiPrak) {
        return $fail(404, 'Kode ujian tidak ditemukan pada Teori maupun Praktek.');
    }

    // ----- Batas jumlah soal (kalau ada di buat_teori) -----
    if ($sesiTeori) {
        $limit = (int)($sesiTeori['jumlah_soal'] ?? 0);
        if ($limit > 0) {
            // di struktur kamu, kolom 'register' dipakai menyimpan kode ujian
            $sudah = (int) $db->table('ujian_teori')->where('register', $register)->countAllResults();
            if ($sudah >= $limit) {
                return $fail(422, "Jumlah soal untuk sesi ini sudah mencapai batas ($limit).");
            }
        }
    }

    // ----- Files: form kamu mengirim 'files[]' berisi NAMA FILE (hasil upload modal) -----
    $names = $this->request->getPost('files') ?? [];
    $savedList = [];
    if (is_array($names)) {
        foreach ($names as $n) {
            $n = trim(basename($n));
            if ($n !== '') {
                // simpan relatif folder (bebas: bisa hanya nama file juga)
                $savedList[] = $n;
            }
        }
    }

    // Catatan: role non reviewer/admin dipaksa draft (status di form bisa disabled)
    $role      = (int) (Auth::user()['role_id'] ?? Auth::user()['id_role'] ?? -1);
    $canReview = in_array($role, [0,4], true);
    $status    = $canReview ? ($this->request->getPost('status') ?: 'draft') : 'draft';

    // ----- Simpan -----
    $data = [
        't1'         => $this->request->getPost('t1') ?: null,
        't2'         => $this->request->getPost('t2') ?: null,
        't3'         => $this->request->getPost('t3') ?: null,
        'vignette'   => (string)$this->request->getPost('vignette'),
        'pertanyaan' => (string)$this->request->getPost('pertanyaan'),
        'a'          => (string)$this->request->getPost('a'),
        'b'          => (string)$this->request->getPost('b'),
        'c'          => (string)$this->request->getPost('c'),
        'd'          => (string)$this->request->getPost('d'),
        'e'          => (string)$this->request->getPost('e'),
        'bobot_a'    => (float)$this->request->getPost('bobot_a'),
        'bobot_b'    => (float)$this->request->getPost('bobot_b'),
        'bobot_c'    => (float)$this->request->getPost('bobot_c'),
        'bobot_d'    => (float)$this->request->getPost('bobot_d'),
        'bobot_e'    => (float)$this->request->getPost('bobot_e'),
        'kunci'      => (string)$this->request->getPost('kunci'),

        // STRUKTUR TETAP: 'register' = KODE UJIAN (dari buat_teori/osce)
        'register'   => $register,

        'departemen' => $departemen,
        'blok'       => $blok,
        'alasan'     => $canReview ? (string)$this->request->getPost('alasan') : null,
        'referensi'  => $canReview ? (string)$this->request->getPost('referensi') : null,
        'insert_by'  => $uid,
        'status'     => $status,
        'subcpl'     => (string)$this->request->getPost('subcpl'),
        // kolom id_paket memang ada di kode awal, biarkan jika kamu masih pakai
        'id_paket'   => $this->request->getPost('id_paket'),

        'file'       => $savedList ? json_encode($savedList) : null,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    $db->table('ujian_teori')->insert($data);
    $newId = (int)$db->insertID();

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'     => 'ok',
            'message'    => 'Soal tersimpan',
            'id'         => $newId,
            'csrf_token' => csrf_hash(),
        ]);
}
    public function create()
    {
         if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
        }

        $me   = Auth::user();
        $role = (int)($me['role_id'] ?? $me['id_role'] ?? -1);
        $canReview = in_array($role, [0,4], true);

        $kode = trim((string)$this->request->getPost('register'));  // ini KODE ujian (no register)
        if ($kode === '') {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'No. Register/Kode ujian wajib dipilih','csrf_token'=>csrf_hash()]);
        }

        // cek kuota jumlah soal
        $uji = $this->db->table('buat_teori')->where('kode',$kode)->get()->getRowArray();
        if (!$uji) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status'=>'error','message'=>'Kode ujian tidak ditemukan','csrf_token'=>csrf_hash()]);
        }
        $max     = (int)($uji['jumlah_soal'] ?? 0);
        $terisi  = (int)$this->db->table('ujian_teori')->where('register',$kode)->countAllResults();
        if ($terisi >= $max && $max > 0) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'Kuota soal untuk kode ini sudah penuh','csrf_token'=>csrf_hash()]);
        }

        // kumpulkan files[]
        $files = $this->request->getPost('files');
        if (is_array($files)) {
            // simpan sebagai JSON (array filename)
            $fileJSON = json_encode(array_values(array_filter($files)));
        } else {
            $fileJSON = null;
        }

        // Normal input
        $data = [
            't1'         => $this->request->getPost('t1') ?: null,
            't2'         => $this->request->getPost('t2') ?: null,
            't3'         => $this->request->getPost('t3') ?: null,
            'vignette'   => $this->request->getPost('vignette'),
            'pertanyaan' => $this->request->getPost('pertanyaan'),
            'a'          => $this->request->getPost('a'),
            'b'          => $this->request->getPost('b'),
            'c'          => $this->request->getPost('c'),
            'd'          => $this->request->getPost('d'),
            'e'          => $this->request->getPost('e'),
            'bobot_a'    => (float)$this->request->getPost('bobot_a'),
            'bobot_b'    => (float)$this->request->getPost('bobot_b'),
            'bobot_c'    => (float)$this->request->getPost('bobot_c'),
            'bobot_d'    => (float)$this->request->getPost('bobot_d'),
            'bobot_e'    => (float)$this->request->getPost('bobot_e'),
            'kunci'      => strtoupper(trim((string)$this->request->getPost('kunci'))),
            'register'   => $kode, // link ke kode ujian
            'departemen' => $this->request->getPost('departemen') ?: '',
            'blok'       => $this->request->getPost('blok') ?: '',
            'file'       => $fileJSON,
            'insert_by'  => (int)($me['id'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Field khusus reviewer/admin
        if ($canReview) {
            $data['alasan']    = $this->request->getPost('alasan');
            $data['referensi'] = $this->request->getPost('referensi');
            $data['status']    = $this->request->getPost('status') ?: 'draft';
        } else {
            $data['alasan']    = null;
            $data['referensi'] = null;
            $data['status']    = 'draft';
        }

        if (trim((string)$data['pertanyaan']) === '') {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'Pertanyaan wajib diisi','csrf_token'=>csrf_hash()]);
        }

        $this->db->table('ujian_teori')->insert($data);

        return $this->response->setJSON([
            'status'=>'ok','message'=>'Soal tersimpan','id'=>$this->db->insertID(),'csrf_token'=>csrf_hash()
        ]);
    
    }
 public function teoriReview(int $id)
    {
      $row = $this->db->table('ujian_teori ut')
    ->select('ut.*, u.name as dosen')
    ->join('users u', 'u.id = ut.insert_by', 'left')
   
    ->where('ut.id', $id)
    ->get()
    ->getRowArray();

        if (!$row) {
            return redirect()->to(site_url('admin/soal/teori'))
                ->with('error','Soal tidak ditemukan');
        }

        // rakit file gambar dari kolom json "file"
        $files = [];
        if (!empty($row['file'])) {
            $arr = json_decode($row['file'], true) ?: [];
            foreach ($arr as $name) {
                $files[] = [
                    'name' => $name,
                    'url'  => base_url('uploads/soal_teori/'.$name),
                ];
            }
        }

        // riwayat ringkas
       $revs = $this->db->table('revisi')
    ->select('revisi.*,users.name')
     ->join('users', 'users.id = revisi.insert_by', 'left')
   
    ->where('soal_id', $id)
    ->orderBy('created_at','DESC')
    ->get()->getResultArray();

// Mapping status
$statusMap = [
    0 => 'draft',
    1 => 'review',
    2 => 'publish',
    3 => 'reject'
];

// Ubah nilai status sesuai mapping
foreach ($revs as &$rev) {
    $rev['status'] = $statusMap[$rev['status']] ?? 'draft';
}

return view('\Modules\Admin\Views\soal\soal_teori_review', [
    'row'   => $row,
    'files' => $files,
    'revs'  => $revs,
]);
    }

    // LIST RIWAYAT
 public function teoriRevisiList(int $soalId)
{
    $row = $this->db->table('ujian_teori ut')
        ->select('ut.*, u.name as dosen')
        ->join('users u', 'u.id = ut.insert_by', 'left')
        ->where('ut.id', $soalId)
        ->get()
        ->getRowArray();

    $rows = $this->db->table('revisi r')
        ->select('r.*, u.name')
        ->join('users u', 'u.id = r.insert_by', 'left')
        ->where('r.soal_id', $soalId)
        ->orderBy('r.created_at', 'DESC')
        ->get()
        ->getResultArray();

    $statusMap = [
        0 => 'draft',
        1 => 'review',
        2 => 'publish',
        3 => 'reject'
    ];

    foreach ($rows as &$r) {
        $r['status'] = $statusMap[$r['status']] ?? 'draft';
    }
    unset($r); // penting untuk break reference

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'     => 'ok',
            'items'      => $rows,
            'soal'        => $row,       // <-- JS baca res.row
            'csrf_token' => csrf_hash()
        ]);
}



    // GET 1 RIWAYAT (untuk modal detail)
public function teoriRevisiGet(int $id)
{
    $row = $this->db->table('revisi r')
        ->select('r.*, u.name AS reviewer')
        ->join('users u', 'u.id = r.insert_by', 'left')
        ->where('r.id', $id)
        ->get()
        ->getRowArray();

    if (!$row) {
        return $this->response->setStatusCode(404)->setJSON([
            'status' => 'error',
            'message' => 'Data tidak ditemukan',
            'csrf_token' => csrf_hash()
        ]);
    }

    // Map angka → nama status
    $statusMap = [
        0 => 'draft',
        1 => 'review',
        2 => 'publish',
        3 => 'reject',
    ];
    $row['status_name'] = $statusMap[$row['status']] ?? 'draft';

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status' => 'ok',
            'data' => $row,
            'csrf_token' => csrf_hash()
        ]);
}

    // SIMPAN REVISI (ubah status) via jQuery
    public function teoriRevisiSave()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON([
                'status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()
            ]);
        }

        $soalId = (int) $this->request->getPost('soal_id');
        if ($soalId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Soal tidak valid','csrf_token'=>csrf_hash()
            ]);
        }

        $data = ['soal_id'=>$soalId];
        for ($i=1; $i<=20; $i++) {
            $k = 't'.$i;
            $data[$k] = trim((string)$this->request->getPost($k));
        }
        $status          = $this->request->getPost('status') ?: null;
        $now             = date('Y-m-d H:i:s');
        $data['status']  = $status;
        $data['insert_by'] = (int)(Auth::user()['uid'] ?? 0);
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $this->db->table('revisi')->insert($data);

        // ikut update status soal jika ada
        if ($status) {
            $this->db->table('ujian_teori')->where('id',$soalId)
                ->update(['status'=>$status,'revisi_by'=>(int)(Auth::user()['uid'] ?? 0),'updated_at'=>$now]);
        }

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','message'=>'Telaah tersimpan','csrf_token'=>csrf_hash()]);
    }
  public function teoriEdit(int $id)
{
    $db = $this->db;

    $row = $db->table('ujian_teori')->where('id', $id)->get()->getRowArray();
    if (!$row) {
        return redirect()->to(site_url('admin/soal/teori'))->with('error','Data tidak ditemukan.');
    }

    // lookup untuk dropdown
    $komp       = $db->table('kom_utama')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
    $sakit      = $db->table('penyakit')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
    $bidang     = $db->table('bid_ilmu')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
    $departemen = $db->table('departemen')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
    $blok       = $db->table('blok')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();

    // label awal untuk select2 "Kode Ujian"
    $paket = $db->table('buat_teori')->select('id,kode,nama,tanggal')->where('id', $row['id_paket'])->get()->getRowArray();

    $paketText = $paket ? ($paket['kode'].' – '.$paket['nama'].' – '.($paket['tanggal']?date('d/m/Y',strtotime($paket['tanggal'])):'-')) : '-';

    // file -> array nama
 $files = [];
    $raw   = $row['file'] ?? null; // contoh: ["1756678133_f4018966ee7bec51f467.png","..."]
    if ($raw) {
        // kalau bukan JSON (misal string dipisah koma), tetap ditangani
        $names = (strpos(trim($raw), '[') === 0) ? (json_decode($raw, true) ?: [])
                                                : array_map('trim', explode(',', $raw));
        foreach ($names as $fn) {
            if (!$fn) continue;
            $fn = basename($fn); // sanitize
            $files[] = [
                'name' => $fn,
                // sesuaikan dengan lokasi upload Anda (dari uploadMedia sebelumnya)
                'url'  => base_url('uploads/soal_teori/'.$fn),
            ];
        }
    }

    return view('\Modules\Admin\Views\soal\soal_teori_edit', [
        'row'        => $row,
        'files'      => $files,
        'paketText'  => $paketText,
        'komp'       => $komp,
        'sakit'      => $sakit,
        'bidang'     => $bidang,
        'departemen' => $departemen,
        'blok'       => $blok,
    ]);
}

public function teoriUpdate(int $id)
{
    if ($this->request->getMethod() !== 'POST') {
        return $this->response->setStatusCode(405)
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }

    $db  = $this->db;
    $now = date('Y-m-d H:i:s');

    // Data lama
    $row = $db->table('ujian_teori')->where('id', $id)->get()->getRowArray();
    if (!$row) {
        return $this->response->setStatusCode(404)
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()]);
    }

    // Validasi minimal
    $rules = [
        'no_register' => 'required',
        'id_paket'    => 'required',
        'pertanyaan'  => 'required',
        'kunci'       => 'required|in_list[A,B,C,D,E]',
    ];
    if (! $this->validate($rules)) {
        return $this->response->setStatusCode(422)
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON([
                'status'    => 'error',
                'message'   => implode("\n", $this->validator->getErrors()),
                'csrf_token'=> csrf_hash(),
            ]);
    }

    // Validasi paket (boleh dari buat_teori ATAU osce)
    $idPaket   = trim((string) $this->request->getPost('id_paket'));
    $sesiTeori = $db->table('buat_teori')->select('jumlah_soal')->where('id', $idPaket)->get()->getRowArray();

    if (! $sesiTeori) {
        $adaPrak = $db->table('osce')->where('id', $idPaket)->countAllResults();
        if (! $adaPrak) {
            return $this->response->setStatusCode(404)
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON([
                    'status'    => 'error',
                    'message'   => 'Kode ujian tidak ditemukan pada Teori maupun Praktek.',
                    'csrf_token'=> csrf_hash(),
                ]);
        }
    }
    // NOTE: Jika ingin aktifkan limit jumlah_soal, silakan buka komentar validasi di sini.

    // =========================
    // FILES
    // =========================

    // 1) Ambil daftar nama file dari hidden inputs "files[]"
    $names = (array) $this->request->getPost('files');
    $names = array_values(array_filter(array_map('trim', $names)));

    // 2) (Opsional) Fallback: terima upload langsung dari form (name="media" atau "files")
    //    Disimpan ke FOLDER PUBLIK agar bisa diakses browser.
    $publicDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'soal_teori';
    if (! is_dir($publicDir)) {
        @mkdir($publicDir, 0775, true);
    }

    $newUploaded = [];
    $filesBag    = $this->request->getFiles();
    $candidates  = [];

    // dukung name="media" (single/multiple)
    if (isset($filesBag['media'])) {
        $candidates = array_merge($candidates, is_array($filesBag['media']) ? $filesBag['media'] : [$filesBag['media']]);
    }
    // dukung name="files" (single/multiple) — hati-hati tidak bentrok dengan hidden "files[]"
    if (isset($filesBag['files'])) {
        $candidates = array_merge($candidates, is_array($filesBag['files']) ? $filesBag['files'] : [$filesBag['files']]);
    }

    foreach ($candidates as $f) {
        if (! $f instanceof \CodeIgniter\HTTP\Files\UploadedFile) continue;
        if ($f->getError() !== UPLOAD_ERR_OK || ! $f->isValid()) continue;

        $ext = strtolower($f->getExtension());
        if (! in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) continue;
        if ($f->getSize() > 5 * 1024 * 1024) continue; // 5MB

        $nn = $f->getRandomName(); // file name saja
        // simpan ke public/uploads/soal_teori
        $f->move($publicDir, $nn, true);
        $newUploaded[] = $nn;
    }

    // 3) Gabungkan file lama (hidden) + yang baru di-upload langsung
    $finalFiles = array_values(array_unique(array_merge($names, $newUploaded)));

    // =========================
    // DATA UPDATE
    // =========================
    $data = [
        't1'          => $this->request->getPost('t1') ?: null,
        't2'          => $this->request->getPost('t2') ?: null,
        't3'          => $this->request->getPost('t3') ?: null,
        'vignette'    => (string) $this->request->getPost('vignette'),
        'pertanyaan'  => (string) $this->request->getPost('pertanyaan'),
        'a'           => (string) $this->request->getPost('a'),
        'b'           => (string) $this->request->getPost('b'),
        'c'           => (string) $this->request->getPost('c'),
        'd'           => (string) $this->request->getPost('d'),
        'e'           => (string) $this->request->getPost('e'),
        'bobot_a'     => (float)  $this->request->getPost('bobot_a'),
        'bobot_b'     => (float)  $this->request->getPost('bobot_b'),
        'bobot_c'     => (float)  $this->request->getPost('bobot_c'),
        'bobot_d'     => (float)  $this->request->getPost('bobot_d'),
        'bobot_e'     => (float)  $this->request->getPost('bobot_e'),
        'kunci'       => (string) $this->request->getPost('kunci'),
        'register'    => (string) $this->request->getPost('no_register'),
        'id_paket'    => $idPaket,
        'departemen'  => $this->request->getPost('departemen') ?: null,
        'blok'        => $this->request->getPost('blok') ?: null,
        'alasan'      => (string) $this->request->getPost('alasan'),
        'referensi'   => (string) $this->request->getPost('referensi'),
        'status'      => (string) ($this->request->getPost('status') ?: 'draft'),
        'subcpl'      => (string) $this->request->getPost('subcpl'),
        'file'        => $finalFiles ? json_encode($finalFiles) : null, // simpan array nama file
        'updated_at'  => $now,
    ];

    $db->table('ujian_teori')->update($data, ['id' => $id]);

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'     => 'ok',
            'message'    => 'Perubahan disimpan',
            'csrf_token' => csrf_hash(),
        ]);
}

    public function delete($id)
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Method not allowed']);
        }
        $this->db->table('ujian_teori')->delete(['id'=>$id]);
        return $this->response->setJSON(['status'=>'ok','message'=>'Soal dihapus','csrf_token'=>csrf_hash()]);
    }
      // === Select2 sumber "kode ujian" (buat_teori) ===
    public function searchKodeTeori()
    {
        $q = trim((string)$this->request->getGet('q'));
        $b = $this->db->table('buat_teori')
             ->select('id,kode,nama,tanggal')
             ->orderBy('tanggal','DESC');

        if ($q !== '') {
            $b->groupStart()
              ->like('kode', $q)->orLike('nama', $q)
              ->groupEnd();
        }
        $rows = $b->limit(20)->get()->getResultArray();

        $items = array_map(function($r){
            $teks = $r['kode'].' - '.$r['nama'].' - '.tgl_id($r['tanggal']);
            return ['id'=>$r['id'], 'text'=>$teks];
        }, $rows);

        return $this->response->setJSON(['items'=>$items]);
    }

    // === Upload gambar dari modal ===
   // Contoh di Modules\Admin\Controllers\SoalTeoriController.php

public function uploadMedia()
{
    $file = $this->request->getFile('media');
    if (!$file || !$file->isValid()) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'File tidak valid',
            'csrf_token' => csrf_hash(),
        ]);
    }

    // Validasi sederhana
    $allowedExt  = ['jpg','jpeg','png'];
    $ext         = strtolower($file->getExtension());
    $allowedMime = ['image/jpeg','image/png'];
    $mime        = $file->getClientMimeType();

    if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'Hanya file JPG/PNG',
            'csrf_token' => csrf_hash(),
        ]);
    }
    if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'Ukuran maksimum 5MB',
            'csrf_token' => csrf_hash(),
        ]);
    }

    // Simpan ke public/uploads/soal_teori
    $dirPublic = FCPATH . 'uploads/soal_teori';
    is_dir($dirPublic) || mkdir($dirPublic, 0775, true);

    $newName = $file->getRandomName();
    $file->move($dirPublic, $newName);

    // URL yang bisa diakses publik
    $url = base_url('uploads/soal_teori/' . $newName);

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status' => 'ok',
            'name'   => $newName,       // simpan ke hidden input "files[]"
            'url'    => $url,           // dipakai untuk preview
            'csrf_token' => csrf_hash()
        ]);
}

public function deleteMedia()
{
    $name = trim((string) $this->request->getPost('name'));
    if ($name === '') {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'Nama file kosong',
            'csrf_token' => csrf_hash(),
        ]);
    }
    $path = FCPATH . 'uploads/soal_teori/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
}


// =============== DOWNLOAD TEMPLATE ===============
public function importTemplate()
{
    // --- ambil data referensi ---
    $bt  = $this->db->table('buat_teori')->select('id,nama')->orderBy('id','DESC')->get()->getResultArray();
    $ku  = $this->db->table('kom_utama')->select('id,nama')->orderBy('nama','ASC')->get()->getResultArray();
    $py  = $this->db->table('penyakit')->select('id,nama')->orderBy('nama','ASC')->get()->getResultArray();
    $bi  = $this->db->table('bid_ilmu')->select('id,nama')->orderBy('nama','ASC')->get()->getResultArray();
    $dep = $this->db->table('departemen')->select('id,nama')->orderBy('nama','ASC')->get()->getResultArray(); // NEW
    $blk = $this->db->table('blok')->select('id,nama')->orderBy('nama','ASC')->get()->getResultArray();        // NEW

    $ss = new Spreadsheet();

    // ================= Sheet 1: Template Soal =================
    $s1 = $ss->getActiveSheet();
    $s1->setTitle('Template Soal');

    $header = [
        'id_paket','no_register',
        't1','t2','t3','departemen','blok',
        'vignette','pertanyaan',
        'a','b','c','d','e','kunci',
        'bobot_a','bobot_b','bobot_c','bobot_d','bobot_e',
        'alasan','referensi','subcpl','status'
    ];
    $s1->fromArray([$header], null, 'A1');

    $s1->getStyle('A1:'.$s1->getHighestColumn().'1')->getFont()->setBold(true);
    $s1->getStyle('A1:'.$s1->getHighestColumn().'1')->getFill()
       ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
    $s1->getStyle('A1:'.$s1->getHighestColumn().'1')->getBorders()->getBottom()
       ->setBorderStyle(Border::BORDER_THIN);

    foreach (['H','I','J','K','L','M','N'] as $c) $s1->getColumnDimension($c)->setWidth(35);
    $s1->getColumnDimension('A')->setWidth(14);
    $s1->getColumnDimension('B')->setWidth(22);
    $s1->freezePane('A2');
    $s1->setCellValue('A4','Catatan: Isi kolom id_paket, t1, t2, t3, departemen, blok menggunakan ID dari sheet "Referensi".');

    // ================= Sheet 2: Referensi =================
    $s2 = $ss->createSheet(1)->setTitle('Referensi');

    $writeBlock = function($sheet, string $title, array $rows, string $startCell) {
        // startCell contoh "A1", "D30", dsb (pakai kolom satu huruf)
        $col = $startCell[0];
        $row = (int)substr($startCell, 1);

        $sheet->setCellValue($startCell, $title);
        $sheet->getStyle($startCell)->getFont()->setBold(true);

        // header
        $sheet->setCellValue([ord($col)-64, $row+1], 'id');
        $sheet->setCellValue([ord($col)-63, $row+1], 'nama');
        $sheet->getStyle($col.($row+1).':'.chr(ord($col)+1).($row+1))->getFont()->setBold(true);

        // data
        $r = $row+2;
        foreach ($rows as $x) {
            $sheet->setCellValue([ord($col)-64, $r], (int)($x['id'] ?? 0));
            $sheet->setCellValue([ord($col)-63, $r], (string)($x['nama'] ?? ''));
            $r++;
        }

        // border tipis
        $sheet->getStyle($col.($row+1).':'.chr(ord($col)+1).($r-1))
              ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
    };

    // blok-blok referensi (posisi diatur agar rapi)
    $writeBlock($s2, 'BUAT_TEORI (id, nama)',  $bt,  'A1');
    $writeBlock($s2, 'KOM_UTAMA (id, nama)',   $ku,  'D1');
    $writeBlock($s2, 'PENYAKIT (id, nama)',    $py,  'A30');
    $writeBlock($s2, 'BID_ILMU (id, nama)',    $bi,  'D30');
    $writeBlock($s2, 'DEPARTEMEN (id, nama)',  $dep, 'A60'); // NEW
    $writeBlock($s2, 'BLOK (id, nama)',        $blk, 'D60'); // NEW

    // lebar kolom referensi
    foreach (['A','D'] as $c) $s2->getColumnDimension($c)->setWidth(8);   // id
    foreach (['B','E'] as $c) $s2->getColumnDimension($c)->setWidth(42);  // nama

    // ================= Output download =================
    $writer = new Xlsx($ss);
    $tmpDir = WRITEPATH.'temp';
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
    $file = $tmpDir.'/template_soal_teori.xlsx';
    $writer->save($file);

    while (ob_get_level() > 0) { ob_end_clean(); }
    return $this->response
        ->download($file, null)
        ->setFileName('template_soal_teori.xlsx');
}
// =============== UPLOAD & IMPORT ===============
// public function importUpload()
// {
//     if (!$this->request->is('post')) {
//         return $this->response->setStatusCode(405)
//             ->setJSON(['status'=>'error','message'=>'Method not allowed','csrf_token'=>csrf_hash()]);
//     }

//     $file = $this->request->getFile('file');
//     if (!$file || !$file->isValid()) {
//         return $this->response->setStatusCode(422)
//             ->setJSON(['status'=>'error','message'=>'File tidak valid','csrf_token'=>csrf_hash()]);
//     }

//     $ext = strtolower($file->getExtension());
//     if (!in_array($ext, ['xlsx','xls','csv'], true)) {
//         return $this->response->setStatusCode(422)
//             ->setJSON(['status'=>'error','message'=>'Hanya xlsx/xls/csv','csrf_token'=>csrf_hash()]);
//     }

//     // Baca spreadsheet
//     $tmp  = $file->getTempName();
//     $spr  = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
//     $ws   = $spr->getActiveSheet();
//     $rows = $ws->toArray(null, true, true, true); // A,B,C,...

//     if (count($rows) < 2) {
//         return $this->response->setStatusCode(422)
//             ->setJSON(['status'=>'error','message'=>'File kosong','csrf_token'=>csrf_hash()]);
//     }

//     // Header (baris 1) -> normalisasi lower-case
//     $header = array_map(static fn($v)=> strtolower(trim((string)$v)), array_values($rows[1]));
//     $map    = array_flip($header); // nama kolom -> index

//     // Helper ambil nilai berdasarkan nama kolom (case-insensitive)
//     $get = function(array $row, string $name) use ($map) {
//         $name = strtolower($name);
//         if (!isset($map[$name])) return null;
//         $idx  = $map[$name];              // posisi dalam header
//         $keys = array_keys($row);         // ['A','B',...]
//         $col  = $keys[$idx] ?? null;      // huruf kolom
//         return $col ? (string)$row[$col] : null;
//     };

//     $db   = $this->db;
//     $now  = date('Y-m-d H:i:s');
//     $me   = \Modules\Auth\Libraries\Auth::user();
//     $role = (int)($me['role_id'] ?? $me['id_role'] ?? -1);
//     $canReview = in_array($role, [0,4], true);
//     $uid  = (int)($me['uid'] ?? $me['id'] ?? 0);

//     // Cache lookup untuk efisiensi
//     $cache = [ 'paketByKode' => [] ];

//     $errors = [];
//     $ok     = 0;

//     // Loop mulai baris ke-2
//     foreach ($rows as $i => $r) {
//         if ($i === 1) continue; // skip header

//         $idPaket   = trim((string)($get($r, 'id_paket') ?? ''));
//         $kodeUjian = trim((string)($get($r, 'kode_ujian') ?? ''));
//         $noReg     = trim((string)($get($r, 'no_register') ?? ''));
//         $t1        = (int)($get($r, 't1') ?? 0);
//         $t2        = (int)($get($r, 't2') ?? 0);
//         $t3        = (int)($get($r, 't3') ?? 0);
//         $dep       = (string)($get($r, 'departemen_id') ?? '');
//         $blok      = (string)($get($r, 'blok_id') ?? '');
//         $subcpl    = (string)($get($r, 'subcpl') ?? '');
//         $vignette  = (string)($get($r, 'vignette') ?? '');
//         $tanya     = (string)($get($r, 'pertanyaan') ?? '');
//         $a         = (string)($get($r, 'a') ?? '');
//         $b         = (string)($get($r, 'b') ?? '');
//         $c         = (string)($get($r, 'c') ?? '');
//         $d         = (string)($get($r, 'd') ?? '');
//         $e         = (string)($get($r, 'e') ?? '');
//         $ba        = (float)($get($r, 'bobot_a') ?? 0);
//         $bb        = (float)($get($r, 'bobot_b') ?? 0);
//         $bc        = (float)($get($r, 'bobot_c') ?? 0);
//         $bd        = (float)($get($r, 'bobot_d') ?? 0);
//         $be        = (float)($get($r, 'bobot_e') ?? 0);
//         $kunci     = strtoupper(trim((string)($get($r, 'kunci') ?? '')));
//         // status impor → paksa angka 0 (draft) agar aman utk kolom INT/TINYINT
//         $statusInt = 0;

//         // Skip baris kosong total
//         if ($tanya==='' && $a==='' && $b==='' && $c==='' && $d==='' && $e==='') {
//             continue;
//         }

//         // Cari id_paket via kode_ujian jika id kosong
//         if ($idPaket === '' && $kodeUjian !== '') {
//             if (!isset($cache['paketByKode'][$kodeUjian])) {
//                 $row = $db->table('buat_teori')->select('id')->where('kode',$kodeUjian)->get()->getRowArray();
//                 $cache['paketByKode'][$kodeUjian] = (int)($row['id'] ?? 0);
//             }
//             $idPaket = (string)$cache['paketByKode'][$kodeUjian];
//         }

//         $idPaket = (int)$idPaket;

//         // Validasi minimum
//         $err = [];
//         if ($idPaket <= 0)                        $err[] = 'id_paket/kode_ujian wajib';
//         if ($t1 <= 0 || $t2 <= 0 || $t3 <= 0)     $err[] = 't1_id/t2_id/t3_id wajib (ID referensi)';
//         if ($tanya === '')                        $err[] = 'pertanyaan wajib';
//         if (!in_array($kunci, ['A','B','C','D','E'], true)) $err[] = 'kunci harus A/B/C/D/E';

//         if ($err) {
//             $errors[] = "Baris $i: ".implode('; ', $err);
//             continue;
//         }

//         // Generate no_register bila kosong
//         if ($noReg === '') {
//             $noReg = $this->buildRegister($t1,$t2,$t3);
//         }

//         // Opsional: batasi jumlah soal sesuai paket
//         $batas = $db->table('buat_teori')->select('jumlah_soal')->where('id',$idPaket)->get()->getRowArray();
//         $limit = (int)($batas['jumlah_soal'] ?? 0);
//         if ($limit > 0) {
//             $terisi = (int)$db->table('ujian_teori')->where('id_paket',$idPaket)->countAllResults();
//             if ($terisi >= $limit) {
//                 $errors[] = "Baris $i: kuota soal untuk paket #$idPaket sudah penuh ($limit).";
//                 continue;
//             }
//         }

//         // Siapkan data insert
//         $data = [
//             'id_paket'   => $idPaket,
//             'register'   => $noReg,     // sekarang dipakai sebagai No.Register
//             't1'         => $t1,
//             't2'         => $t2,
//             't3'         => $t3,
//             'departemen' => $dep !== '' ? (int)$dep : '',
//             'blok'       => $blok !== '' ? (int)$blok : '',
//             'subcpl'     => $subcpl ?: '',
//             'vignette'   => $vignette,
//             'pertanyaan' => $tanya,
//             'a'          => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'e' => $e,
//             'bobot_a'    => $ba, 'bobot_b' => $bb, 'bobot_c' => $bc, 'bobot_d' => $bd, 'bobot_e' => $be,
//             'kunci'      => $kunci,
          
           
//             'insert_by'  => $uid ?: null,
//             'created_at' => $now,
//             'updated_at' => $now,
//         ];

//         // INSERT + ambil error detail
//         try {
//             $okInsert = $db->table('ujian_teori')->insert($data);
//             if ($okInsert === false) {
//                 $e = $db->error(); // ['code'=>..., 'message'=>...]
//                 $msg = trim(($e['code'] ?? '').' '.$e['message'] ?? '');
//                 if ($msg === '' || $msg === '0') $msg = 'insert gagal (unknown DB error)';
//                 $errors[] = "Baris $i: $msg";
//                 continue;
//             }
//             $ok++;
//         } catch (\Throwable $ex) {
//             $errors[] = "Baris $i: ".$ex->getMessage();
//             continue;
//         }
//     }

//     return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
//         ->setJSON([
//             'status'     => 'ok',
//             'inserted'   => $ok,
//             'failed'     => count($errors),
//             'errors'     => $errors,
//             'csrf_token' => csrf_hash()
//         ]);
// }
// =============== UPLOAD & IMPORT (VERTIKAL + WIDE) ===============
public function importUpload()
{
    if (!$this->request->is('post')) {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Method not allowed','csrf_token'=>csrf_hash()]);
    }

    $file = $this->request->getFile('file');
    if (!$file || !$file->isValid()) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'File tidak valid','csrf_token'=>csrf_hash()]);
    }

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['xlsx','xls','csv'], true)) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'Hanya xlsx/xls/csv','csrf_token'=>csrf_hash()]);
    }

    // Baca spreadsheet
    $tmp  = $file->getTempName();
    $spr  = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
    $ws   = $spr->getActiveSheet();
    $rows = $ws->toArray(null, true, true, true); // indeks 1..N, kolom A,B,C,...

    if (count($rows) < 1) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'File kosong','csrf_token'=>csrf_hash()]);
    }

    // ==== Deteksi format vertikal (toleran spasi di sekitar "|") ====
    $cellA1 = (string)($rows[1]['A'] ?? '');
    $tokA1  = preg_split('/\s*\|\s*/', trim($cellA1), -1, PREG_SPLIT_NO_EMPTY);
    $firstTag = strtolower($tokA1[0] ?? '');         // "soal" / "pilihan"
    $looksVertical = in_array($firstTag, ['soal','pilihan'], true);

    if (!$looksVertical) {
        // fallback: format wide (header-lebar)
        return $this->importUploadWide($rows);
    }

    // =========================
    // PARSER FORMAT VERTIKAL
    // =========================
    $db   = $this->db;
    $now  = date('Y-m-d H:i:s');
    $me   = \Modules\Auth\Libraries\Auth::user();
    $uid  = (int)($me['uid'] ?? $me['id'] ?? 0);

    $inserted = 0;
    $errors   = [];
    $curr     = null; // buffer 1 soal aktif

    // Parse kolom A menjadi token, tahan spasi di sekitar "|"
    $parseA = function($cellA): array {
        $s = trim((string)$cellA);
        if ($s === '') return [];
        // hilangkan spasi di sekitar '|', kompres pipa ganda
        $s = preg_replace('~\s*\|\s*~', '|', $s);
        $s = preg_replace('~\|{2,}~', '|', $s);
        $s = trim($s, '| ');
        if ($s === '') return [];
        return array_map('trim', explode('|', $s));
    };

    // Simpan buffer soal ke DB
    $flush = function() use (&$curr, &$errors, &$inserted, $db, $now, $uid) {
        if (!$curr) return;

        $kunci = $curr['kunci'] ?? '';
        if (!in_array($kunci, ['A','B','C','D','E'], true)) {
            $errors[] = "Soal {$curr['register']}: kunci tidak valid/kosong";
            $curr = null; return;
        }
        if (trim($curr['pertanyaan']) === '') {
            $errors[] = "Soal {$curr['register']}: teks soal kosong";
            $curr = null; return;
        }

        $data = [
            'id_paket'   => (int)($curr['id_paket'] ?? 0) ?: '',   // opsional
            'register'   => (string)$curr['register'],

            // referensi opsional (biarkan null bila tak disediakan)
            't1'         => (int)($curr['t1'] ?? 0) ?: '',
            't2'         => (int)($curr['t2'] ?? 0) ?: '',
            't3'         => (int)($curr['t3'] ?? 0) ?: '',
            'departemen' => (int)($curr['departemen'] ?? 0) ?: '',
            'blok'       => (int)($curr['blok'] ?? 0) ?: '',
            'subcpl'     => (string)($curr['subcpl'] ?? ''),

            'vignette'   => (string)$curr['vignette'],
            'pertanyaan' => (string)$curr['pertanyaan'],
            'a'          => (string)($curr['A'] ?? ''),
            'b'          => (string)($curr['B'] ?? ''),
            'c'          => (string)($curr['C'] ?? ''),
            'd'          => (string)($curr['D'] ?? ''),
            'e'          => (string)($curr['E'] ?? ''),
            'bobot_a'    => (float)($curr['bobot']['A'] ?? 0),
            'bobot_b'    => (float)($curr['bobot']['B'] ?? 0),
            'bobot_c'    => (float)($curr['bobot']['C'] ?? 0),
            'bobot_d'    => (float)($curr['bobot']['D'] ?? 0),
            'bobot_e'    => (float)($curr['bobot']['E'] ?? 0),
            'kunci'      => (string)$kunci,

            // simpan gambar jika ada (kolom file berupa JSON array)
            'file'       => !empty($curr['gambar']) ? json_encode([basename($curr['gambar'])]) : null,

            'insert_by'  => $uid ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $ok = $db->table('ujian_teori')->insert($data);
            if ($ok === false) {
                $e = $db->error();
                $errors[] = "Soal {$curr['register']}: gagal insert (".$e['code'].") ".$e['message'];
            } else {
                $inserted++;
            }
        } catch (\Throwable $ex) {
            $errors[] = "Soal {$curr['register']}: ".$ex->getMessage();
        }
        $curr = null;
    };

    $rowNum = 0;
    foreach ($rows as $r) {
        $rowNum++;

        $tokens = $parseA($r['A'] ?? '');
        if (!$tokens) continue;

        $tag = strtolower($tokens[0] ?? '');

        if ($tag === 'soal') {
            // simpan soal sebelumnya lebih dulu
            $flush();

            // A: soal|<register>
            $register = trim((string)($tokens[1] ?? ''));

            // B: vignette; jika hanya berisi kata "VIGNETTE", kosongkan
            $vignette = (string)($r['B'] ?? '');
            if (trim(strtolower($vignette)) === 'vignette') $vignette = '';

            // C: pertanyaan
            $pertanyaan = (string)($r['C'] ?? '');

            // D: nama file gambar (opsional), abaikan "no_image.jpg"
            $gambar = trim((string)($r['D'] ?? ''));
            if ($gambar === '' || strtolower($gambar) === 'no_image.jpg') {
                $gambar = '';
            }

            // siapkan buffer
            $curr = [
                'register'   => $register,
                'vignette'   => $vignette,
                'pertanyaan' => $pertanyaan,
                'gambar'     => $gambar,
                'A'=>null,'B'=>null,'C'=>null,'D'=>null,'E'=>null,
                'bobot' => ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0],
                'kunci' => '',
            ];
            continue;
        }

        if ($tag === 'pilihan') {
            if (!$curr) {
                $errors[] = "Baris $rowNum: 'pilihan' tanpa pembuka 'soal'";
                continue;
            }

            // A: pilihan|<register>|<A-E>
            $label = strtoupper(trim((string)($tokens[2] ?? '')));
            if (!in_array($label, ['A','B','C','D','E'], true)) {
                $errors[] = "Soal {$curr['register']}: label pilihan tidak valid";
                continue;
            }

            // C diprioritaskan untuk teks opsi; jika kosong, ambil B
            $teksOpsi = (string)($r['C'] ?? '');
            if ($teksOpsi === '') $teksOpsi = (string)($r['B'] ?? '');

            // D: "kunci|bobot" -> angka 1 menandai kunci, angka lain bobot
            $rawD = trim((string)($r['D'] ?? ''));
            $rawD = trim($rawD, '|');
            $isKey = false;
            $bobot = 0.0;

            if ($rawD !== '') {
                $parts = preg_split('/\s*\|\s*/', $rawD);
                $parts = array_values(array_filter(array_map('trim', (array)$parts), fn($x)=>$x!==''));

                // ambil angka saja
                $nums = [];
                foreach ($parts as $p) {
                    if (is_numeric($p)) $nums[] = $p + 0;
                }

                if (count($nums) === 1) {
                    if ((int)$nums[0] === 1) { $isKey = true; $bobot = 0; }
                    elseif ($nums[0] > 1)   { $bobot = (float)$nums[0]; }
                } elseif (count($nums) >= 2) {
                    if ((int)$nums[0] === 1 && is_numeric($nums[1])) {
                        $isKey = true; $bobot = (float)$nums[1];
                    } elseif ((int)$nums[1] === 1 && is_numeric($nums[0])) {
                        $isKey = true; $bobot = (float)$nums[0];
                    } else {
                        $bobot = (float)$nums[0];
                    }
                }
            }

            $curr[$label] = $teksOpsi;
            $curr['bobot'][$label] = $bobot;
            if ($isKey) $curr['kunci'] = $label;

            continue;
        }

        // baris lain diabaikan
    }

    // simpan soal terakhir
    $flush();

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'     => 'ok',
            'inserted'   => $inserted,
            'failed'     => count($errors),
            'errors'     => $errors,
            'csrf_token' => csrf_hash()
        ]);
}

private function importUploadWide(array $rows)
{
    // Header (baris 1) -> normalisasi lower-case
    $header = array_map(static fn($v)=> strtolower(trim((string)$v)), array_values($rows[1]));
    $map    = array_flip($header);

    // helper ambil nilai by nama kolom (case-insensitive)
    $get = function(array $row, string $name) use ($map) {
        $name = strtolower($name);
        if (!isset($map[$name])) return null;
        $idx  = $map[$name];
        $keys = array_keys($row);           // A,B,C,...
        $col  = $keys[$idx] ?? null;
        return $col ? (string)$row[$col] : null;
    };

    $db   = $this->db;
    $now  = date('Y-m-d H:i:s');
    $me   = \Modules\Auth\Libraries\Auth::user();
    $uid  = (int)($me['uid'] ?? $me['id'] ?? 0);

    $ok = 0; $errors = [];

    foreach ($rows as $i => $r) {
        if ($i === 1) continue; // skip header

        $idPaket = (int)($get($r,'id_paket') ?? 0);
        $t1      = (int)($get($r,'t1') ?? 0);
        $t2      = (int)($get($r,'t2') ?? 0);
        $t3      = (int)($get($r,'t3') ?? 0);
        $dep     = (string)($get($r,'departemen') ?? '');
        $blok    = (string)($get($r,'blok') ?? '');
        $noReg   = (string)($get($r,'no_register') ?? '');
        $tanya   = (string)($get($r,'pertanyaan') ?? '');
        $kunci   = strtoupper(trim((string)($get($r,'kunci') ?? '')));

        // skip baris kosong
        if ($tanya==='' && ($get($r,'a')??'')==='' && ($get($r,'b')??'')==='' && ($get($r,'c')??'')==='' && ($get($r,'d')??'')==='' && ($get($r,'e')??'')==='') {
            continue;
        }

        $err = [];
        if ($idPaket<=0) $err[]='id_paket wajib';
        if ($t1<=0 || $t2<=0 || $t3<=0) $err[]='t1/t2/t3 wajib';
        if ($tanya==='') $err[]='pertanyaan wajib';
        if (!in_array($kunci, ['A','B','C','D','E'], true)) $err[]='kunci harus A-E';
        if ($err){ $errors[]="Baris $i: ".implode('; ',$err); continue; }

        if ($noReg==='') $noReg = $this->buildRegister($t1,$t2,$t3);

        $data = [
            'id_paket'   => $idPaket,
            'register'   => $noReg,
            't1'         => $t1,
            't2'         => $t2,
            't3'         => $t3,
            'departemen' => $dep!==''?(int)$dep:null,
            'blok'       => $blok!==''?(int)$blok:null,
            'subcpl'     => (string)($get($r,'subcpl') ?? ''),
            'vignette'   => (string)($get($r,'vignette') ?? ''),
            'pertanyaan' => $tanya,
            'a'          => (string)($get($r,'a') ?? ''),
            'b'          => (string)($get($r,'b') ?? ''),
            'c'          => (string)($get($r,'c') ?? ''),
            'd'          => (string)($get($r,'d') ?? ''),
            'e'          => (string)($get($r,'e') ?? ''),
            'bobot_a'    => (float)($get($r,'bobot_a') ?? 0),
            'bobot_b'    => (float)($get($r,'bobot_b') ?? 0),
            'bobot_c'    => (float)($get($r,'bobot_c') ?? 0),
            'bobot_d'    => (float)($get($r,'bobot_d') ?? 0),
            'bobot_e'    => (float)($get($r,'bobot_e') ?? 0),
            'kunci'      => $kunci,
            'insert_by'  => $uid ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $okInsert = $db->table('ujian_teori')->insert($data);
            if ($okInsert === false) {
                $e = $db->error();
                $errors[] = "Baris $i: (".$e['code'].") ".$e['message'];
                continue;
            }
            $ok++;
        } catch (\Throwable $ex) {
            $errors[] = "Baris $i: ".$ex->getMessage();
            continue;
        }
    }

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'=>'ok',
            'inserted'=>$ok,
            'failed'=>count($errors),
            'errors'=>$errors,
            'csrf_token'=>csrf_hash()
        ]);
}

public function exportZipPerPaket()
{
    // Ambil filter GET (opsional, sama seperti index)
    $r   = $this->request;
    $q   = trim((string)$r->getGet('q'));
    $kId = $r->getGet('t1');
    $pId = $r->getGet('t2');
    $bId = $r->getGet('t3');
    $dep = $r->getGet('departemen');
    $blk = $r->getGet('blok');
    $st  = $r->getGet('status'); // bisa kosong → semua status

    // Query dasar + join buat_teori utk meta paket
$b = $this->db->table('ujian_teori t')
    ->select('t.id,t.id_paket,t.pertanyaan,t.a,t.b,t.c,t.d,t.e,
              bt.id AS bt_id, bt.kode AS bt_kode, bt.nama AS bt_nama, 
              bt.tanggal AS bt_tanggal, bt.mulai AS bt_mulai, bt.selesai AS bt_selesai')
    ->join('buat_teori bt','bt.id = t.id_paket','left')
    ->where('DATE(bt.tanggal) >= CURDATE()', null, false);

// tambahkan filter lainnya...
if ($q  !== '') $b->groupStart()->like('t.register',$q)->orLike('t.pertanyaan',$q)->groupEnd();
// dst...

// sebelum get():
$sql = $b->getCompiledSelect();

// tampilkan ke view (sementara debugging)
echo "<pre>SQL: ".$sql."</pre>";

// baru eksekusi query
$rows = $b->orderBy('t.id_paket','ASC')
          ->orderBy('t.id','ASC')
          ->get()
          ->getResultArray();

    if (!$rows) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'=>'error',
            'message'=>'Data soal tidak ditemukan untuk kriteria ini.',
            'csrf_token'=>csrf_hash(),
        ]);
    }

    // Group by id_paket
    $groups = [];
    foreach ($rows as $r) {
        $pid = (int)($r['id_paket'] ?? 0);
        if (!isset($groups[$pid])) {
            $groups[$pid] = [
                'meta'  => [
                    'id'      => $r['bt_id'] ?? $pid,
                    'kode'    => $r['bt_kode'] ?? '',
                    'nama'    => $r['bt_nama'] ?? '',
                    'tanggal' => $r['bt_tanggal'] ?? null,
                    'mulai'   => $r['bt_mulai'] ?? null,
                    'selesai' => $r['bt_selesai'] ?? null,
                ],
                'items' => []
            ];
        }
        $groups[$pid]['items'][] = [
            'pertanyaan' => (string)$r['pertanyaan'],
            'a' => (string)($r['a'] ?? ''),
            'b' => (string)($r['b'] ?? ''),
            'c' => (string)($r['c'] ?? ''),
            'd' => (string)($r['d'] ?? ''),
            'e' => (string)($r['e'] ?? ''),
        ];
    }

    // Persiapan folder/temp
    $exportDir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'exports' . DIRECTORY_SEPARATOR . 'soal_teori';
    if (!is_dir($exportDir)) @mkdir($exportDir, 0775, true);

    $stamp   = date('Ymd_His');
    $zipPath = $exportDir . DIRECTORY_SEPARATOR . "soal_teori_per_paket_$stamp.zip";

    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        return $this->response->setStatusCode(500)->setJSON([
            'status'=>'error',
            'message'=>'Gagal membuat file ZIP.',
            'csrf_token'=>csrf_hash(),
        ]);
    }

    // Helper: sanitize filename
    $sanitize = static function(string $s): string {
        $s = preg_replace('~[^\pL\d\-_.]+~u', '_', $s);
        $s = trim($s, '_');
        return $s !== '' ? $s : 'dokumen';
    };
    // Helper: format tanggal jam
    $fmtDate = static function($d) {
        if (!$d) return '-';
        $ts = strtotime($d);
        return $ts ? date('d/m/Y', $ts) : (string)$d;
    };
    $fmtTime = static function($t) {
        if (!$t) return '-';
        $ts = strtotime($t);
        return $ts ? date('H:i', $ts) : (string)$t;
    };

    // Generate 1 DOCX per paket
    foreach ($groups as $pid => $data) {
        $meta  = $data['meta'];
        $items = $data['items'];

        $phpWord = new PhpWord();
        $sec     = $phpWord->addSection([
            'marginLeft'   => 1200,
            'marginRight'  => 1200,
            'marginTop'    => 1000,
            'marginBottom' => 800,
            'orientation'  => 'portrait',
        ]);

        // Header
        $sec->addText('UJIAN TEORI', ['bold'=>true, 'size'=>14], ['alignment'=>'center', 'spaceAfter'=>200]);
        $sec->addTextBreak(1);

        // Info paket
        $info = [
            'Nama'    => $meta['nama'] ?: '-',
            'Tanggal' => $fmtDate($meta['tanggal']),
            'Mulai'   => $fmtTime($meta['mulai']),
            'Selesai' => $fmtTime($meta['selesai']),
            'Kode'    => $meta['kode'] ?: '-',
        ];
        foreach ($info as $k=>$v) {
            $sec->addText("$k: $v", ['size'=>11], ['spaceAfter'=>80]);
        }
        $sec->addTextBreak(1);

        // Isi: nomor, pertanyaan, pilihan A-E (tanpa kunci)
        $no = 1;
        foreach ($items as $it) {
            $sec->addText($no.'. '.$it['pertanyaan'], ['size'=>11], ['spaceAfter'=>100, 'lineHeight'=>1.2]);

            // Pilihan — tampilkan hanya yang tidak kosong
            $pil = ['A'=>$it['a'], 'B'=>$it['b'], 'C'=>$it['c'], 'D'=>$it['d'], 'E'=>$it['e']];
            foreach ($pil as $abjad=>$teks) {
                if (trim((string)$teks) === '') continue;
                $sec->addText("   $abjad. ".$teks, ['size'=>11], ['spaceAfter'=>20]);
            }

            $sec->addTextBreak(1);
            $no++;
        }

        // Simpan ke file sementara
        $kode  = $sanitize((string)$meta['kode']);
        $nama  = $sanitize((string)$meta['nama']);
       $namaFile = $sanitize((string)($meta['nama'] ?: "paket_$pid"));
$fname    = $namaFile . '.docx';
$docx     = $exportDir . DIRECTORY_SEPARATOR . $fname;

        $docx  = $exportDir . DIRECTORY_SEPARATOR . $fname;

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($docx);

        // Masukkan ke ZIP
        $zip->addFile($docx, $fname);
    }

    $zip->close();

    // (Opsional) Bersihkan file docx sementara setelah zip terkunci
    // Bisa dihapus langsung, atau biarkan utk audit.
    // try {
    //     foreach (glob($exportDir.'/*.docx') as $f) { @unlink($f); }
    // } catch (\Throwable $e) {}

    while (ob_get_level() > 0) { ob_end_clean(); }
    return $this->response
        ->download($zipPath, null)
        ->setFileName("soal_teori_per_paket_$stamp.zip");
}


}


?>