<?php
namespace Modules\Admin\Controllers;

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
use PhpOffice\PhpWord\IOFactory as WordIO;

class SoalPraktekController extends BaseController
{
    
    /* ===================== LIST ===================== */

public function index()
{
    // dropdowns
    $data['komp']       = $this->db->table('kom_utama')->orderBy('nama')->get()->getResultArray();
    $data['sakit']      = $this->db->table('penyakit')->orderBy('nama')->get()->getResultArray();
    $data['bidang']     = $this->db->table('bid_ilmu')->orderBy('nama')->get()->getResultArray();
    $data['departemen'] = $this->db->table('departemen')->orderBy('nama')->get()->getResultArray();
    $data['blok']       = $this->db->table('blok')->orderBy('nama')->get()->getResultArray();

    // data list awal
    $data += $this->buildQuery();
    $data['menuActive'] = 'soal_praktek';

    // >>> kunci: kalau dipanggil AJAX dengan frag=list, kirim partial tabel
    if ($this->request->getGet('frag') === 'list') {
        return view('\Modules\Admin\Views\praktek\partials\praktek_table', $data);
    }

    // normal page
    return view('\Modules\Admin\Views\praktek\praktek_list', $data);
}

    public function table()
    {
        $data = $this->buildQuery();
        return view('\Modules\Admin\Views\praktek\partials\praktek_table', $data);
    }

private function buildQuery(): array
{
    $page = max(1, (int)($this->request->getGet('page') ?: 1));
    $per  = max(5, (int)($this->request->getGet('per')  ?: 10));

    $b = $this->db->table('ujian_praktek u');
    $b->select("
        u.*,
        -- map status int -> label (sesuaikan dengan skema kamu)
        CASE u.status
            WHEN 0 THEN 'draft'
            WHEN 1 THEN 'review'
            WHEN 2 THEN 'publish'
            WHEN 3 THEN 'reject'
            ELSE 'draft'
        END AS status_label,
        (
            SELECT COUNT(*) FROM aspek a WHERE a.soal_id = u.id
        ) AS aspek_jlh
    ");

    if ($q = trim((string)$this->request->getGet('q'))) {
        $b->groupStart()
          ->like('u.register', $q)
          ->orLike('u.skenario', $q)
          ->groupEnd();
    }

    if ($t1 = $this->request->getGet('t1')) $b->where('u.t1', $t1);
    if ($t2 = $this->request->getGet('t2')) $b->where('u.t2', $t2);
    if ($t3 = $this->request->getGet('t3')) $b->where('u.t3', $t3);
    if ($d  = $this->request->getGet('departemen')) $b->where('u.departemen', $d);
    if ($bl = $this->request->getGet('blok')) $b->where('u.blok', $bl);

    // filter status: terima angka (0-3) atau string ('draft','review',...)
    if ('' !== ($st = (string)$this->request->getGet('status'))) {
        $map = ['draft'=>0,'review'=>1,'publish'=>2,'reject'=>3];
        if (ctype_digit($st)) {
            $b->where('u.status', (int)$st);
        } else {
            $b->where('u.status', $map[strtolower($st)] ?? 0);
        }
    }

    $total = (clone $b)->countAllResults(false);
    $rows  = $b->orderBy('u.created_at','DESC')->limit($per, ($page-1)*$per)->get()->getResultArray();

    return compact('rows','page','per','total');
}
public function aspekList()
{
    $soalId = (int)$this->request->getGet('soal_id');
    if ($soalId <= 0) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'soal_id tidak valid','csrf_token'=>csrf_hash()]);
    }

    $rows = $this->db->table('aspek')
        ->select('id, soal_id, t1, t2, t3, aspek, keterangan, file, insert_by, created_at, updated_at')
        ->where('soal_id', $soalId)
        ->orderBy('created_at','DESC')
        ->get()->getResultArray();

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','items'=>$rows,'csrf_token'=>csrf_hash()]);
}

public function aspekDelete()
{
    if (!$this->request->is('post')) {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }
    $id = (int)$this->request->getPost('id');
    if ($id <= 0) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'ID tidak valid','csrf_token'=>csrf_hash()]);
    }

    $row = $this->db->table('osce')->where('id',$id)->get()->getRowArray();
    if (!$row) {
        return $this->response->setStatusCode(404)
            ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()]);
    }

    $this->db->table('osce')->where('id',$id)->delete();

    // hitung ulang jumlah aspek dari tabel aspek
    $jlh = (int)$this->db->table('aspek')->where('soal_id', (int)$row['soal_id'])->countAllResults();

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','message'=>'Berhasil dihapus','soal_id'=>$row['soal_id'],'jlh'=>$jlh,'csrf_token'=>csrf_hash()]);
}


    /* ===================== CREATE / EDIT ===================== */

    public function create()
    {
        $data = $this->formLookups();
        $data['row']   = [];
        $data['files'] = [];
        return view('\Modules\Admin\Views\praktek\praktek_form', $data);
    }

    public function edit(int $id)
    {
        $row = $this->db->table('ujian_praktek')->where('id',$id)->get()->getRowArray();
        if (!$row) return redirect()->to(site_url('admin/soal/praktek'))->with('error','Data tidak ditemukan');

        $files=[];
        if (!empty($row['file'])) {
            foreach (json_decode($row['file'], true) ?: [] as $name) {
                $files[] = ['name'=>$name,'url'=>base_url('uploads/soal_praktek/'.$name)];
            }
        }

        $data = $this->formLookups();
        $data['row']   = $row;
        $data['files'] = $files;

        return view('\Modules\Admin\Views\praktek\praktek_form', $data);
    }

    private function formLookups(): array
    {
        return [
            'komp'       => $this->db->table('kom_utama')->orderBy('nama')->get()->getResultArray(),
            'sakit'      => $this->db->table('penyakit')->orderBy('nama')->get()->getResultArray(),
               'ranah'      => $this->db->table('ranah_ket')->orderBy('nama','asc')->get()->getResultArray(),
            'bidang'     => $this->db->table('bid_ilmu')->orderBy('nama')->get()->getResultArray(),
            'departemen' => $this->db->table('departemen')->orderBy('nama')->get()->getResultArray(),
            'blok'       => $this->db->table('blok')->orderBy('nama')->get()->getResultArray(),
        ];
    }

    /* ===================== STORE / UPDATE (AJAX) ===================== */

    public function store(): ResponseInterface
    {
        return $this->persist();
    }

    public function update(int $id): ResponseInterface
    {
        return $this->persist($id);
    }

    private function persist(int $id = 0): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON([
                'status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $uid = (int)(Auth::user()['uid'] ?? 0);

        // files[] yang sudah di-upload lewat endpoint upload()
        $names = $this->request->getPost('files') ?? [];
        if (!is_array($names)) $names = [$names];
        $names = array_values(array_filter($names, fn($x)=>!!$x));
        $fileJson = $names ? json_encode($names) : null;

        $data = [
            'register'   => trim((string)$this->request->getPost('register')),
            't1'         => $this->request->getPost('t1') ?: null,
            't2'         => $this->request->getPost('t2') ?: null,
            'sub2'       => $this->request->getPost('sub2') ?: null,
            't3'         => $this->request->getPost('t3') ?: null,
            't4'         => $this->request->getPost('t4') ?: null,

            'tujuan'     => $this->request->getPost('tujuan'),
            'skenario'   => $this->request->getPost('skenario'),
            'tugas_k'    => $this->request->getPost('tugas_k'),
            'tugas_p'    => $this->request->getPost('tugas_p'),
            'intruksi'   => $this->request->getPost('intruksi'),
            'peralatan'  => $this->request->getPost('peralatan'),

            'departemen' => $this->request->getPost('departemen') ?: null,
            'blok'       => $this->request->getPost('blok') ?: null,
            'referensi'  => $this->request->getPost('referensi'),
            'status'     => $this->request->getPost('status') ?: 'draft',
            'file'       => $fileJson,
            'updated_at' => $now,
        ];

        // validasi minimal
        if (!$data['register'] || !$data['skenario']) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'No. register & skenario wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }

        if ($id) {
            $this->db->table('ujian_praktek')->where('id',$id)->update($data);
        } else {
            $data['insert_by'] = $uid;
            $data['created_at']= $now;
            $this->db->table('ujian_praktek')->insert($data);
            $id = (int)$this->db->insertID();
        }

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','id'=>$id,'message'=>'Disimpan','csrf_token'=>csrf_hash()]);
    }

    /* ===================== DELETE (AJAX) ===================== */

    public function delete(int $id): ResponseInterface
    {
        $this->db->table('ujian_praktek')->where('id',$id)->delete();
        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }

    /* ===================== UPLOAD GAMBAR (AJAX) ===================== */

public function upload(): \CodeIgniter\HTTP\ResponseInterface
{
    $f = $this->request->getFile('media');
    if (!$f || !$f->isValid()) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'File tidak valid',
            'csrf_token' => csrf_hash(),
        ]);
    }

    $allow = ['jpg','jpeg','png','webp'];
    if (!in_array(strtolower($f->getExtension()), $allow, true)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'Hanya jpg/png/webp',
            'csrf_token' => csrf_hash(),
        ]);
    }

    if ($f->getSize() > 5 * 1024 * 1024) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => 'error',
            'message' => 'Maksimal 5MB',
            'csrf_token' => csrf_hash(),
        ]);
    }

    // simpan di public/uploads/soal_praktek
    $dir = rtrim(FCPATH, '/').'/uploads/soal_praktek';

if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
    return $this->response->setStatusCode(500)->setJSON(['status'=>'error','message'=>'Gagal membuat folder upload']);
}
if (!is_writable($dir)) {
    return $this->response->setStatusCode(500)->setJSON(['status'=>'error','message'=>'Folder upload tidak bisa ditulis']);
}
if (!is_writable(WRITEPATH.'logs')) {
    return $this->response->setStatusCode(500)->setJSON(['status'=>'error','message'=>'Folder logs tidak bisa ditulis']);
}

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return $this->response->setStatusCode(500)->setJSON([
            'status' => 'error',
            'message' => 'Folder upload tidak bisa dibuat',
            'csrf_token' => csrf_hash(),
        ]);
    }

    $name = $f->getRandomName();

    // param ke-3 'true' untuk overwrite jika nama sama
    if (!$f->move($dir, $name, true)) {
        return $this->response->setStatusCode(500)->setJSON([
            'status' => 'error',
            'message' => 'Gagal memindahkan file (cek permission)',
            'csrf_token' => csrf_hash(),
        ]);
    }

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON([
        'status' => 'ok',
        'name'   => $name,
        'url'    => base_url('uploads/soal_praktek/'.$name),
        'csrf_token' => csrf_hash(),
    ]);
}


    public function uploadDelete(): ResponseInterface
    {
        $name = basename((string)$this->request->getPost('name'));
        $path ='uploads/soal_praktek/'.$name;
        if (is_file($path)) @unlink($path);
        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }

    /* ===================== REVIEW & RIWAYAT ===================== */

    // public function review(int $id)
    // {
    //     $row = $this->db->table('ujian_praktek')->where('id',$id)->get()->getRowArray();
    //     if (!$row) return redirect()->to(site_url('admin/soal/praktek'))->with('error','Data tidak ditemukan');

    //     $files=[];
    //     if (!empty($row['file'])) {
    //         foreach (json_decode($row['file'], true) ?: [] as $n) {
    //             $files[] = ['name'=>$n,'url'=>base_url('/uploads/soal_praktek/'.$n)];
    //         }
    //     }

    //     $revs = $this->db->table('revisi')
    //         ->select('id,status,created_at,updated_at')
    //         ->where('soal_id',$id)->orderBy('created_at','DESC')->get()->getResultArray();

    //     return view('\Modules\Admin\Views\praktek\praktek_review', compact('row','files','revs'));
    // }

    public function revisiList(int $soalId): ResponseInterface
    {
        $rows = $this->db->table('revisi')
            ->select('id,status,created_at,updated_at')
            ->where('soal_id',$soalId)->orderBy('created_at','DESC')->get()->getResultArray();

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','items'=>$rows,'csrf_token'=>csrf_hash()]);
    }

    public function revisiGet(int $id): ResponseInterface
    {
        $row = $this->db->table('revisi')->where('id',$id)->get()->getRowArray();
        if (!$row) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Tidak ada','csrf_token'=>csrf_hash()]);
        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
    }
public function praktekAdd()
{
    // dropdown data (samakan sumbernya dg milik Anda)
    $data = [
        'komp'       => $this->db->table('kom_utama')->orderBy('nama','asc')->get()->getResultArray(),
        'kelompok'   => $this->db->table('kel_penyakit')->orderBy('nama','asc')->get()->getResultArray(),
        'penyakit'   => $this->db->table('penyakit')->orderBy('nama','asc')->get()->getResultArray(),  // sub2
        'ranah'      => $this->db->table('ranah_ket')->orderBy('nama','asc')->get()->getResultArray(),
        'bidang'     => $this->db->table('bid_ilmu')->orderBy('nama','asc')->get()->getResultArray(),
        'departemen' => $this->db->table('departemen')->orderBy('nama','asc')->get()->getResultArray(),
        'blok'       => $this->db->table('blok')->orderBy('nama','asc')->get()->getResultArray(),
    ];
    return view('\Modules\Admin\Views\praktek\praktek_add', $data);
}

/** Select2 Kode Ujian OSCE */
public function praktekCariKode()
{
    $q = trim((string)$this->request->getGet('q'));
    $b = $this->db->table('osce')->select('id,kode,nama_ujian,tanggal');
    if ($q!=='') {
        $b->groupStart()
            ->like('kode', $q)->orLike('nama_ujian', $q)
          ->groupEnd();
    }
    $rows = $b->orderBy('tanggal','DESC')->limit(20)->get()->getResultArray();
    $items = array_map(function($r){
        return ['id'=>$r['kode'], 'text'=> $r['kode'].' — '.$r['nama_ujian'].' — '.$r['tanggal']];
    }, $rows);
    return $this->response->setJSON(['items'=>$items]);
}

/** Generate No.Register: {id_soal}/{kode_kom}/{kode_penyakit}/{kode_bidang} */
public function praktekRegGenerate()
{
    $t1   = (int)$this->request->getGet('t1');
    $sub2 = (int)$this->request->getGet('sub2');
    $t4   = (int)$this->request->getGet('t4');

    // ambil kode singkat dari master (sesuaikan kolom kode Anda)
    $k1 = $this->db->table('kom_utama')->select('kode')->where('id',$t1)->get()->getRow('kode') ?? '00';
    $k2 = $this->db->table('penyakit')->select('kode')->where('id',$sub2)->get()->getRow('kode') ?? 'P.00';
    $k4 = $this->db->table('bid_ilmu')->select('kode')->where('id',$t4)->get()->getRow('kode') ?? 'K.00';

    // next id soal praktek (sangat sederhana)
    $nextId = (int)($this->db->table('ujian_praktek')->selectMax('id','m')->get()->getRow('m')) + 1;

    $reg = $nextId.'/'.$k1.'/'.$k2.'/'.$k4.'/'.date('d/m/Y');
    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','register'=>$reg,'csrf_token'=>csrf_hash()]);
}

/** Upload gambar ke /uploads/soal_praktek */
public function praktekUpload()
{
    $file = $this->request->getFile('media');
    if (!$file || !$file->isValid()) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'=>'error','message'=>'File tidak valid','csrf_token'=>csrf_hash()
        ]);
    }
    $valid = ['jpg','jpeg','png'];
    if (!in_array(strtolower($file->getExtension()), $valid, true)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'=>'error','message'=>'Hanya jpg/png','csrf_token'=>csrf_hash()
        ]);
    }
    if ($file->getSize() > 5*1024*1024) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'=>'error','message'=>'Maks 5MB','csrf_token'=>csrf_hash()
        ]);
    }

    $dir = 'uploads/soal_praktek';
    is_dir($dir) || mkdir($dir, 0775, true);
    $new = $file->getRandomName();
    $file->move($dir, $new);
    $url = base_url('uploads/soal_praktek/'.$new);

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','name'=>$new,'url'=>$url,'csrf_token'=>csrf_hash()]);
}

public function praktekUploadDelete()
{
    $name = basename((string)$this->request->getPost('name'));
    if ($name) {
        $path = 'uploads/soal_praktek/'.$name;
        if (is_file($path)) @unlink($path);
    }
    return $this->response->setJSON(['ok'=>true,'csrf_token'=>csrf_hash()]);
}

/** Simpan via AJAX */
public function praktekSimpan()
{
    if ($this->request->getMethod() !== 'POST') {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan']);
    }

    $now = date('Y-m-d H:i:s');
  $uid = (int) (Auth::id() ?? 0);    

    // validasi minimal
    $rules = [
        'register'   => 'required',
      
    ];
    if (! $this->validate($rules)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'=>'error','message'=> implode("\n",$this->validator->getErrors()),
            'csrf_token'=>csrf_hash()
        ]);
    }

    // cek kode ujian ada di OSCE
    // $ada = $this->db->table('osce')->where('kode',$this->request->getPost('kode_ujian'))->countAllResults();
    // if (!$ada) {
    //     return $this->response->setStatusCode(404)->setJSON([
    //         'status'=>'error','message'=>'Kode ujian OSCE tidak ditemukan','csrf_token'=>csrf_hash()
    //     ]);
    // }

    // files[] hasil upload (hanya simpan nama; akses via //..)
    $names = $this->request->getPost('files');
    $fileJson = $names ? json_encode(array_values((array)$names)) : null;

    $data = [
        'register'    => trim((string)$this->request->getPost('register')),
        't1'          => $this->request->getPost('t1') ?: null,
        't2'          => $this->request->getPost('t2') ?: null,
        'sub2'        => $this->request->getPost('sub2') ?: null,
        't3'          => $this->request->getPost('t3') ?: null,
        't4'          => $this->request->getPost('t4') ?: null,
        'tujuan'      => (string)$this->request->getPost('tujuan'),
        'skenario'    => (string)$this->request->getPost('skenario'),
        'tugas_k'     => (string)$this->request->getPost('tugas_k'),
        'tugas_p'     => (string)$this->request->getPost('tugas_p'),
        'intruksi'    => (string)$this->request->getPost('intruksi'),
        'peralatan'   => (string)$this->request->getPost('peralatan'),
        'departemen'  => $this->request->getPost('departemen') ?: '',
        'blok'        => $this->request->getPost('blok') ?: '',
        'referensi'   => (string)$this->request->getPost('referensi'),
        'file'        => $fileJson,
        'insert_by'   => $uid,
        'status'      => $this->request->getPost('status') ?: 'draft',
        'created_at'  => $now,
        'updated_at'  => $now,
    ];

    $this->db->table('ujian_praktek')->insert($data);
    return $this->response->setJSON([
        'status'=>'ok','message'=>'Soal praktek tersimpan','id'=>$this->db->insertID(),
        'csrf_token'=>csrf_hash()
    ]);
}
    public function revisiSave(): ResponseInterface
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
        }
        $soalId = (int)$this->request->getPost('soal_id');
        if ($soalId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status'=>'error','message'=>'Soal tidak valid','csrf_token'=>csrf_hash()]);
        }

        $now = date('Y-m-d H:i:s');
        $ins = [
            'soal_id'    => $soalId,
            'status'     => $this->request->getPost('status') ?: 'review',
            'insert_by'  => (int)(Auth::user()['id'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        for($i=1;$i<=20;$i++){ $k='t'.$i; $ins[$k]=trim((string)$this->request->getPost($k)); }

        $this->db->table('revisi')->insert($ins);

        // ikut update status soal
        $this->db->table('ujian_praktek')->where('id',$soalId)->update(['status'=>$ins['status'],'updated_at'=>$now]);

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','message'=>'Telaah disimpan','csrf_token'=>csrf_hash()]);
    }

    private function statusToDb($st){
  // terima 'draft/review/publish/reject' atau '0..3'
  $map = ['draft'=>0,'review'=>1,'publish'=>2,'reject'=>3];
  if ($st === '0' || $st === '1' || $st === '2' || $st === '3' || is_int($st)) return (int)$st;
  return $map[strtolower((string)$st)] ?? 1; // default 'review'
}
private function statusToLabel($val){
  $labels = ['draft','review','publish','reject'];
  if (is_numeric($val)) return $labels[(int)$val] ?? 'draft';
  $s = strtolower((string)$val);
  return in_array($s,$labels,true) ? $s : 'draft';
}

public function review($id)
{
    $id = (int)$id;
  $row = $this->db->table('ujian_praktek up')
    ->select('up.*, u.name as dosen')
    ->join('users u', 'u.id = up.insert_by', 'left')      // join ke tabel users
   
    ->where('up.id', $id)
    ->get()
    ->getRowArray();

    if (!$row) return redirect()->to(site_url('admin/soal/praktek'))->with('error','Soal tidak ditemukan');

    // Map nama untuk t1..t4, departemen, blok
    $maps = [
      't1' => $this->db->table('kom_utama')->select('id,nama')->get()->getResultArray(),
      't2' => $this->db->table('penyakit')->select('id,nama')->get()->getResultArray(),
      't3' => $this->db->table('bid_ilmu')->select('id,nama')->get()->getResultArray(),
      't4' => $this->db->table('bid_ilmu')->select('id,nama')->get()->getResultArray(), // ganti jika beda tabel
      'departemen' => $this->db->table('departemen')->select('id,nama')->get()->getResultArray(),
      'blok'       => $this->db->table('blok')->select('id,nama')->get()->getResultArray(),
    ];
    $m = [];
    foreach ($maps as $k=>$arr){ $m[$k] = array_column($arr,'nama','id'); }

    // history awal (5 terbaru)
    $history = $this->db->table('revisi_prak')
    ->select('revisi_prak.*, u.name')
     ->join('users u', 'u.id = revisi_prak.insert_by', 'left')      // join ke tabel users
   
    ->where('revisi_prak.soal_id', $id)
    ->orderBy('revisi_prak.created_at','DESC')
    ->limit(10)
    ->get()->getResultArray();

foreach ($history as &$h) {
    $h['status_label'] = $this->statusToLabel($h['status'] ?? 'draft');
}
unset($h);

    // normalisasi status label utk tampilan
    $row['status_label'] = $this->statusToLabel($row['status'] ?? 'draft');
    
     $files=[];
        if (!empty($row['file'])) {
            foreach (json_decode($row['file'], true) ?: [] as $n) {
                $files[] = ['name'=>$n,'url'=>base_url('/uploads/soal_praktek/'.$n)];
            }
        }

    

    return view('\Modules\Admin\Views\praktek\praktek_review', [
      'row'=>$row, 'map'=>$m, 'files'=>$files, 'history'=>$history, 'menuActive'=>'ujian_praktek'
    ]);
}

public function revisiPrakSave(): \CodeIgniter\HTTP\ResponseInterface
{
    if (!$this->request->is('post')) {
        return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }

    $soalId = (int)$this->request->getPost('soal_id');
    if ($soalId <= 0) {
        return $this->response->setStatusCode(422)->setJSON(['status'=>'error','message'=>'Soal tidak valid','csrf_token'=>csrf_hash()]);
    }

   $statusLabel = $this->statusToLabel($this->request->getPost('status') ?? 'review');
$statusDb    = $this->statusToDb($statusLabel);

$now = date('Y-m-d H:i:s');
$ins = [
  'soal_id'    => (int)$this->request->getPost('soal_id'),
  'status'     => $this->request->getPost('status'),
  'insert_by'  => (int)(Auth::user()['uid'] ?? 0),
  'created_at' => $now,
  'updated_at' => $now,
];

// T1–T12: hanya 'ya' | 'tidak' | null
for ($i=1; $i<=12; $i++){
  $k = 't'.$i;
  $v = strtolower(trim((string)$this->request->getPost($k)));
  $ins[$k] = in_array($v, ['ya','tidak'], true) ? $v : '';
}

// T13–T14: catatan bebas
for ($i=13; $i<=14; $i++){
  $k = 't'.$i;
  $ins[$k] = trim((string)$this->request->getPost($k));
}

$this->db->table('revisi_prak')->insert($ins);
$this->db->table('ujian_praktek')->where('id',$ins['soal_id'])->update([
  'status'=>$statusDb,'updated_at'=>$now
]);

return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
  ->setJSON(['status'=>'ok','message'=>'Telaah disimpan','csrf_token'=>csrf_hash()]);

}

// ========== HISTORY (select id agar bisa diklik) ==========
public function revisiPrakHistory($soalId)
{
    $soalId = (int) $soalId;

    $rows = $this->db->table('revisi_prak rp')
        ->select('rp.id, rp.created_at, rp.status, u.name')   // <-- name untuk "Petugas"
        ->join('users u', 'u.id = rp.insert_by', 'left')
        ->where('rp.soal_id', $soalId)
        ->orderBy('rp.created_at', 'DESC')
        ->get()
        ->getResultArray();

    // Map 0/1/2/3 -> draft/review/publish/reject (fallback ke string existing)
    $map = [0=>'draft', 1=>'review', 2=>'publish', 3=>'reject'];
    foreach ($rows as &$h) {
        $s = $h['status'];
        $h['status_label'] = is_numeric($s) ? ($map[(int)$s] ?? 'draft') : strtolower((string)$s);
    }
    unset($h);

    // kembalikan partial HTML yang akan di-inject ke #revHistory
    return view('\Modules\Admin\Views\praktek\partials\rev_history', ['rows' => $rows]);
}


// ========== DETAIL (JSON) ==========
public function revisiPrakGet($id)
{
    $id = (int)$id;

    // join users agar dapat nama reviewer
    $row = $this->db->table('revisi_prak rp')
        ->select('rp.*, u.name AS reviewer_name')
        ->join('users u', 'u.id = rp.insert_by', 'left')
        ->where('rp.id', $id)
        ->get()->getRowArray();

    if (!$row) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()
        ]);
    }

    // status -> label konsisten (draft/review/publish/reject)
    $row['status_label'] = $this->statusToLabel($row['status'] ?? 'draft');

    // fallback reviewer_name kalau null
    if (empty($row['reviewer_name']) && !empty($row['insert_by'])) {
        $row['reviewer_name'] = 'User #'.$row['insert_by'];
    }

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
}



public function importTemplate(): ResponseInterface
{
    $db = $this->db ?? db_connect();

    // referensi utk sheet "REFERENSI"
    $komp   = $db->table('kom_utama')->select('id,nama')->orderBy('id')->get()->getResultArray();
    $kel    = $db->table('kel_penyakit')->select('id,nama')->orderBy('id')->get()->getResultArray();
    $sakit  = $db->table('penyakit')->select('id,nama')->orderBy('id')->get()->getResultArray();
    $ranah  = $db->table('ranah_ket')->select('id,nama')->orderBy('id')->get()->getResultArray();
    $bidang = $db->table('bid_ilmu')->select('id,nama')->orderBy('id')->get()->getResultArray();
    $deps   = $db->table('departemen')->select('id,nama')->orderBy('id')->get()->getResultArray();
    $bloks  = $db->table('blok')->select('id,nama')->orderBy('id')->get()->getResultArray();

    $ss = new Spreadsheet();

    // === SHEET 1: SOAL ===
    $sh = $ss->getActiveSheet();
    $sh->setTitle('SOAL');
    $sh->fromArray([[
        'register*',
        't1_id (kom_utama)',
        't2_kel_id (kel_penyakit)',
        'sub2_penyakit_id',
        't3_ranah_id (ranah_ket)',
        't4_bidang_id (bid_ilmu)',
        'tujuan','skenario*','tugas_k','tugas_p','intruksi','peralatan',
        'departemen_id','blok_id','referensi','status (draft/review/publish/reject atau 0..3)'
    ]], null, 'A1');

    // contoh baris
    $sh->fromArray([[
        'REG-001', 1, 1, 2, 1, 3,
        'Tujuan …','Skenario contoh …','Tugas K …','Tugas P …','Instruksi …','Peralatan …',
        1, 1, 'Buku X hal 12', 'draft'
    ]], null, 'A2');

    // === SHEET 2: ASPEK ===
    $sa = $ss->createSheet();
    $sa->setTitle('ASPEK');
    $sa->fromArray([[
        'soal_register*', 'aspek*', 'keterangan',
        't1_id (kom_utama)', 't2_penyakit_id', 't3_bidang_id'
    ]], null, 'A1');
    $sa->fromArray([['REG-001','Komunikasi pasien','menjelaskan diagnosis',1,2,3]], null, 'A2');

    // === SHEET 3: REFERENSI ===
    $sr = $ss->createSheet();
    $sr->setTitle('REFERENSI');

    $row = 1;
    $dump = function($title, array $rows) use (&$row, $sr){
        $sr->setCellValue("A{$row}", $title); $row++;
        $sr->fromArray([['ID','Nama']], null, "A{$row}"); $row++;
        foreach ($rows as $r) { $sr->fromArray([[ (int)$r['id'], (string)$r['nama'] ]], null, "A{$row}"); $row++; }
        $row++;
    };
    $dump('kom_utama',   $komp);
    $dump('kel_penyakit',$kel);
    $dump('penyakit',    $sakit);
    $dump('ranah_ket',   $ranah);
    $dump('bid_ilmu',    $bidang);
    $dump('departemen',  $deps);
    $dump('blok',        $bloks);

    // output
    $fname = 'template_praktek_'.date('Ymd_His').'.xlsx';
    $this->response->setHeader('Content-Type','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $this->response->setHeader('Content-Disposition','attachment; filename="'.$fname.'"');
    $writer = IOFactory::createWriter($ss, 'Xlsx');
    ob_start(); $writer->save('php://output'); $bin = ob_get_clean();
    return $this->response->setBody($bin);
}


public function importUpload(): \CodeIgniter\HTTP\ResponseInterface
{
    if (!$this->request->is('post')) {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Method not allowed','csrf_token'=>csrf_hash()]);
    }

    $f = $this->request->getFile('file');
    if (!$f || !$f->isValid()) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'File tidak valid','csrf_token'=>csrf_hash()]);
    }
    $ext = strtolower($f->getExtension());
    if (!in_array($ext, ['xlsx','xls','csv'], true)) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'Hanya xlsx/xls/csv','csrf_token'=>csrf_hash()]);
    }

    $ss        = \PhpOffice\PhpSpreadsheet\IOFactory::load($f->getTempName());
    $soalSheet = $ss->getSheetByName('SOAL')  ?? $ss->getSheet(0);
    $aspSheet  = $ss->getSheetByName('ASPEK') ?? null;

    $rowsSoal = $soalSheet ? $soalSheet->toArray(null, true, true, true) : [];
    $rowsAsp  = $aspSheet  ? $aspSheet->toArray(null, true, true, true)  : [];

    if (count($rowsSoal) < 2) {
        return $this->response->setStatusCode(422)
            ->setJSON(['status'=>'error','message'=>'Sheet SOAL kosong','csrf_token'=>csrf_hash()]);
    }

    /* ================== Helpers yang lebih “tahan banting” ================== */

    // cari baris header otomatis (mencari baris yang memuat minimal 2 kata kunci)
    $findHeaderRow = function(array $rows, array $keywords): int {
        $maxScan = min(count($rows), 10);
        for ($i=1; $i<=$maxScan; $i++) {
            $vals = array_map(fn($v)=> strtolower(trim((string)$v)), array_values($rows[$i] ?? []));
            $hit  = 0;
            foreach ($keywords as $kw) {
                foreach ($vals as $v) {
                    if ($v!=='' && (str_contains($v, strtolower($kw)))) { $hit++; break; }
                }
            }
            if ($hit >= 2) return $i; // anggap ini header
        }
        return 1; // fallback
    };

    // normalisasi string (hapus spasi/tanda baca untuk pencocokan kolom)
    $slug = function(string $s): string {
        $s = strtolower($s);
        $s = preg_replace('/\s+/u','', $s);
        $s = preg_replace('/[^a-z0-9_]/u','', $s);
        return $s;
    };

    // petakan baris header -> {A:'skenario', B:'register', ...} (sudah lowercase & trim)
    $hdrMap = function(array $row) {
        $m=[]; foreach($row as $c=>$v){ $m[$c] = strtolower(trim((string)$v)); } return $m;
    };

    // cari kolom dengan pencocokan fleksibel (persis/substring/slug)
    $colOf = function(array $hdr, string $name) use ($slug) {
        $target = $slug($name);
        foreach ($hdr as $c => $h) {
            $h1 = strtolower(trim((string)$h));
            if ($h1 === $name)               return $c;                 // persis
            if (str_contains($h1, $name))    return $c;                 // substring
            if ($slug($h1) === $target)      return $c;                 // slug match
            if (str_contains($slug($h1), $target) || str_contains($target, $slug($h1))) return $c; // fuzzy
        }
        return null;
    };

    // ambil mentah berdasarkan beberapa alias nama kolom
    $getRaw = function(array $hdr, array $row, array $names) use ($colOf) {
        foreach ($names as $n) {
            $c = $colOf($hdr, strtolower($n));
            if ($c && array_key_exists($c, $row)) return $row[$c];
        }
        return null;
    };

    // string (trim, normalisasi newline)
    $getStr = function(array $hdr, array $row, array $names) use ($getRaw) {
        $v = $getRaw($hdr, $row, $names);
        if ($v === null) return '';
        $s = (string)$v;
        $s = preg_replace("/\r\n|\r|\n/u", "\n", $s);
        return trim($s);
    };

    // integer
    $getInt = function(array $hdr, array $row, array $names) use ($getStr) {
        $v = $getStr($hdr, $row, $names);
        return is_numeric($v) ? (int)$v : 0;
    };

    /* ================== DB & util generator register ================== */

    $db  = $this->db ?? db_connect();
    $now = date('Y-m-d H:i:s');
    $uid = (int)((\Modules\Auth\Libraries\Auth::user()['uid'] ?? \Modules\Auth\Libraries\Auth::user()['id']) ?? 0);

    $kodeOf = function(string $table, int $id) use ($db){
        if ($id<=0) return null;
        return $db->table($table)->select('kode')->where('id',$id)->get()->getRow('kode');
    };

    $buildRegister = function(int $t1, int $sub2, int $t4) use ($db, $kodeOf){
        $nextId = (int)($db->table('ujian_praktek')->selectMax('id','m')->get()->getRow('m')) + 1;
        $k1 = $kodeOf('kom_utama', $t1) ?? '00';
        $k2 = $kodeOf('penyakit',  $sub2) ?? 'P.00';
        $k4 = $kodeOf('bid_ilmu',  $t4) ?? 'K.00';
        return $nextId.'/'.$k1.'/'.$k2.'/'.$k4.'/'.date('d/m/Y');
    };

    $uniqueRegister = function(string $reg) use ($db){
        $try=0; $base=$reg;
        while ($try < 10) {
            if ((int)$db->table('ujian_praktek')->where('register',$reg)->countAllResults() === 0) return $reg;
            $try++; $reg = $base.'#'.$try;
        }
        return $reg;
    };

    /* ================== PROSES ================== */

    $errors = [];
    $okSoal = 0; $failSoal = 0;
    $okAsp  = 0; $failAsp  = 0;
    $reg2id = []; // map register -> id soal (agar ASPEK bisa ikut)

    // ------ SOAL ------
    $hdrRowS = $findHeaderRow($rowsSoal, ['register','skenario']);
    $hS      = $hdrMap($rowsSoal[$hdrRowS]);

    for ($i = $hdrRowS+1; $i <= count($rowsSoal); $i++) {
        $r = $rowsSoal[$i] ?? null; if (!$r) continue;

        $register = $getStr($hS,$r,['register','no_register','no reg','no.reg']);
        $t1       = $getInt($hS,$r,['t1_id','t1','kom_utama_id','kom_id']);
        $t2kel    = $getInt($hS,$r,['t2_kel_id','t2','kelompok_penyakit_id']);
        $sub2     = $getInt($hS,$r,['sub2_penyakit_id','sub2','penyakit_id']);
        $t3ranah  = $getInt($hS,$r,['t3_ranah_id','t3','ranah_id']);
        $t4bid    = $getInt($hS,$r,['t4_bidang_id','t4','bidang_id']);

        $tujuan   = $getStr($hS,$r,['tujuan','tujuan_pembelajaran']);
        $skenario = $getStr($hS,$r,['skenario','narasi','scenario','skenario_soal','deskripsi','deskripsi_skenario','skenario(narasi)','skenario narasi']);
        $tugas_k  = $getStr($hS,$r,['tugas_k','tugas_peserta']);
        $tugas_p  = $getStr($hS,$r,['tugas_p','tugas_penguji']);
        $intruksi = $getStr($hS,$r,['intruksi','instruksi']);
        $peralatan= $getStr($hS,$r,['peralatan','alat']);
        $dep      = $getInt($hS,$r,['departemen_id','departemen']);
        $blok     = $getInt($hS,$r,['blok_id','blok']);
        $refs     = $getStr($hS,$r,['referensi','referensi_soal']);
        $statusRw = strtolower($getStr($hS,$r,['status']));
        $status   = is_numeric($statusRw) ? (int)$statusRw : (['draft'=>0,'review'=>1,'publish'=>2,'reject'=>3][$statusRw] ?? 0);

        // skip baris kosong total
        if ($register==='' && $skenario==='' && $tujuan==='') continue;

        // register: isi dari excel; kalau kosong → generate; pastikan unik
        if ($register === '') {
            $register = $buildRegister($t1, $sub2, $t4bid);
        }
        $register = $uniqueRegister($register);

        // validasi minimal
        $err = [];
        if ($skenario === '') $err[] = 'skenario wajib';
        if ($err){ $errors[]="SOAL Baris $i: ".implode('; ', $err); $failSoal++; continue; }

        $data = [
            'register'   => $register,
            't1'         => $t1 ?: null,
            't2'         => $t2kel ?: null,
            'sub2'       => $sub2 ?: null,
            't3'         => $t3ranah ?: null,
            't4'         => $t4bid ?: null,
            'tujuan'     => $tujuan,
            'skenario'   => $skenario,
            'tugas_k'    => $tugas_k,
            'tugas_p'    => $tugas_p,
            'intruksi'   => $intruksi,
            'peralatan'  => $peralatan,
            'departemen' => $dep ?: null,
            'blok'       => $blok ?: null,
            'referensi'  => $refs,
            'file'       => null,
            'insert_by'  => $uid ?: null,
            'status'     => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $ok = $db->table('ujian_praktek')->insert($data);
            if ($ok === false) {
                $e=$db->error(); $msg=trim(($e['code']??'').' '.($e['message']??'')) ?: 'insert gagal (unknown DB error)';
                $errors[] = "SOAL Baris $i: $msg"; $failSoal++; continue;
            }
            $newId = (int)$db->insertID();
            $reg2id[$register] = $newId;
            $okSoal++;
        } catch (\Throwable $ex) {
            $errors[] = "SOAL Baris $i: ".$ex->getMessage(); $failSoal++;
        }
    }

    // ------ ASPEK ------
    if ($rowsAsp && count($rowsAsp) >= 2) {
        $hdrRowA = $findHeaderRow($rowsAsp, ['aspek','register']);
        $hA      = $hdrMap($rowsAsp[$hdrRowA]);

        for ($i = $hdrRowA+1; $i <= count($rowsAsp); $i++) {
            $r = $rowsAsp[$i] ?? null; if (!$r) continue;

            $reg   = $getStr($hA,$r,['soal_register','register','no_register']);
            $aspek = $getStr($hA,$r,['aspek']);
            $ket   = $getStr($hA,$r,['keterangan','deskripsi']);
            $t1a   = $getInt($hA,$r,['t1_id','t1']);                // kom_utama
            $t2a   = $getInt($hA,$r,['t2_penyakit_id','t2']);       // penyakit
            $t3a   = $getInt($hA,$r,['t3_bidang_id','t3']);         // bid_ilmu

            if ($reg==='' && $aspek==='') continue;

            $err = [];
            $soalId = 0;
            if ($reg !== '') {
                $soalId = (int)($reg2id[$reg] ?? 0);
                if (!$soalId) {
                    $row = $db->table('ujian_praktek')->select('id')->where('register',$reg)->get()->getRowArray();
                    $soalId = (int)($row['id'] ?? 0);
                }
            }
            if ($soalId <= 0) $err[] = 'soal_register tidak ditemukan';
            if ($aspek  === '') $err[] = 'aspek wajib';

            if ($err){ $errors[]="ASPEK Baris $i: ".implode('; ', $err); $failAsp++; continue; }

            $ins = [
                'soal_id'    => $soalId,
                't1'         => $t1a ?: null,
                't2'         => $t2a ?: null,
                't3'         => $t3a ?: null,
                'aspek'      => $aspek,
                'keterangan' => $ket,
                'file'       => null,
                'insert_by'  => $uid ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            try {
                $ok = $db->table('aspek')->insert($ins);
                if ($ok === false) {
                    $e=$db->error(); $msg=trim(($e['code']??'').' '.($e['message']??'')) ?: 'insert gagal (unknown DB error)';
                    $errors[] = "ASPEK Baris $i: $msg"; $failAsp++; continue;
                }
                $okAsp++;
            } catch (\Throwable $ex) {
                $errors[] = "ASPEK Baris $i: ".$ex->getMessage(); $failAsp++;
            }
        }
    }

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'         => 'ok',
            'inserted_soal'  => $okSoal,
            'failed_soal'    => $failSoal,
            'inserted_aspek' => $okAsp,
            'failed_aspek'   => $failAsp,
            'errors'         => $errors,
            'csrf_token'     => csrf_hash(),
        ]);
}

/* ================== HELPER DATA UNTUK EXPORT ================== */
/* ================== HELPER DATA UNTUK EXPORT (dengan filter tanggal) ================== */
private function fetchSoalForExport(): array
{
    $b = $this->db->table('ujian_praktek u')
        ->select('u.id,u.register,u.tujuan,u.skenario,u.created_at');

    // --- Filter created_at: default dari HARI INI 00:00:00 sampai seterusnya ---
    // Bisa override via query: ?from=2025-09-16&to=2025-09-30
    $from = trim((string)$this->request->getGet('from')); // YYYY-MM-DD
    $to   = trim((string)$this->request->getGet('to'));   // YYYY-MM-DD

    if ($from === '') $from = date('Y-m-d'); // default: hari ini
    $b->where('u.created_at >=', $from.' 00:00:00');
    if ($to !== '')  $b->where('u.created_at <=', $to.' 23:59:59');

    // --- Filter lain (sama seperti list) ---
    if ('' !== ($q = trim((string)$this->request->getGet('q')))) {
        $b->groupStart()->like('u.register',$q)->orLike('u.skenario',$q)->groupEnd();
    }
    foreach (['t1','t2','sub2','t3','t4','departemen','blok'] as $f) {
        $v = $this->request->getGet($f);
        if ($v !== null && $v!=='') $b->where('u.'.$f, $v);
    }
    if ('' !== ($st = (string)$this->request->getGet('status'))) {
        $map=['draft'=>0,'review'=>1,'publish'=>2,'reject'=>3];
        $b->where('u.status', ctype_digit($st)?(int)$st:($map[strtolower($st)]??0));
    }

    return $b->orderBy('u.created_at','ASC')->get()->getResultArray();
}

/**
 * Rapikan HTML summernote -> XHTML aman utk PhpWord::Html::addHtml
 */
private function xhtmlForWord(?string $html): string
{
    $html = (string)$html;
    if ($html==='') return '';

    // normalisasi baris & self-closing
    $html = preg_replace("/\r\n|\r|\n/u", "\n", $html);
    $html = str_replace('&nbsp;', '&#160;', $html);                // non-breaking space
    $html = preg_replace('~<br(\s*)/?>~i', '<br/>', $html);        // <br/> konsisten
    $html = preg_replace('~<(hr|img|input)([^>]*)>~i', '<$1$2 />', $html);

    // bungkus supaya DOMDocument mau “merapikan”
    $wrapper = '<!DOCTYPE html><html><body>'.$html.'</body></html>';

    libxml_use_internal_errors(true);
    $dom = new \DOMDocument('1.0','UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8"?>'.$wrapper,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $body = $dom->getElementsByTagName('body')->item(0);
    $xhtml = $dom->saveXML($body) ?: '';
    libxml_clear_errors();

    // buang tag body agar bisa disisipkan ke dokumen Word
    $xhtml = preg_replace('~^<body>|</body>$~i', '', $xhtml);
    $xhtml = str_replace('&nbsp;', '&#160;', $xhtml);

    return trim($xhtml);
}

/** Tambahkan HTML ke Section/Cell dengan fallback teks polos */
private function addHtmlSafe($container, string $rawHtml): void
{
    $x = $this->xhtmlForWord($rawHtml);
    if ($x==='') { $container->addText('-'); return; }
    $full = '<html><body>'.$x.'</body></html>'; // kirim sebagai dokumen HTML utuh
    try {
        Html::addHtml($container, $full, true, false); // fullHTML = true
    } catch (\Throwable $e) {
        $container->addText(strip_tags($rawHtml));
    }
}

/** Gambar tabel ASPEK (Daftar CHECKLIST) ke section */
private function renderAspekTable($section, array $aspeks): void
{
    if (!$aspeks) return;

    $section->addTextBreak(1);
    $section->addText('Daftar CHECKLIST', ['bold'=>true,'italic'=>true]);

    $tblStyle = ['borderSize'=>6, 'borderColor'=>'7F7F7F', 'cellMargin'=>80];
    $hdrCell  = ['bgColor'=>'DDD9C3'];
    $pC = ['alignment'=>'center','spaceAfter'=>0];
    $pL = ['alignment'=>'left','spaceAfter'=>0];

    $wNo=900; $wAsp=6000; $wSk=900; $wKet=3800;

    $t = $section->addTable($tblStyle);
    // header baris 1
    $r1=$t->addRow();
    $r1->addCell($wNo,$hdrCell)->addText('No',['bold'=>true],$pC);
    $r1->addCell($wAsp,$hdrCell)->addText('Aspek Penilaian',['bold'=>true],$pC);
    $c = $r1->addCell($wSk*3,$hdrCell); $c->getStyle()->setGridSpan(3); $c->addText('Skor',['bold'=>true],$pC);
    $r1->addCell($wKet,$hdrCell)->addText('Keterangan',['bold'=>true],$pC);
    // header baris 2
    $r2=$t->addRow();
    $r2->addCell($wNo,$hdrCell); $r2->addCell($wAsp,$hdrCell);
    $r2->addCell($wSk,$hdrCell)->addText('0',['bold'=>true],$pC);
    $r2->addCell($wSk,$hdrCell)->addText('1',['bold'=>true],$pC);
    $r2->addCell($wSk,$hdrCell)->addText('2',['bold'=>true],$pC);
    $r2->addCell($wKet,$hdrCell);

    $no=1;
    foreach ($aspeks as $a) {
        $r=$t->addRow();
        $r->addCell($wNo)->addText((string)$no++,[],$pC);

        $cellAsp=$r->addCell($wAsp);
        $this->addHtmlSafe($cellAsp, (string)($a['aspek'] ?? ''));

        // kolom skor kosong
        $r->addCell($wSk)->addText('',[],$pC);
        $r->addCell($wSk)->addText('',[],$pC);
        $r->addCell($wSk)->addText('',[],$pC);

        $cellKet=$r->addCell($wKet);
        $this->addHtmlSafe($cellKet, (string)($a['keterangan'] ?? ''));
    }
}

/* ================== EXPORT: SATU FILE DOCX (semua soal) ================== */
public function exportAllDocx(): \CodeIgniter\HTTP\ResponseInterface
{
    $rows = $this->fetchSoalForExport();
    if (!$rows) return $this->response->setStatusCode(404)->setBody('Tidak ada data.');

    $pw = new PhpWord();
    $pw->setDefaultFontName('Times New Roman');
    $pw->setDefaultFontSize(11);

    $sec = $pw->addSection([
        'marginTop'=>900,'marginRight'=>900,'marginBottom'=>900,'marginLeft'=>900
    ]);

    $label = ['bold'=>true,'size'=>12];

    $i=0;
    foreach ($rows as $r) {
        if ($i++>0) $sec->addPageBreak();

        // judul
        $sec->addText('No. Bank Soal : '.($r['register'] ?: '-'), ['bold'=>true,'size'=>12]);
        $sec->addTextBreak(1);

        // Tujuan
        $sec->addText('Tujuan', $label);
        $this->addHtmlSafe($sec, (string)($r['tujuan'] ?? ''));

        // Skenario (kalau mau ditampilkan)
        $sec->addTextBreak(1);
        $sec->addText('Skenario', $label);
        $this->addHtmlSafe($sec, (string)($r['skenario'] ?? ''));

        // ASPEK
        $aspeks = $this->db->table('aspek')
            ->select('id,aspek,keterangan')
            ->where('soal_id',(int)$r['id'])
            ->orderBy('id','ASC')->get()->getResultArray();

        $this->renderAspekTable($sec, $aspeks);
    }

$file = WRITEPATH.'cache/soal_praktek_'.date('Ymd_His').'.docx';
WordIO::createWriter($pw, 'Word2007')->save($file);
return $this->response->download($file, null)->setFileName(basename($file));

}

/* ============ EXPORT: ZIP (banyak DOCX, 1 soal = 1 file) ============ */
public function exportZipDocx(): \CodeIgniter\HTTP\ResponseInterface
{
    $rows = $this->fetchSoalForExport();
    if (!$rows) return $this->response->setStatusCode(404)->setBody('Tidak ada data.');

    $zipPath = WRITEPATH.'cache/soal_praktek_'.date('Ymd_His').'.zip';
    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE|\ZipArchive::OVERWRITE)!==true) {
        return $this->response->setStatusCode(500)->setBody('Tidak bisa membuat ZIP');
    }

    $tempDocs = [];

    foreach ($rows as $r) {
        // ambil aspek utk soal ini
        $aspeks = $this->db->table('aspek')
            ->select('id,aspek,keterangan')
            ->where('soal_id',(int)$r['id'])
            ->orderBy('id','ASC')->get()->getResultArray();

        // buat 1 DOCX
        $pw = new PhpWord();
        $pw->setDefaultFontName('Times New Roman');
        $pw->setDefaultFontSize(11);
        $sec = $pw->addSection(['marginTop'=>900,'marginRight'=>900,'marginBottom'=>900,'marginLeft'=>900]);

        $label=['bold'=>true,'size'=>12];
        $sec->addText('No. Bank Soal : '.($r['register'] ?: '-'), ['bold'=>true,'size'=>12]);
        $sec->addTextBreak(1);

        $sec->addText('Tujuan', $label);
        $this->addHtmlSafe($sec, (string)($r['tujuan'] ?? ''));

        $sec->addTextBreak(1);
        $sec->addText('Skenario', $label);
        $this->addHtmlSafe($sec, (string)($r['skenario'] ?? ''));

        $this->renderAspekTable($sec, $aspeks);

        // simpan & masukkan ke zip
        $safe = preg_replace('~[^A-Za-z0-9\-_]+~','_', $r['register'] ?: ('soal_'.$r['id']));
      $docPath = WRITEPATH.'cache/'.$safe.'.docx';
WordIO::createWriter($pw, 'Word2007')->save($docPath);


        $tempDocs[] = $docPath;
        $zip->addFile($docPath, basename($docPath));
    }

    $zip->close();

    // download + bersihkan file sementara
    register_shutdown_function(function() use ($tempDocs, $zipPath){
        foreach ($tempDocs as $f) { @is_file($f) && @unlink($f); }
        @is_file($zipPath) && @unlink($zipPath);
    });

    return $this->response->download($zipPath, null)->setFileName(basename($zipPath));
}

 
    
}
