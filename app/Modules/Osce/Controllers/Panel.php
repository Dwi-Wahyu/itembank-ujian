<?php
namespace Modules\Osce\Controllers;
use App\Controllers\BaseController;
use Config\Database;                  // <-- import ini
use DateTimeZone;
use DateTime;
class Panel extends BaseController
{
    public function index()
    {
        $os = session('osce') ?: [];
        return view('\Modules\Osce\Views\panel_list', ['os'=>$os]);
    }

    /**
     * GET /osce/api/peserta
     * Params: page, per, q, sort
     * Data: dari admin_cbt where kode = osce_kode
     * Join: mahasiswa (id_mahasiswa=mahasiswa.id)
     * Nilai: jawaban_osce.global_skor (osce_id & soal_id & id_mahasiswa)
     */
  public function peserta()
{
    $os = session('osce') ?: [];
    $osceId  = (int)($os['osce_id'] ?? 0);
    $soalId  = (int)($os['soal_id'] ?? 0);
    $kode    = (string)($os['osce_kode'] ?? '');

    $page = max(1, (int)($this->request->getGet('page') ?? 1));
    $per  = max(5, min(100, (int)($this->request->getGet('per') ?? 5)));
    $q    = trim((string)$this->request->getGet('q'));
    $sort = (string)($this->request->getGet('sort') ?? 'nama_asc');

    $b = $this->db->table('admin_cbt ac')
        ->select('ac.id, ac.id_mahasiswa, ac.kode, m.nim, m.nama')
        ->join('mahasiswa m', 'm.id = ac.id_mahasiswa', 'left')
        ->where('ac.kode', $kode);

    if ($q !== '') {
        $b->groupStart()->like('m.nim', $q)->orLike('m.nama', $q)->groupEnd();
    }

    // total pakai clone agar SELECT utama tidak berubah
    $total = (clone $b)->countAllResults();

    // sorting
    switch ($sort) {
        case 'nim_asc':  $b->orderBy('m.nim','ASC'); break;
        case 'nim_desc': $b->orderBy('m.nim','DESC'); break;
        case 'nama_desc':$b->orderBy('m.nama','DESC'); break;
        default:         $b->orderBy('m.nama','ASC');
    }

    $rows = $b->limit($per, ($page-1)*$per)->get()->getResultArray();

    // Ambil nilai + GPS sekali jalan lalu map ke baris
    if ($rows) {
        $ids = array_values(array_unique(array_map('intval', array_column($rows, 'id_mahasiswa'))));

        $jo = $this->db->table('jawaban_osce')
            ->select('mahasiswa_id, global_skor, gps')
            ->where('osce_id', $osceId)
            ->where('soal_id', $soalId)
            ->whereIn('mahasiswa_id', $ids)
            ->get()->getResultArray();

        $map = [];
        foreach ($jo as $j) {
            $mid = (int)$j['mahasiswa_id'];
            $map[$mid] = [
                'global_skor' => isset($j['global_skor']) ? (int)$j['global_skor'] : null,
                'gps'         => isset($j['gps']) ? (int)$j['gps'] : null,
            ];
        }

        foreach ($rows as &$r) {
            $mid = (int)$r['id_mahasiswa'];
            $r['global_skor'] = $map[$mid]['global_skor'] ?? null;
            $r['gps']         = $map[$mid]['gps'] ?? null;   // <-- kirim ke frontend
            $r['status']      = ($r['global_skor'] === null) ? 'belum' : 'sudah';
        }
        unset($r);
    }

    return $this->response->setJSON([
        'status'     => 'ok',
        'page'       => $page,
        'per'        => $per,
        'total'      => $total,
        'data'       => $rows,      // sekarang tiap item punya field `gps`
        'csrf_token' => csrf_hash(),
    ]);
}

public function info($mhsId)
{
    $os = session('osce') ?: [];
    $osceId = (int)($os['osce_id'] ?? 0);
    $soalId = (int)($os['soal_id'] ?? 0);

    $mhs = $this->db->table('mahasiswa')->select('id,nim,nama')->where('id',(int)$mhsId)->get()->getRowArray();
    if (!$mhs) {
        return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Mahasiswa tidak ditemukan','csrf_token'=>csrf_hash()]);
    }

    // ambil info soal praktek + label departemen/blok + tugas penguji & kandidat
    $info = $this->db->table('ujian_praktek up')
        ->select('up.departemen, up.blok, up.tugas_p, up.tugas_k,
                  d.nama AS dep_nama, b.nama AS blok_nama')
        ->join('departemen d', 'd.id = up.departemen', 'left')
        ->join('blok b',       'b.id = up.blok',       'left')
        ->where('up.id', $soalId)
        ->get()->getRowArray() ?: [];

    // rakit response
    $res = [
        'nama'       => (string)($mhs['nama'] ?? ''),
        'nim'        => (string)($mhs['nim'] ?? ''),
        'namaUjian'  => (string)($os['osce_nama'] ?? ''),
        'departemen' => $info['dep_nama']  ?? 'Semua Departemen',
        'blok'       => $info['blok_nama'] ?? 'Semua Blok Pada Departemen',
        'tanggal'    => (string)($os['osce_tanggal'] ?? ''),
        'waktu'      => self::minutesToHms((int)($os['waktu'] ?? 0)),
        'tugas_p'    => (string)($info['tugas_p'] ?? ''), // bisa HTML dari summernote
        'tugas_k'    => (string)($info['tugas_k'] ?? ''), // bisa HTML dari summernote
    ];

    return $this->response->setJSON(['status'=>'ok','data'=>$res,'csrf_token'=>csrf_hash()]);
}

private static function minutesToHms(int $m): string
{
    $h = intdiv($m, 60);
    $mi = $m % 60;
    return sprintf('%02d:%02d:00', $h, $mi);
}

    // Stub aksi: mulai ujian (bisa diisi log start dsb.)
    public function start($mhsId)
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan']);
        }
        // TODO: catat log mulai kalau dibutuhkan
        return $this->response->setJSON(['status'=>'ok','message'=>'Silakan arahkan mahasiswa untuk mulai ujian pada perangkatnya.','csrf_token'=>csrf_hash()]);
    }

    // Stub detail nilai
 public function detail($mhsId)
{
    $os     = session('osce') ?: [];
    $soalId = (int)($os['soal_id'] ?? 0);
    $osceId = (int)($os['osce_id'] ?? 0);
    $mid    = (int)$mhsId;

    // --- 1) identitas mahasiswa
    $mhs = $this->db->table('mahasiswa')
        ->select('id,nim,nama')
        ->where('id', $mid)->get()->getRowArray();

    if (!$mhs) {
        return redirect()->to(site_url('e-osce/panel'))->with('err','Mahasiswa tidak ditemukan');
    }

    // --- 2) meta soal: skenario, tugas_k, file (media)
    $meta = $this->db->table('ujian_praktek')
        ->select('skenario,tugas_k,file')
        ->where('id',$soalId)->get()->getRowArray() ?: ['skenario'=>'','tugas_k'=>'','file'=>null];

    // normalisasi media soal -> array URL
    $mediaSoal = $this->normalizeFilesFromSoal($meta['file'] ?? null, base_url('uploads/soal_praktek'));

    // --- 3) ambil hasil (jawaban_osce) + waktu tersimpan
   // --- 3) ambil hasil (jawaban_osce) + waktu tersimpan
$hasil = $this->db->table('jawaban_osce')
    ->select('id, global_skor, waktu, kode_penguji, gps') // <— tambah gps
    ->where('osce_id', $osceId)
    ->where('soal_id', $soalId)
    ->where('mahasiswa_id', $mid)
    ->get()->getRowArray();



$gpsInit = isset($hasil['gps']) && $hasil['gps'] !== '' ? (int)$hasil['gps'] : null;





    if (!$hasil) {
        // kalau tidak pernah ujian, arahkan balik
        return redirect()->to(site_url('e-osce/panel'))->with('err','Belum ada jawaban untuk mahasiswa ini');
    }

    $jawabanId  = (int)$hasil['id'];
    $savedTime  = (string)($hasil['waktu'] ?? '00:00:00'); // HH:MM:SS
    $totalSkor  = (int)($hasil['global_skor'] ?? 0);

    // --- 4) peta jawaban per-aspek
    $det = $this->db->table('jawaban_osce_aspek')
        ->select('aspek_id, jawaban')
        ->where('jawaban_osce_id', $jawabanId)
        ->get()->getResultArray();

    $ansMap = [];
    foreach ($det as $d) {
        $ansMap[(int)$d['aspek_id']] = (int)$d['jawaban'];
    }

    // --- 5) daftar aspek + opsi (seperti ujianPage), tandai selected dari $ansMap
    $raw = $this->db->table('aspek')
        ->select('id, aspek, keterangan')
        ->where('soal_id',$soalId)->orderBy('id','ASC')
        ->get()->getResultArray();

    $items = [];
    foreach ($raw as $i => $r) {
        $opts   = $this->parseOptionsFromKeterangan((string)$r['keterangan']);
        $legend = [];
        foreach ($opts as $o) $legend[] = "{$o['v']}: {$o['t']}";
        $items[] = [
            'id'     => (int)$r['id'],
            'no'     => $i+1,
            'teks'   => (string)$r['aspek'],
            'opsi'   => $opts,
            'legend' => implode('<br>', $legend),
            'ans'    => $ansMap[(int)$r['id']] ?? null, // <-- nilai terpilih
        ];
    }

    // --- 6) kirim ke view (pakai ujian_page dengan mode readOnly)
    $data = [
        'mhs'         => $mhs,
        'skenario'    => (string)$meta['skenario'],
        'tugas_k'     => (string)$meta['tugas_k'],
        'mediaSoal'   => $mediaSoal,
        'items'       => $items,
        'savedTime'   => $savedTime,      // "HH:MM:SS"
        'savedTotal'  => $totalSkor,
        'readOnly'    => true,            // <-- kunci: view non-interaktif
        'waktuMenit'  => 0,               // jangan jalankan timer count-down
        'nilaiInit'   => $ansMap, 
          'gpsInit'    => $gpsInit,         // untuk preselect di JS
        'csrf_name'   => csrf_token(),
        'csrf_tok'    => csrf_hash(),
    ];

    // gunakan view ujian_page dengan guarding readOnly, atau buat view khusus (ujian_detail)
    return view('\Modules\Osce\Views\ujian_page', $data);
}


    public function ujianData($mhsId)
{
    $os   = session('osce') ?: [];
    $sid  = (int)($os['soal_id'] ?? 0);
    $rid  = (int)$mhsId;

    // identitas mhs (opsional, kalau mau ditampilkan di header)
    $mhs = $this->db->table('mahasiswa')->select('id,nim,nama')->where('id',$rid)->get()->getRowArray();

    // kiri: skenario & tugas_k
    $meta = $this->db->table('ujian_praktek')->select('skenario,tugas_k')->where('id',$sid)->get()->getRowArray() ?: ['skenario'=>'','tugas_k'=>''];

    // kanan: daftar aspek
    $raw = $this->db->table('aspek')
            ->select('id, aspek, keterangan')
            ->where('soal_id',$sid)
            ->orderBy('id','ASC')
            ->get()->getResultArray();

    $aspek = [];
    foreach ($raw as $i => $r) {
        $opts = $this->parseOptionsFromKeterangan((string)$r['keterangan']); // [['v'=>0,'t'=>'...'],...]
        $legend = [];
        foreach ($opts as $o) { $legend[] = "{$o['v']}: {$o['t']}"; }
        $aspek[] = [
            'id'    => (int)$r['id'],
            'no'    => $i+1,
            'teks'  => (string)$r['aspek'],        // HTML
            'opsi'  => $opts,                      // untuk radio
            'legend'=> implode("<br>", $legend),   // untuk kotak "Ket:"
        ];
    }

    return $this->response->setJSON([
        'status'=>'ok',
        'data'=>[
            'mhs'      => ['id'=>$mhs['id']??$rid, 'nim'=>$mhs['nim']??'', 'nama'=>$mhs['nama']??''],
            'skenario' => (string)$meta['skenario'],
            'tugas_k'  => (string)$meta['tugas_k'],
            'waktuMenit'=> (int)($os['waktu'] ?? 0),
            'aspek'    => $aspek,
        ],
        'csrf_token'=> csrf_hash()
    ]);
}



// Modules/Osce/Controllers/Panel.php

public function ujianPage($mhsId)
{
    $os  = session('osce') ?: [];
    $sid = (int)($os['soal_id'] ?? 0);
    $mid = (int)$mhsId;

    // identitas mahasiswa
    $mhs = $this->db->table('mahasiswa')->select('id,nim,nama')->where('id',$mid)->get()->getRowArray();
    if (!$mhs) return redirect()->to(site_url('e-osce/panel'))->with('err','Mahasiswa tidak ditemukan');

    // meta soal (skenario, tugas_k, file)
    $meta = $this->db->table('ujian_praktek')
        ->select('skenario,tugas_k,file')
        ->where('id',$sid)->get()->getRowArray() ?: ['skenario'=>'','tugas_k'=>'','file'=>null];

    // Gambar dari ujian_praktek.file (json array atau single filename)
    // -> sesuaikan base path upload kamu
    $mediaSoal = $this->normalizeFilesFromSoal($meta['file'] ?? null, base_url('uploads/soal_praktek'));

    // aspek
    $raw = $this->db->table('aspek')
        ->select('id, aspek, keterangan')
        ->where('soal_id',$sid)->orderBy('id','ASC')->get()->getResultArray();

    $items = [];
    foreach ($raw as $i => $r) {
        $opts = $this->parseOptionsFromKeterangan((string)$r['keterangan']);

        $legend = [];
        foreach ($opts as $o) { $legend[] = "{$o['v']}: {$o['t']}"; }

        $items[] = [
            'id'     => (int)$r['id'],
            'no'     => $i+1,
            'teks'   => (string)$r['aspek'],
            'opsi'   => $opts,
            'legend' => implode('<br>', $legend),
            // JANGAN masukkan mediaSoal/waktu di sini
        ];
    }

    return view('\Modules\Osce\Views\ujian_page', [
        'mhs'       => $mhs,
        'skenario'  => (string)$meta['skenario'],
        'tugas_k'   => (string)$meta['tugas_k'],
        'items'     => $items,
        'mediaSoal' => $mediaSoal,                 // <— PENTING: kirim top-level
        'csrf_name' => csrf_token(),
        'csrf_tok'  => csrf_hash(),
        'os'=>$os
    ]);
}

/** Helper: ubah "file" (json/string) -> array URL gambar */
private function normalizeFilesFromSoal($val, string $baseUrl): array
{
    if (!$val) return [];
    $arr = json_decode((string)$val, true);
    if (!is_array($arr)) $arr = [trim((string)$val)];

    $out = [];
    foreach ($arr as $fn) {
        $fn = trim((string)$fn);
        if ($fn === '') continue;
        $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
        $out[] = rtrim($baseUrl, '/').'/'.$fn;
    }
    return $out;
}

private function minutesFrom($val): int
{
    if ($val === null || $val === '') return 0;
    if (is_numeric($val)) return (int)$val;
    if (is_string($val) && preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $val, $m)) {
        $h = (int)$m[1]; $i = (int)$m[2]; $s = isset($m[3]) ? (int)$m[3] : 0;
        return $h * 60 + $i + (int) round($s / 60); // 01:40:00 => 100
    }
    return 0;
}

/** keterangan:
 *  <p>0 : Tidak atau salah ...</p>
 *  <p>1 : Memverbalkan 1-3 tahapan dengan benar</p>
 *  <p>2 : Memverbalkan 4-7 tahapan dengan benar dan berurutan</p>
 *
 *  atau versi plain text:
 *  0 : Tidak atau salah ...
 *  1 : Memverbalkan 1-3 ...
 *  2 : Memverbalkan 4-7 ...
 *
 *  => menjadi:
 *  [
 *    ['v'=>0,'t'=>'Tidak atau salah ...'],
 *    ['v'=>1,'t'=>'Memverbalkan 1-3 tahapan dengan benar'],
 *    ['v'=>2,'t'=>'Memverbalkan 4-7 tahapan dengan benar dan berurutan'],
 *  ]
 */
private function parseOptionsFromKeterangan(string $html): array
{
    $out  = [];
    $html = trim($html);
    if ($html === '') {
        return $out;
    }

    // 1) decode entity: &lt;p&gt; -> <p>
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2) <br> jadi newline
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);

    // 3) ambil isi tiap <p>, kalau ada; kalau tidak, pakai string utuh
    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $m) && !empty($m[1])) {
        $chunks = $m[1];
    } else {
        $chunks = [$html];
    }

    foreach ($chunks as $chunk) {
        // buang tag HTML lain
        $text  = strip_tags($chunk);
        // normalisasi newline
        $text  = str_replace(["\r\n", "\r"], "\n", $text);
        // pecah per baris
        $lines = preg_split('/\n+/', $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // pola: angka di depan, lalu ":" atau "-", lalu teks
            if (preg_match('/^(\d+)\s*[:\-]\s*(.+)$/u', $line, $mm)) {
                $out[] = [
                    'v' => (int)$mm[1],
                    't' => trim($mm[2]),
                ];
            }
        }
    }

    return $out;
}

/** SIMPAN nilai ke jawaban_osce (total saja, contoh sederhana) */
public function ujianSubmit($mhsId)
{
    if (!$this->request->is('post')) {
        return $this->response->setStatusCode(405)
            ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }

    $os      = session('osce') ?: [];
    $osceId  = (int)($os['osce_id'] ?? 0);
    $soalId  = (int)($os['soal_id'] ?? 0);
    $mhsId   = (int)$mhsId;
    $now     = date('Y-m-d H:i:s');

    // 1) Nilai per-aspek
    $nilaiIn = (array)$this->request->getPost('nilai');
    $pairs   = [];
    $total   = 0;
    foreach ($nilaiIn as $aid => $sv) {
        $aid = (int)$aid; $sv = (int)$sv;
        if ($aid <= 0) continue;
        $pairs[$aid] = $sv;
        $total += $sv;
    }

    // 1b) NEW: gps (0/1/2)
    $gps = $this->request->getPost('gps');
    $gps = is_numeric($gps) ? (int)$gps : null;
    if ($gps !== null && !in_array($gps, [0,1,2], true)) $gps = null;

    // 2) Waktu
    $waktuStr = (string)$this->request->getPost('waktu');
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $waktuStr)) {
        $detik = (int)$this->request->getPost('durasi_detik');
        if ($detik < 0) $detik = 0;
        $h = floor($detik / 3600);
        $m = floor(($detik % 3600) / 60);
        $s = $detik % 60;
        $waktuStr = sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    // 3) Kode penguji dari osce.kode
    $kodePenguji = (string)($os['osce_kode'] ?? '');
    if ($kodePenguji === '' && $osceId > 0) {
        $q = $this->db->table('osce')->select('kode')->where('id', $osceId)->get();
        if ($q !== false) $kodePenguji = (string)($q->getRowArray()['kode'] ?? '');
    }
    if ($kodePenguji === '') $kodePenguji = null;

    $db = $this->db;
    $db->transStart();

    // 4) Upsert jawaban_osce (+ gps)
    $tbl = $db->table('jawaban_osce');
    $studentCol = 'mahasiswa_id';

    $res = $tbl->select('id')
               ->where('osce_id', $osceId)
               ->where('soal_id', $soalId)
               ->where($studentCol, $mhsId)
               ->get();

    $row = $res ? $res->getRowArray() : null;
    if ($row) {
        $jid = (int)$row['id'];
        $upd = [
            'global_skor'  => $total,
            'waktu'        => $waktuStr,
            'kode_penguji' => $kodePenguji,
            'updated_at'   => $now,
        ];
        if ($gps !== null) $upd['gps'] = $gps; // NEW
        $ok = $tbl->where('id', $jid)->update($upd);
    } else {
        $ins = [
            'osce_id'       => $osceId,
            'soal_id'       => $soalId,
            $studentCol     => $mhsId,
            'global_skor'   => $total,
            'waktu'         => $waktuStr,
            'kode_penguji'  => $kodePenguji,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];
        if ($gps !== null) $ins['gps'] = $gps; // NEW
        $ok  = $tbl->insert($ins);
        $jid = (int)$db->insertID();
    }

    if ($ok === false) {
        $db->transRollback();
        return $this->response->setStatusCode(500)->setJSON([
            'status'=>'error','message'=>'DB error saat upsert jawaban_osce','csrf_token'=>csrf_hash()
        ]);
    }

    // 5) Detail aspek seperti semula
    $db->table('jawaban_osce_aspek')->where('jawaban_osce_id', $jid)->delete();
    if (!empty($pairs)) {
        $rowsIns = [];
        foreach ($pairs as $aid => $sv) {
            $rowsIns[] = [
                'jawaban_osce_id' => $jid,
                'aspek_id'        => (int)$aid,
                'jawaban'         => (int)$sv,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }
        $db->table('jawaban_osce_aspek')->insertBatch($rowsIns);
    }

    $db->transComplete();
    if ($db->transStatus() === false) {
        return $this->response->setStatusCode(500)->setJSON([
            'status'=>'error','message'=>'Transaksi gagal','csrf_token'=>csrf_hash()
        ]);
    }

    return $this->response->setJSON([
        'status'=>'ok',
        'total' => $total,
        'waktu' => $waktuStr,
        'gps'   => $gps,           // kirim balik jika perlu
        'csrf_token'=> csrf_hash(),
    ]);
}




}
