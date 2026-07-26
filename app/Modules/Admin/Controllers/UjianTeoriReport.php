<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Dompdf\Dompdf;
use Dompdf\Options;

class UjianTeoriReport extends BaseController
{
    public function laporan(string $kode)
    {
        helper(['text','date']); // kalau perlu
        $db = db_connect();

        // ------------- DATA PESERTA (lembar 2) -------------
        $attempts = $db->table('ujian_attempt ua')
            ->select('ua.no_ujian, ua.id_mahasiswa, ua.id_paket, ua.benar, ua.salah, ua.kosong, ua.nilai,
                      m.nama AS nama_mhs')
            ->join('mahasiswa m','m.id = ua.id_mahasiswa','left')
            ->where('ua.kode', $kode)
            ->orderBy('ua.benar','DESC')->orderBy('ua.salah','ASC')
            ->get()->getResultArray();

        if (!$attempts) {
            return $this->response->setStatusCode(404)->setBody('Data ujian tidak ditemukan');
        }

        $idPaket   = (int) ($attempts[0]['id_paket'] ?? 0);
        $jumlahPeserta = count($attempts);

        // waktu & info header (fallback dari attempt)
        $w = $db->table('ujian_attempt')
            ->selectMin('start_at','mulai')->selectMax('finished_at','selesai')
            ->where('kode',$kode)->get()->getRowArray();
        $mulai   = $w['mulai'] ?? null;
        $selesai = $w['selesai'] ?? null;

        // jumlah soal (dari paket / dari jawaban distinct)
        $jumlahSoal = 0;
        if ($idPaket) {
            $jumlahSoal = (int)$db->table('ujian_teori')->where('id_paket',$idPaket)->countAllResults();
        }
        if ($jumlahSoal <= 0) {
            $jumlahSoal = (int)$db->table('jawaban_teori')->where('kode',$kode)
                                  ->select('soal_id')->distinct()->countAllResults();
        }

        // ------------- SUBSET TOP/BOTTOM -------------
        $take27 = max(1, (int)round($jumlahPeserta * 0.27));
        $take50 = max(1, (int)round($jumlahPeserta * 0.50));

        $top27   = array_slice($attempts, 0, $take27);
        $top50   = array_slice($attempts, 0, $take50);
        $asc     = array_reverse($attempts);         // dari benar terkecil
        $bot27   = array_slice($asc, 0, $take27);
        $bot50   = array_slice($asc, 0, $take50);

   // ----- DAFTAR SOAL + Kunci (pastikan hanya soal milik sesi ini) -----
$soal = []; // [soal_id => ['no'=>..., 'kunci'=>...]]
if ($idPaket) {
    $rows = $db->table('ujian_teori')
        ->select('id AS soal_id, UPPER(TRIM(kunci)) AS kunci')
        ->where('id_paket', $idPaket)
        ->orderBy('id', 'ASC')
        ->get()->getResultArray();
    foreach ($rows as $i => $r) {
        $soal[(int)$r['soal_id']] = ['no' => $i + 1, 'kunci' => ($r['kunci'] ?: '-')];
    }
} else {
    // Fallback: susun daftar soal dari jawaban untuk kode ini
    $ids = array_map('intval', array_column(
        $db->table('jawaban_teori')->select('soal_id')->where('kode', $kode)
          ->distinct()->orderBy('soal_id','ASC')->get()->getResultArray(),
        'soal_id'
    ));
    if (!empty($ids)) {
        // ambil kunci (kalau ada) dari ujian_teori, sisanya placeholder '-'
        $kunciMap = [];
        $kunciRows = $db->table('ujian_teori')
            ->select('id, UPPER(TRIM(kunci)) AS kunci')
            ->whereIn('id', $ids)->get()->getResultArray();
        foreach ($kunciRows as $kr) $kunciMap[(int)$kr['id']] = $kr['kunci'] ?: '-';

        $no = 1;
        foreach ($ids as $sid) {
            $soal[$sid] = ['no' => $no++, 'kunci' => $kunciMap[$sid] ?? '-'];
        }
    }
}
$jumlahSoal = count($soal);  // << tampilkan di halaman 1

// ----- FUNGSI Hitung Jawaban per subset (dibatasi hanya id soal di atas) -----
$countAnswers = function(array $subset) use ($db, $kode, $soal) {
    $idsMhs  = array_column($subset, 'id_mahasiswa');
    $idsSoal = array_keys($soal);
    if (empty($idsMhs) || empty($idsSoal)) return [];

    $q = $db->table('jawaban_teori jt')
        ->select("jt.soal_id,
                  UPPER(COALESCE(NULLIF(jt.jawaban,''),'K')) AS ans,
                  COUNT(*) AS jml")
        ->where('jt.kode', $kode)
        ->whereIn('jt.id_mahasiswa', $idsMhs)
        ->whereIn('jt.soal_id', $idsSoal)             // << BATASI DI SINI
        ->groupBy('jt.soal_id, ans')
        ->get()->getResultArray();

    // inisialisasi hanya untuk soal yang terdaftar
    $init = ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'K'=>0];
    $bag  = [];
    foreach ($idsSoal as $sid) { $bag[$sid] = $init; }

    foreach ($q as $r) {
        $sid = (int)$r['soal_id']; $ans = $r['ans'];
        if (isset($bag[$sid][$ans])) $bag[$sid][$ans] += (int)$r['jml'];
    }
    return $bag; // [soal_id => ['A'=>..,'B'=>..,'C'=>..,'D'=>..,'E'=>..,'K'=>..]]
};

        $jawTop27 = $countAnswers($top27);
        $jawTop50 = $countAnswers($top50);
        $jawBot27 = $countAnswers($bot27);
        $jawBot50 = $countAnswers($bot50);

        // ------------- VIEW HTML (semua halaman) -------------
        $kop = function(string $judul){
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
                  <td width="80"><img src="'.$logoData.'" style="height:60px"></td>
                  <td style="text-align:center;font-weight:bold">
                    KEMENTERIAN RISET, TEKNOLOGI, DAN PENDIDIKAN TINGGI<br>
                    UNIVERSITAS HASANUDDIN<br>
                    FAKULTAS KEDOKTERAN GIGI<br>
                    <div style="font-weight:normal">Jl. Perintis Kemerdekaan KM. 10 Makassar 90245 Tlp: (0411) 586012 Web: dent.unhas.ac.id</div>
                  </td>
                </tr>
              </table>
              <div style="font-weight:bold;text-align:center;margin:8px 0">'.$judul.'</div>
            ';
        };

        $fmtJam = function($ts){ return $ts ? date('H:i', strtotime($ts)).' WITA' : '-'; };
        $fmtTgl = function($ts){
            if(!$ts) return '-';
            $hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $t = strtotime($ts);
            return $hari[(int)date('w',$t)].', '.date('d',$t).' '.$bulan[(int)date('n',$t)].' '.date('Y',$t);
        };

        // halaman 1
        $html  = $kop('LAPORAN HASIL ANALISIS');
        $html .= '
        <table width="100%" cellspacing="2" cellpadding="2" style="font-size:12pt">
          <tr><td width="30%">Nama Ujian</td><td>: '.esc($kode).'</td></tr>
          <tr><td>Tanggal Ujian</td><td>: '.$fmtTgl($mulai).'</td></tr>
          <tr><td>Jam Masuk Ujian</td><td>: '.$fmtJam($mulai).'</td></tr>
          <tr><td>Jam Selesai Ujian</td><td>: '.$fmtJam($selesai).'</td></tr>
          <tr><td>Jumlah Peserta Ujian</td><td>: '.$jumlahPeserta.' Peserta</td></tr>
          <tr><td>Jumlah Soal Ujian</td><td>: '.$jumlahSoal.' Butir Soal</td></tr>
        </table>
        <div style="page-break-after:always"></div>';

        // halaman 2: nilai peserta
        $html .= $kop('JAWABAN PESERTA');
        $html .= '
        <table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;font-size:10pt">
          <thead>
            <tr style="background:#e9f2ff">
              <th>No Ujian</th><th>Nama</th><th>Benar</th><th>Salah</th><th>Kosong</th><th>Nilai</th>
            </tr>
          </thead><tbody>';
        foreach ($attempts as $a){
            $html .= '<tr>
              <td>'.esc($a['no_ujian']).'</td>
              <td>'.esc($a['nama_mhs']).'</td>
              <td style="text-align:center">'.(int)$a['benar'].'</td>
              <td style="text-align:center">'.(int)$a['salah'].'</td>
              <td style="text-align:center">'.(int)$a['kosong'].'</td>
              <td style="text-align:center">'.(int)$a['nilai'].'</td>
            </tr>';
        }
        $html .= '</tbody></table><div style="page-break-after:always"></div>';

        // util render ranking subset
        $renderRank = function(string $judul, array $rows, int $limit) use($kop){
            $rows = array_slice($rows, 0, $limit);
            $out  = $kop($judul);
            $out .= '<div style="margin:6px 0">Jumlah Peserta Yang Dianalisis Adalah '.count($rows).' Peserta</div>';
            $out .= '<table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;font-size:10pt">
              <thead><tr style="background:#e9f2ff">
                <th width="40">Nomor</th><th>No Ujian</th><th>Nama</th><th width="70">Nilai</th>
              </tr></thead><tbody>';
            $i=1;
            foreach ($rows as $r){
              $out .= '<tr>
                 <td style="text-align:center">'.$i++.'</td>
                 <td>'.esc($r['no_ujian']).'</td>
                 <td>'.esc($r['nama_mhs']).'</td>
                 <td style="text-align:center">'.(int)$r['nilai'].'</td>
               </tr>';
            }
            $out .= '</tbody></table><div style="page-break-after:always"></div>';
            return $out;
        };

        // halaman 3–6: top/bottom
        $html .= $renderRank('ANALISIS DIFERENSIASI BATAS ATAS DARI 27% PESERTA', $attempts, $take27);
        $html .= $renderRank('ANALISIS DIFERENSIASI BATAS ATAS DARI 50% PESERTA', $attempts, $take50);
        $html .= $renderRank('ANALISIS DIFERENSIASI BATAS BAWAH DARI 27% PESERTA', $asc,      $take27);
        $html .= $renderRank('ANALISIS DIFERENSIASI BATAS BAWAH DARI 50% PESERTA', $asc,      $take50);

        // util render tabel rekap jawaban per soal
        $renderJawaban = function(string $judul, array $mapJaw, array $soalMeta) use($kop){
            $out  = $kop($judul);
            $out .= '<table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;font-size:10pt">
              <thead>
                <tr style="background:#e9f2ff">
                  <th>No Soal</th><th>Kunci</th>
                  <th>Jawaban A</th><th>Jawaban B</th><th>Jawaban C</th>
                  <th>Jawaban D</th><th>Jawaban E</th><th>Jawaban K</th>
                </tr>
              </thead><tbody>';
            // urutkan sesuai nomor
            uasort($soalMeta, fn($a,$b)=>$a['no']<=>$b['no']);
            foreach($soalMeta as $sid=>$meta){
                $c = $mapJaw[$sid] ?? ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0,'K'=>0];
                $out .= '<tr>
                   <td style="text-align:center">'.$meta['no'].'</td>
                   <td style="text-align:center">'.esc($meta['kunci']).'</td>
                   <td style="text-align:center">'.$c['A'].'</td>
                   <td style="text-align:center">'.$c['B'].'</td>
                   <td style="text-align:center">'.$c['C'].'</td>
                   <td style="text-align:center">'.$c['D'].'</td>
                   <td style="text-align:center">'.$c['E'].'</td>
                   <td style="text-align:center">'.$c['K'].'</td>
                 </tr>';
            }
            $out .= '</tbody></table><div style="page-break-after:always"></div>';
            return $out;
        };

        // halaman 7–10: rekap jawaban subset
        if ($soal) {
            $html .= $renderJawaban('ANALISIS SOAL DIFERENSIASI ATAS DARI 27% PESERTA', $jawTop27, $soal);
            $html .= $renderJawaban('ANALISIS SOAL DIFERENSIASI ATAS DARI 50% PESERTA', $jawTop50, $soal);
            $html .= $renderJawaban('ANALISIS SOAL DIFERENSIASI BAWAH DARI 27% PESERTA', $jawBot27, $soal);
            $html .= $renderJawaban('ANALISIS SOAL DIFERENSIASI BAWAH DARI 50% PESERTA', $jawBot50, $soal);
        }

        // ------------- RENDER PDF -------------
        // penting: bersihkan output buffer agar header tidak bentrok
        // bersihkan buffer
while (ob_get_level() > 0) { ob_end_clean(); }

// Aman untuk semua versi Dompdf
if (class_exists(\Dompdf\Options::class)) {
    $opts = new \Dompdf\Options();
    $opts->set('isRemoteEnabled', true);
    $opts->set('isHtml5ParserEnabled', true);
    $opts->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($opts);
} else {
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
}


        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml('<style>body{font-family:DejaVu Sans, Arial, sans-serif;font-size:11pt}</style>'.$html);
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Cache-Control','private, max-age=0, must-revalidate')
            ->setHeader('Pragma','public')
            ->setBody($dompdf->output());
    }
}
