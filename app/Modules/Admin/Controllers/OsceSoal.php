<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Admin\Models\OsceSoalModel;
use Modules\Auth\Libraries\Auth;
use Dompdf\Dompdf;
use Dompdf\Options;

class OsceSoal extends BaseController
{
    protected $db;
    protected $m;

    public function __construct()
    {
        $this->db = db_connect();
        $this->m  = new OsceSoalModel();
    }

 public function index()
{
    $data = $this->buildQuery();

    // kalau hanya minta frag list, kirim partial saja (tanpa layout)
    if ($this->request->getGet('frag') === 'list') {
        return view('\Modules\Admin\Views\osce_soal\partials\osce_soal_table', $data);
    }

    $data['menuActive'] = 'osce-soal';
    return view('\Modules\Admin\Views\osce_soal\osce_soal_list', $data);
}


    public function table()
    {
        $data = $this->buildQuery();
        return view('\Modules\Admin\Views\osce_soal\partials\osce_soal_table', $data);
    }

    private function buildQuery(): array
    {
        $page = max(1, (int)($this->request->getGet('page') ?: 1));
        $per  = max(5, (int)($this->request->getGet('per')  ?: 10));
        $q    = trim((string)$this->request->getGet('q'));

        $b = $this->db->table('osce_soal s')
            ->select("s.*, o.kode AS osce_kode, up.register AS soal_register")
            ->join('osce o', 'o.id = s.osce_id', 'left')
            ->join('ujian_praktek up', 'up.id = s.soal_id', 'left');

        if ($q !== '') {
            $b->groupStart()
              ->like('s.nama_pengawas', $q)
              ->orLike('s.nama_station', $q)
              ->orLike('s.kode', $q)
              ->orLike('o.kode', $q)
              ->orLike('up.register', $q)
              ->groupEnd();
        }
        if ($oid = (int)$this->request->getGet('osce_id')) $b->where('s.osce_id', $oid);
        if ($sid = (int)$this->request->getGet('soal_id')) $b->where('s.soal_id', $sid);

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->orderBy('s.created_at','DESC')->limit($per, ($page-1)*$per)->get()->getResultArray();

        return compact('rows','page','per','total');
    }

    // ---------- CRUD JSON ----------

    public function get($id=null)
{
    $id = (int)$id;
    $row = $this->db->table('osce_soal s')
        ->select('s.*, o.kode AS osce_kode, up.register AS soal_register')
        ->join('osce o', 'o.id = s.osce_id', 'left')
        ->join('ujian_praktek up', 'up.id = s.soal_id', 'left')
        ->where('s.id', $id)
        ->get()->getRowArray();

    if (!$row) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()
        ]);
    }

    // Konversi waktu (HH:MM:SS) ke menit untuk form edit
    if (!empty($row['waktu'])) {
        $parts = explode(':', $row['waktu']);
        $row['waktu_menit'] = ((int)$parts[0] * 60) + (int)$parts[1];
    } else {
        $row['waktu_menit'] = 0;
    }

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
}

    public function create()
    {
        if (!$this->request->is('post')) return $this->fail405();
        $uid = (int)(Auth::user()['uid'] ?? Auth::user()['id'] ?? 0);

        $rules = [
            'osce_id'       => 'required',
            'soal_id'       => 'required',
            'nama_pengawas' => 'required|min_length[3]',
            'nama_station'  => 'required|min_length[2]',
            'kode'          => 'required|min_length[2]',
            'waktu'         => 'required',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=> implode("\n",$this->validator->getErrors()), 'csrf_token'=>csrf_hash()
            ]);
        }

        $nip  = trim((string)$this->request->getPost('nip_pengawas'));
        $nam  = trim((string)$this->request->getPost('nama_pengawas'));
        $mnt  = (int)$this->request->getPost('waktu');
        $time = sprintf('00:%02d:00', $mnt);

        $data = [
          'osce_id'       => (int)$this->request->getPost('osce_id'),
          'soal_id'       => (int)$this->request->getPost('soal_id'),
          'nip_pengawas'  => $nip ?: null,
          'nama_pengawas' => $nam ?: null,
          'nama_station'  => trim((string)$this->request->getPost('nama_station')),
          'kode'          => trim((string)$this->request->getPost('kode')),
          'waktu'         => $time,
          'created_by'    => $uid,
        ];

        $this->m->insert($data);

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON([
            'status'=>'ok','id'=>$this->m->getInsertID(),'csrf_token'=>csrf_hash()
        ]);
    }

    public function update($id=null)
    {
        if (!$this->request->is('post')) return $this->fail405();
        $id = (int)$id; if ($id<=0) return $this->fail422('ID tidak valid');

        $rules = [
            'osce_id'       => 'required',
            'soal_id'       => 'required',
            'nama_pengawas' => 'required|min_length[3]',
            'nama_station'  => 'required|min_length[2]',
            'kode'          => 'required|min_length[2]',
            'waktu'         => 'required',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=> implode("\n",$this->validator->getErrors()), 'csrf_token'=>csrf_hash()
            ]);
        }

        $row = $this->m->find($id);
        if (!$row) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()]);

        $nip  = trim((string)$this->request->getPost('nip_pengawas'));
        $nam  = trim((string)$this->request->getPost('nama_pengawas'));
        $mnt  = (int)$this->request->getPost('waktu');
        $time = sprintf('00:%02d:00', $mnt);

        $data = [
          'osce_id'       => (int)$this->request->getPost('osce_id'),
          'soal_id'       => (int)$this->request->getPost('soal_id'),
          'nip_pengawas'  => $nip ?: null,
          'nama_pengawas' => $nam ?: null,
          'nama_station'  => trim((string)$this->request->getPost('nama_station')),
          'kode'          => trim((string)$this->request->getPost('kode')),
          'waktu'         => $time,
        ];

        if ($this->m->update($id, $data)) {
            return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON([
                'status'=>'ok','id'=>$id,'csrf_token'=>csrf_hash()
            ]);
        }
        
        return $this->fail422('Gagal update data');
    }

    public function delete($id=null)
    {
        if (!$this->request->is('post')) return $this->fail405();
        $id = (int)$id; if ($id<=0) return $this->fail422('ID tidak valid');

        $this->m->delete($id);

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }

    // ---------- Select2 options ----------

 public function optionsOsce()
{
    $q = trim((string)$this->request->getGet('q'));
    $bd = $this->db->table('osce')->select('id, kode, nama_ujian, tanggal');
    if ($q!=='') $bd->groupStart()->like('kode',$q)->orLike('nama_ujian',$q)->groupEnd();
    $bd->orderBy('tanggal','DESC')->limit(20);
    $rows = $bd->get()->getResultArray();

    $results = array_map(function($r){
        $tgl = isset($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '';
        return [
            'id'   => $r['id'],
            'text' => "{$r['kode']} – {$r['nama_ujian']} ({$tgl})",
            'kode' => $r['kode'],   // << penting untuk autofill
        ];
    }, $rows);

    return $this->response->setJSON(['results'=>$results]);
}

    public function optionsSoal()
    {
        $q = trim((string)$this->request->getGet('q'));
        $bd = $this->db->table('ujian_praktek')->select('id, register');
        if ($q!=='') $bd->like('register',$q);
        $bd->orderBy('created_at','DESC')->limit(20);
        $rows = $bd->get()->getResultArray();

        $results = array_map(fn($r)=> ['id'=>$r['id'], 'text'=> "{$r['id']} – {$r['register']}"], $rows);
        return $this->response->setJSON(['results'=>$results]);
    }
public function optionsPengawas()
{
    $q = trim((string)$this->request->getGet('q'));

    // Ganti 'pengawas' dengan tabel master pengawas kamu.
    // Asumsi kolom: nip, nama
    $bd = $this->db->table('dosen')
        ->select('nip, nama');

    if ($q !== '') {
        $bd->groupStart()
           ->like('nip', $q)
           ->orLike('nama', $q)
           ->groupEnd();
    }

    $rows = $bd->orderBy('nama','ASC')->limit(20)->get()->getResultArray();

    // Return bentuk yang mudah untuk builder di JS
    $results = array_map(fn($r)=> ['nip'=>$r['nip'], 'nama'=>$r['nama']], $rows);

    return $this->response->setJSON(['results'=>$results]);
}
public function detail($id)
    {
       

        // Detail station (osce_soal) + info OSCE
        $station = $this->db->table('osce_soal s')
            ->select('s.*, o.id as osce_id, o.kode as osce_kode, s.nama_station as osce_nama, o.tanggal as osce_tanggal')
            ->join('osce o', 'o.id = s.osce_id', 'left')
            ->where('s.id', (int)$id)
            ->get()->getRowArray();

        if (!$station) {
            throw PageNotFoundException::forPageNotFound('Station tidak ditemukan');
        }

        // Daftar mahasiswa terdaftar untuk kode OSCE tsb
        // join sesuai permintaan: osce_soal.osce_id = osce.id, osce.kode = admin_cbt.kode, admin_cbt.id_mahasiswa = mahasiswa.id
        $mhs = $this->db->table('admin_cbt ac')
            ->select('m.id, m.nim, m.nama, m.kelas, ac.id as reg_id')
            ->join('mahasiswa m', 'm.id = ac.id_mahasiswa', 'left')
            ->where('ac.kode', $station['osce_kode'])
            ->orderBy('m.nama', 'asc')
            ->get()->getResultArray();

        return view('\Modules\Admin\Views\osce_soal\detail', [
            'station' => $station,
            'mhs'     => $mhs,
        ]);
    }

    public function historyMahasiswa( $mahasiswaId)
{
    
    // Ambil 1 record TERBARU untuk mahasiswa & station ini
    $mhs = $this->db->table('mahasiswa')
        ->select('id, nim, nama, kelas')
        ->where('id', (int)$mahasiswaId)
        ->get()->getRowArray();

    // Ambil semua history mahasiswa tsb dari SEMUA station
    // Join sesuai requirement: osce_soal.soal_id = jawaban_osce.soal_id
$rows = $this->db->table('jawaban_osce jo')
  ->select('jo.id, jo.osce_id, jo.soal_id, jo.kode_penguji, jo.mahasiswa_id,
            jo.global_skor, jo.gps, jo.waktu, jo.created_at, jo.updated_at,
            s.id AS station_id, s.nama_station, s.kode AS station_kode')
  ->select("
    CASE CAST(jo.gps AS UNSIGNED)
      WHEN 0 THEN 'Tidak Lulus'
      WHEN 1 THEN 'Borderline'
      WHEN 2 THEN 'Lulus'
      ELSE '-'
    END AS gps_text
  ", false)
  ->join('osce_soal s', 's.osce_id = jo.osce_id AND s.soal_id = jo.soal_id', 'left')
  ->where('jo.mahasiswa_id', (int)$mahasiswaId)
  ->orderBy('jo.created_at', 'DESC')
  ->get()
  ->getResultArray();




    // Tambahkan status per row
     foreach ($rows as &$r) {
        // format tanggal ujian dari created_at
       $ts = $r['created_at'] ?? '';
        if ($ts) {
            $unix = strtotime(str_replace('/', '-', $ts));
            // contoh: "Jumat, 05 - September - 2025 14:23"
            $r['tanggal_jam_ujian'] = tgl_id($ts, true).' '.date('H:i', $unix);
        } else {
            $r['tanggal_jam_ujian'] = '-';
        }
        // status (berdasar ada/tidaknya nilai)
        $r['status'] = is_null($r['global_skor']) ? 'Belum Ujian' : 'Sudah Ujian';
    }
    return $this->response->setJSON([
        'status'     => 'ok',
        'mahasiswa'  => $mhs,
        'list'       => $rows,
    ]);
}
public function historyMahasiswaStation($stationId)
{
    $stationId = (int) $stationId;

    // ---------- INFO STATION ----------
    $station = $this->db->table('osce_soal s')
        ->select('
            s.id,
            s.nama_station,
            s.kode           AS station_kode,
            o.id             AS osce_id,
            o.kode           AS osce_kode,
            o.nama_ujian,
            o.tanggal        AS osce_tanggal
        ')
        ->join('osce o', 'o.id = s.osce_id', 'left')
        ->where('s.id', $stationId)
        ->get()->getRowArray();

    if (! $station) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'  => 'error',
            'message' => 'Soal Belum ditambahkan',
        ]);
    }

    // ---------- HISTORY SEMUA MAHASISWA DI STATION INI ----------
    $rows = $this->db->table('jawaban_osce jo')
        ->select('
            jo.id,
            jo.osce_id,
            jo.soal_id,
            jo.kode_penguji,
            jo.mahasiswa_id,
            jo.global_skor,
            jo.gps,
            jo.waktu,
            jo.created_at,
            jo.updated_at,
            s.id          AS station_id,
            s.nama_station,
            s.kode        AS station_kode,
            m.nim,
            m.nama,
            m.kelas
        ')
        ->select("
            CASE CAST(jo.gps AS UNSIGNED)
              WHEN 0 THEN 'Tidak Lulus'
              WHEN 1 THEN 'Borderline'
              WHEN 2 THEN 'Lulus'
              ELSE '-'
            END AS gps_text
        ", false)
        ->join('osce_soal s', 's.soal_id = jo.soal_id', 'left')
        ->join('mahasiswa m', 'm.id = jo.mahasiswa_id', 'left')
        ->where('s.id', $stationId)              // ⬅️ filter by STATION
        ->orderBy('jo.created_at', 'DESC')
        ->get()->getResultArray();

    // Tambahkan status & format tanggal
    foreach ($rows as &$r) {
        $ts = $r['created_at'] ?? '';
        if ($ts) {
            $unix = strtotime(str_replace('/', '-', $ts));
            $r['tanggal_jam_ujian'] = tgl_id($ts, true).' '.date('H:i', $unix);
        } else {
            $r['tanggal_jam_ujian'] = '-';
        }

        $r['status'] = is_null($r['global_skor']) ? 'Belum Ujian' : 'Sudah Ujian';
    }
    unset($r);

    return $this->response->setJSON([
        'status'  => 'ok',
        'station' => $station,   // info station (bukan mahasiswa lagi)
        'list'    => $rows,      // history semua mahasiswa di station tsb
    ]);
}

public function deleteMultiple()
{
    $ids = $this->request->getPost('ids') ?: [];

    if (empty($ids)) {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON([
                'status'  => 'error',
                'message' => 'Tidak ada data yang dipilih.'
            ]);
    }

    $model = new \Modules\Admin\Models\OsceSoalModel();
    $model->whereIn('id', $ids)->delete();

    return $this->response
        ->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON([
            'status'  => 'ok',
            'message' => 'Data terpilih dihapus.'
        ]);
}
public function historyMahasiswaPdf($mahasiswaId)
{
    helper(['date','text']);

    $db = db_connect();

    // --- data mahasiswa ---
    $mhs = $db->table('mahasiswa')
        ->select('id, nim, nama, kelas')
        ->where('id', (int)$mahasiswaId)
        ->get()->getRowArray();

    if (!$mhs) {
        return $this->response
            ->setStatusCode(404)
            ->setBody('Mahasiswa tidak ditemukan');
    }

    // --- data history (sama seperti historyMahasiswa) ---
    $rows = $db->table('jawaban_osce jo')
        ->select('jo.id, jo.osce_id, jo.soal_id, jo.kode_penguji,s.nama_pengawas, jo.mahasiswa_id,
                  jo.global_skor, jo.gps, jo.waktu, jo.created_at, jo.updated_at,
                  s.id AS station_id, s.nama_station, s.kode AS station_kode')
        ->select("
            CASE CAST(jo.gps AS UNSIGNED)
              WHEN 0 THEN 'Tidak Lulus'
              WHEN 1 THEN 'Borderline'
              WHEN 2 THEN 'Lulus'
              ELSE '-'
            END AS gps_text
        ", false)
        ->join('osce_soal s', 's.osce_id = jo.osce_id AND s.soal_id = jo.soal_id', 'left')
        ->where('jo.mahasiswa_id', (int)$mahasiswaId)
        ->orderBy('jo.created_at', 'DESC')
        ->get()
        ->getResultArray();

    foreach ($rows as &$r) {
        $ts = $r['created_at'] ?? '';
        if ($ts) {
            $unix = strtotime(str_replace('/', '-', $ts));
            $r['tanggal_ujian'] = date('d/m/Y', $unix);
        } else {
            $r['tanggal_ujian'] = '-';
        }

        $r['status'] = is_null($r['global_skor']) ? 'Belum Ujian' : 'Sudah Ujian';
    }
    unset($r);

    // --- template KOP ---
    $kop = function () {
        $logoPath = FCPATH . 'assets/img/logo_unhas.png';
        $logoData = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return '
          <table width="100%" style="border-bottom:3px solid #000;margin-bottom:6px">
            <tr>
              <td width="80">
                <img src="'.$logoData.'" style="height:60px">
              </td>
              <td style="text-align:center;font-weight:bold;font-size:11pt">
                KEMENTERIAN RISET, TEKNOLOGI, DAN PENDIDIKAN TINGGI<br>
                UNIVERSITAS HASANUDDIN<br>
                FAKULTAS KEDOKTERAN GIGI<br>
                <div style="font-weight:normal;font-size:9pt">
                  Jl. Perintis Kemerdekaan KM. 10 Makassar 90245 Tlp: (0411) 586012 Web: dent.unhas.ac.id
                </div>
              </td>
            </tr>
          </table>
        ';
    };

    // --- header identitas mahasiswa ---
    $html  = $kop();
    $html .= '
      <div style="text-align:center;font-weight:bold;margin:8px 0 12px 0;font-size:12pt">
        FORM NILAI UJIAN OSCE
      </div>
      <table width="100%" cellspacing="2" cellpadding="2" style="font-size:11pt;margin-bottom:10px">
        <tr>
          <td style="width:18%">Nama</td>
          <td style="width:2%">:</td>
          <td>'.esc($mhs['nama']).'</td>
        </tr>
        <tr>
          <td>NIM</td>
          <td>:</td>
          <td>'.esc($mhs['nim']).'</td>
        </tr>
        <tr>
          <td>Ujian</td>
          <td>:</td>
          <td>OSCE</td>
        </tr>
      </table>
    ';

    // --- tabel nilai per station ---
    $html .= '
      <table width="100%" border="1" cellspacing="0" cellpadding="4"
             style="border-collapse:collapse;font-size:10pt">
        <thead>
          <tr style="background:#e9f2ff">
            <th style="width:35px;text-align:center">No</th>
            <th>Nama Station</th>
            <th style="width:120px">Penguji</th>
            <th style="width:70px;text-align:center">Nilai</th>
            <th style="width:80px;text-align:center">GPS</th>
            <th style="width:120px">Ket</th>
          </tr>
        </thead>
        <tbody>';

    if (!$rows) {
        $html .= '<tr><td colspan="6" style="text-align:center;padding:8px">Belum ada data ujian.</td></tr>';
    } else {
        $i = 1;
        foreach ($rows as $r) {
            $html .= '<tr>
              <td style="text-align:center">'.$i++.'</td>
              <td>'.esc($r['nama_station'] ?: '-').'</td>
              <td>'.esc($r['nama_pengawas'] ?: '-').'</td>
              <td style="text-align:center">'.(is_null($r['global_skor']) ? '-' : (float)$r['global_skor']).'</td>
              <td style="text-align:center">'.esc($r['gps_text']).'</td>
              <td>'.esc($r['status']).'</td>
            </tr>';
        }
    }

    $html .= '</tbody></table>';

    // --- RENDER PDF ---
    while (ob_get_level() > 0) { ob_end_clean(); }

    $opts = new Options();
    $opts->set('isRemoteEnabled', true);
    $opts->set('isHtml5ParserEnabled', true);
    $opts->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($opts);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml(
        '<style>
           body{font-family:DejaVu Sans, Arial, sans-serif;font-size:11pt}
         </style>'.$html
    );
    $dompdf->render();

    return $this->response
        ->setContentType('application/pdf')
        ->setHeader('Cache-Control','private, max-age=0, must-revalidate')
        ->setHeader('Pragma','public')
        ->setBody($dompdf->output());
}

    // ---------- helpers ----------
    private function fail405(){ return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]); }
    private function fail422($m){ return $this->response->setStatusCode(422)->setJSON(['status'=>'error','message'=>$m,'csrf_token'=>csrf_hash()]); }
}
