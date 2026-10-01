<?php

namespace Modules\Admin\Services;

use Config\Database;
use Modules\Admin\Models\AnalisisUjianModel;
use Modules\Admin\Models\AnalisisSoalModel;

class AnalisisUjianService
{
    protected $db;
    protected $analisisUjianModel;
    protected $analisisSoalModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->analisisUjianModel = new AnalisisUjianModel();
        $this->analisisSoalModel  = new AnalisisSoalModel();
    }

    /**
     * Ambil data analisis yang sudah disimpan, atau hitung otomatis jika belum ada.
     */
    public function getOrCalculate(int $ujianId, bool $force = false): ?array
    {
        $uji = $this->db->table('buat_teori')->where('id', $ujianId)->get()->getRowArray();
        if (!$uji) {
            return null;
        }

        $summary = $this->analisisUjianModel->where('id_ujian', $ujianId)->first();

        if (!$summary || $force) {
            $this->calculateAndSave($ujianId, $uji);
            $summary = $this->analisisUjianModel->where('id_ujian', $ujianId)->first();
        }

        // Ambil data soal
        $soalList = $this->getSoalAnalisis($ujianId, $uji['kode']);

        // Ambil rekap peserta
        $pesertaList = $this->getPesertaList($uji['kode'], (int)($uji['nilai_minimum'] ?? 0));

        // Highlight soal
        $topBenar = $soalList;
        usort($topBenar, fn($a, $b) => ($b['tingkat_kesukaran'] <=> $a['tingkat_kesukaran']) ?: ($b['total_benar'] <=> $a['total_benar']));
        $topBenar = array_slice($topBenar, 0, 5);

        $topSalah = $soalList;
        usort($topSalah, fn($a, $b) => ($a['tingkat_kesukaran'] <=> $b['tingkat_kesukaran']) ?: ($b['total_salah'] <=> $a['total_salah']));
        $topSalah = array_slice($topSalah, 0, 5);

        return [
            'ujian'       => $uji,
            'summary'     => $summary,
            'soalList'    => $soalList,
            'pesertaList' => $pesertaList,
            'topBenar'    => $topBenar,
            'topSalah'    => $topSalah,
        ];
    }

    /**
     * Jalankan kalkulasi analisis dan simpan ke database.
     */
    public function calculateAndSave(int $ujianId, ?array $uji = null): bool
    {
        if (!$uji) {
            $uji = $this->db->table('buat_teori')->where('id', $ujianId)->get()->getRowArray();
            if (!$uji) return false;
        }

        $kode = $uji['kode'];
        $passingGrade = (int)($uji['nilai_minimum'] ?? 0);

        // 1. Data Peserta & Attempts
        $totalTerdaftar = $this->db->table('admin_cbt')->where('kode', $kode)->countAllResults();

        $attempts = $this->db->table('ujian_attempt ua')
            ->select('ua.id_mahasiswa, ua.no_ujian, ua.benar, ua.salah, ua.kosong, ua.nilai, ua.status')
            ->where('ua.kode', $kode)
            ->get()->getResultArray();

        $scores = [];
        $totalLulus = 0;
        $totalSelesai = 0;

        if (!empty($attempts)) {
            foreach ($attempts as $att) {
                $isDone = in_array($att['status'], ['finished', 'expired']) || (int)$att['benar'] > 0 || (int)$att['salah'] > 0;
                if ($isDone) {
                    $totalSelesai++;
                    $skor = (int)$att['benar'];
                    $scores[] = $skor;
                    if ($passingGrade > 0 && $skor >= $passingGrade) {
                        $totalLulus++;
                    }
                }
            }
        } else {
            // Fallback: hitung dari tabel jawaban_teori jika ujian_attempt kosong
            $jawabanMhs = $this->db->table('jawaban_teori jt')
                ->select('jt.id_mahasiswa,
                          SUM(CASE WHEN UPPER(TRIM(jt.jawaban)) = UPPER(TRIM(ut.kunci)) THEN 1 ELSE 0 END) AS benar,
                          SUM(CASE WHEN UPPER(TRIM(jt.jawaban)) != UPPER(TRIM(ut.kunci)) AND jt.jawaban != "" THEN 1 ELSE 0 END) AS salah,
                          SUM(CASE WHEN jt.jawaban IS NULL OR jt.jawaban = "" THEN 1 ELSE 0 END) AS kosong')
                ->join('ujian_teori ut', 'ut.id = jt.soal_id')
                ->where('jt.kode', $kode)
                ->groupBy('jt.id_mahasiswa')
                ->get()->getResultArray();

            foreach ($jawabanMhs as $jm) {
                $totalSelesai++;
                $skor = (int)$jm['benar'];
                $scores[] = $skor;
                if ($passingGrade > 0 && $skor >= $passingGrade) {
                    $totalLulus++;
                }
            }
        }

        // Statistik Nilai
        $countScores = count($scores);
        $maxScore = $countScores > 0 ? max($scores) : 0;
        $minScore = $countScores > 0 ? min($scores) : 0;
        $avgScore = $countScores > 0 ? round(array_sum($scores) / $countScores, 2) : 0.00;
        $medianScore = $this->calculateMedian($scores);
        $stdDev = $this->calculateStdDev($scores, $avgScore);
        $persenLulus = $totalSelesai > 0 ? round(($totalLulus / $totalSelesai) * 100, 2) : 0.00;

        $now = date('Y-m-d H:i:s');

        // Simpan / Update Ringkasan Ujian
        $summaryData = [
            'id_ujian'        => $ujianId,
            'kode'            => $kode,
            'total_peserta'   => $totalTerdaftar ?: $totalSelesai,
            'total_selesai'   => $totalSelesai,
            'nilai_tertinggi' => $maxScore,
            'nilai_terendah'  => $minScore,
            'nilai_rata_rata' => $avgScore,
            'nilai_median'    => $medianScore,
            'standar_deviasi' => $stdDev,
            'total_lulus'     => $totalLulus,
            'persen_kelulusan'=> $persenLulus,
            'processed_at'    => $now,
            'updated_at'      => $now,
        ];

        $existingSummary = $this->analisisUjianModel->where('id_ujian', $ujianId)->first();
        if ($existingSummary) {
            $this->analisisUjianModel->update($existingSummary['id'], $summaryData);
        } else {
            $summaryData['created_at'] = $now;
            $this->analisisUjianModel->insert($summaryData);
        }

        // 2. Analisis Butir Soal
        $soalList = $this->db->table('ujian_teori')
            ->select('id, kunci')
            ->where('id_paket', $ujianId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (empty($soalList)) {
            // Fallback: ambil soal dari jawaban_teori
            $soalIds = array_map('intval', array_column(
                $this->db->table('jawaban_teori')->select('soal_id')->distinct()->where('kode', $kode)->get()->getResultArray(),
                'soal_id'
            ));
            if (!empty($soalIds)) {
                $soalList = $this->db->table('ujian_teori')
                    ->select('id, kunci')
                    ->whereIn('id', $soalIds)
                    ->orderBy('id', 'ASC')
                    ->get()->getResultArray();
            }
        }

        // Kelompok Atas & Bawah untuk Daya Pembeda (Top 27% vs Bot 27%)
        $rankedMhs = $this->getRankedStudentIds($kode);
        $totalRanked = count($rankedMhs);
        $groupSize = max(1, (int)round($totalRanked * 0.27));
        $topGroup = array_slice($rankedMhs, 0, $groupSize);
        $botGroup = array_slice(array_reverse($rankedMhs), 0, $groupSize);

        // Hapus data analisis soal lama untuk ujian ini
        $this->analisisSoalModel->where('id_ujian', $ujianId)->delete();

        // Ambil semua distribusi jawaban sekaligus (1 query untuk seluruh soal)
        $jawabanCounts = $this->db->table('jawaban_teori')
            ->select("soal_id, UPPER(COALESCE(NULLIF(TRIM(jawaban),''),'K')) AS ans, COUNT(*) AS jml")
            ->where('kode', $kode)
            ->groupBy('soal_id, ans')
            ->get()->getResultArray();

        $distMap = [];
        foreach ($jawabanCounts as $jc) {
            $sid = (int)$jc['soal_id'];
            $ans = $jc['ans'];
            $distMap[$sid][$ans] = (int)$jc['jml'];
        }

        // Daya Pembeda: Top & Bot counts (2 query untuk seluruh soal)
        $topCorrectMap = [];
        if (!empty($topGroup)) {
            $tc = $this->db->table('jawaban_teori')
                ->select("soal_id, UPPER(TRIM(jawaban)) AS ans, COUNT(*) AS jml")
                ->where('kode', $kode)
                ->whereIn('id_mahasiswa', $topGroup)
                ->groupBy('soal_id, ans')
                ->get()->getResultArray();
            foreach ($tc as $r) {
                $topCorrectMap[(int)$r['soal_id']][$r['ans']] = (int)$r['jml'];
            }
        }

        $botCorrectMap = [];
        if (!empty($botGroup)) {
            $bc = $this->db->table('jawaban_teori')
                ->select("soal_id, UPPER(TRIM(jawaban)) AS ans, COUNT(*) AS jml")
                ->where('kode', $kode)
                ->whereIn('id_mahasiswa', $botGroup)
                ->groupBy('soal_id, ans')
                ->get()->getResultArray();
            foreach ($bc as $r) {
                $botCorrectMap[(int)$r['soal_id']][$r['ans']] = (int)$r['jml'];
            }
        }

        $batchSoal = [];
        $noUrut = 1;

        foreach ($soalList as $soal) {
            $sid = (int)$soal['id'];
            $kunci = strtoupper(trim((string)$soal['kunci']));

            $dist = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'K' => 0];
            if (isset($distMap[$sid])) {
                foreach ($distMap[$sid] as $k => $cnt) {
                    if (isset($dist[$k])) {
                        $dist[$k] = $cnt;
                    }
                }
            }

            $totalJawab = array_sum($dist);
            $benar  = $dist[$kunci] ?? 0;
            $kosong = $dist['K'] ?? 0;
            $salah  = max(0, $totalJawab - $benar - $kosong);

            // Tingkat Kesulitan (P)
            $p = $totalJawab > 0 ? round(($benar / $totalJawab) * 100, 2) : 0.00;
            if ($p >= 70.0) {
                $kategori = 'mudah';
            } elseif ($p >= 30.0) {
                $kategori = 'sedang';
            } else {
                $kategori = 'sulit';
            }

            // Daya Pembeda (D)
            $dayaPembeda = null;
            if ($totalRanked >= 4 && $groupSize > 0) {
                $topCorrect = $topCorrectMap[$sid][$kunci] ?? 0;
                $botCorrect = $botCorrectMap[$sid][$kunci] ?? 0;
                $dayaPembeda = round(($topCorrect - $botCorrect) / $groupSize, 2);
            }

            $batchSoal[] = [
                'id_ujian'           => $ujianId,
                'kode'               => $kode,
                'soal_id'            => $sid,
                'nomor_urut'         => $noUrut++,
                'kunci'              => $kunci,
                'total_peserta'      => $totalJawab,
                'total_benar'        => $benar,
                'total_salah'        => $salah,
                'total_kosong'       => $kosong,
                'tingkat_kesukaran'  => $p,
                'kategori_kesukaran' => $kategori,
                'pilihan_a'          => $dist['A'],
                'pilihan_b'          => $dist['B'],
                'pilihan_c'          => $dist['C'],
                'pilihan_d'          => $dist['D'],
                'pilihan_e'          => $dist['E'],
                'pilihan_kosong'     => $dist['K'],
                'daya_pembeda'       => $dayaPembeda,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        if (!empty($batchSoal)) {
            $this->analisisSoalModel->insertBatch($batchSoal);
        }

        return true;
    }

    /**
     * Ambil data soal yang sudah digabung dengan teks pertanyaan.
     */
    protected function getSoalAnalisis(int $ujianId, string $kode): array
    {
        return $this->db->table('analisis_soal aso')
            ->select('aso.*, ut.pertanyaan, ut.vignette, ut.a, ut.b, ut.c, ut.d, ut.e, ut.register')
            ->join('ujian_teori ut', 'ut.id = aso.soal_id', 'left')
            ->where('aso.id_ujian', $ujianId)
            ->orderBy('aso.nomor_urut', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Ambil daftar peserta lengkap dengan nilai dan status.
     */
    protected function getPesertaList(string $kode, int $passingGrade): array
    {
        // 1. Ambil pendaftar dari admin_cbt
        $pendaftar = $this->db->table('admin_cbt p')
            ->select('p.no_ujian, p.id_mahasiswa, m.nama AS nama_mhs, m.nim, m.kelas')
            ->join('mahasiswa m', 'm.id = p.id_mahasiswa', 'left')
            ->where('p.kode', $kode)
            ->get()->getResultArray();

        // 2. Ambil attempts
        $attempts = $this->db->table('ujian_attempt')
            ->select('id_mahasiswa, no_ujian, benar, salah, kosong, nilai, status, start_at, finished_at')
            ->where('kode', $kode)
            ->get()->getResultArray();

        $attemptMap = [];
        foreach ($attempts as $a) {
            $key = $a['id_mahasiswa'] ?: $a['no_ujian'];
            $attemptMap[$key] = $a;
        }

        // 3. Fallback jika attempt kosong, ambil langsung dari jawaban_teori
        if (empty($attempts)) {
            $jawabanMhs = $this->db->table('jawaban_teori jt')
                ->select('jt.id_mahasiswa,
                          SUM(CASE WHEN UPPER(TRIM(jt.jawaban)) = UPPER(TRIM(ut.kunci)) THEN 1 ELSE 0 END) AS benar,
                          SUM(CASE WHEN UPPER(TRIM(jt.jawaban)) != UPPER(TRIM(ut.kunci)) AND jt.jawaban != "" THEN 1 ELSE 0 END) AS salah,
                          SUM(CASE WHEN jt.jawaban IS NULL OR jt.jawaban = "" THEN 1 ELSE 0 END) AS kosong')
                ->join('ujian_teori ut', 'ut.id = jt.soal_id')
                ->where('jt.kode', $kode)
                ->groupBy('jt.id_mahasiswa')
                ->get()->getResultArray();

            foreach ($jawabanMhs as $jm) {
                $attemptMap[$jm['id_mahasiswa']] = [
                    'benar'       => (int)$jm['benar'],
                    'salah'       => (int)$jm['salah'],
                    'kosong'      => (int)$jm['kosong'],
                    'nilai'       => (int)$jm['benar'],
                    'status'      => 'finished',
                    'start_at'    => null,
                    'finished_at' => null,
                ];
            }
        }

        $result = [];
        if (!empty($pendaftar)) {
            foreach ($pendaftar as $p) {
                $att = $attemptMap[$p['id_mahasiswa']] ?? ($attemptMap[$p['no_ujian']] ?? null);
                $benar  = (int)($att['benar'] ?? 0);
                $salah  = (int)($att['salah'] ?? 0);
                $kosong = (int)($att['kosong'] ?? 0);
                $hasAttempt = $att !== null;
                $isLulus = ($passingGrade > 0 && $benar >= $passingGrade);

                $result[] = [
                    'no_ujian'    => $p['no_ujian'],
                    'id_mahasiswa'=> $p['id_mahasiswa'],
                    'nama'        => $p['nama_mhs'],
                    'nim'         => $p['nim'],
                    'kelas'       => $p['kelas'],
                    'benar'       => $benar,
                    'salah'       => $salah,
                    'kosong'      => $kosong,
                    'nilai'       => $benar,
                    'has_attempt' => $hasAttempt,
                    'status'      => $att['status'] ?? 'belum',
                    'lulus'       => $isLulus,
                ];
            }
        } elseif (!empty($attemptMap)) {
            // Jika admin_cbt kosong tapi ada attempt/jawaban
            foreach ($attemptMap as $mid => $att) {
                $mhs = $this->db->table('mahasiswa')->select('nama, nim, kelas')->where('id', $mid)->get()->getRowArray();
                $benar = (int)($att['benar'] ?? 0);
                $result[] = [
                    'no_ujian'    => $att['no_ujian'] ?? '-',
                    'id_mahasiswa'=> $mid,
                    'nama'        => $mhs['nama'] ?? 'Mahasiswa #' . $mid,
                    'nim'         => $mhs['nim'] ?? '-',
                    'kelas'       => $mhs['kelas'] ?? '-',
                    'benar'       => $benar,
                    'salah'       => (int)($att['salah'] ?? 0),
                    'kosong'      => (int)($att['kosong'] ?? 0),
                    'nilai'       => $benar,
                    'has_attempt' => true,
                    'status'      => $att['status'] ?? 'finished',
                    'lulus'       => ($passingGrade > 0 && $benar >= $passingGrade),
                ];
            }
        }

        // Urutkan berdasarkan nilai tertinggi
        usort($result, fn($a, $b) => ($b['benar'] <=> $a['benar']) ?: ($a['salah'] <=> $b['salah']));

        return $result;
    }

    /**
     * Dapatkan ID mahasiswa yang diurutkan berdasarkan skor tertinggi.
     */
    protected function getRankedStudentIds(string $kode): array
    {
        $attempts = $this->db->table('ujian_attempt')
            ->select('id_mahasiswa')
            ->where('kode', $kode)
            ->orderBy('benar', 'DESC')
            ->orderBy('salah', 'ASC')
            ->get()->getResultArray();

        if (!empty($attempts)) {
            return array_map('intval', array_column($attempts, 'id_mahasiswa'));
        }

        // Fallback dari jawaban_teori
        $rows = $this->db->table('jawaban_teori jt')
            ->select('jt.id_mahasiswa,
                      SUM(CASE WHEN UPPER(TRIM(jt.jawaban)) = UPPER(TRIM(ut.kunci)) THEN 1 ELSE 0 END) AS benar')
            ->join('ujian_teori ut', 'ut.id = jt.soal_id')
            ->where('jt.kode', $kode)
            ->groupBy('jt.id_mahasiswa')
            ->orderBy('benar', 'DESC')
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'id_mahasiswa'));
    }

    protected function calculateMedian(array $numbers): float
    {
        if (empty($numbers)) return 0.00;
        sort($numbers);
        $count = count($numbers);
        $mid = floor($count / 2);
        if ($count % 2 === 0) {
            return round(($numbers[$mid - 1] + $numbers[$mid]) / 2, 2);
        }
        return round($numbers[$mid], 2);
    }

    protected function calculateStdDev(array $numbers, float $mean): float
    {
        $count = count($numbers);
        if ($count <= 1) return 0.00;
        $variance = 0.0;
        foreach ($numbers as $val) {
            $variance += pow($val - $mean, 2);
        }
        return round(sqrt($variance / ($count - 1)), 2);
    }
}
