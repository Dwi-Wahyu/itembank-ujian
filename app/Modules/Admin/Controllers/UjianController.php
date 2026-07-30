<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Modules\Auth\Libraries\Auth;

class UjianController extends BaseController
{
    protected $db;
    protected $syncApiKey;
    protected $managementUrl;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Set these in your Exam App .env file
        $this->syncApiKey = env('SYNC_API_KEY');
        $this->managementUrl = env('MANAGEMENT_API_URL'); 
    }

    // public function teoriDetail($id)
    // {
    //     $ujian = $this->db->table('buat_teori')->where('id', $id)->get()->getRowArray();
        
    //     if (!$ujian) {
    //         $ujian = ['id' => $id, 'kode' => '', 'nama' => 'Sesi belum disinkronkan'];
    //     }

    //     return view('Modules\Admin\Views\ujian\teori_detail', ['ujian' => $ujian]);
    // }

    public function teoriDetail(int $id)
    {
        $db = $this->db;

        $uji = $db->table('buat_teori')->where('id', $id)->get()->getRowArray();
        if (!$uji) {
            return redirect()->to(site_url('admin/ujian/teori'))->with('error','Data tidak ditemukan');
        }

        // join referensi (opsional)
        $dep = null;
        if (!empty($uji['dapertemen_id'])) {
            $dep = $db->table('departemen')->select('nama')->where('id', $uji['dapertemen_id'])->get()->getRowArray();
        }
        $blok = null;
        if (!empty($uji['blok'])) {
            $blok = $db->table('blok')->select('nama')->where('id', $uji['blok'])->get()->getRowArray();
        }

        // hitung peserta
        $jml = $db->table('admin_cbt')->where('kode', $uji['kode'])->countAllResults();

        return view('\Modules\Admin\Views\ujian\teori_detail', [
            'title'      => $uji['nama'],
            'menuActive' => 'ujian_teori',
            'uji'        => $uji,
            'dep'        => $dep['nama'] ?? 'Semua Departemen',
            'blok'       => $blok['nama'] ?? 'Semua Blok',
            'jumlah'     => $jml,
        ]);
    }

    public function importOffline()
    {
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('admin/ujian/teori'))->with('error', 'Metode request tidak diizinkan');
        }

        $file = $this->request->getFile('zip_file');
        if (!$file || !$file->isValid()) {
            return redirect()->to(site_url('admin/ujian/teori'))->with('error', 'File tidak valid atau tidak ditemukan');
        }

        if ($file->getClientExtension() !== 'zip') {
            return redirect()->to(site_url('admin/ujian/teori'))->with('error', 'Hanya file ZIP yang diperbolehkan');
        }

        // Pindahkan file zip ke folder sementara (writable/uploads/temp_import/)
        $tempDir = WRITEPATH . 'uploads/temp_import/' . uniqid('import_', true) . '/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        try {
            $zipName = $file->getRandomName();
            $file->move($tempDir, $zipName);
            $zipPath = $tempDir . $zipName;

            // Gunakan ZipArchive untuk mengekstrak file zip tersebut
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Gagal membuka file ZIP');
            }

            $extractPath = $tempDir . 'extracted/';
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0777, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();

            // Baca isi data.json dan decode menjadi array PHP
            $jsonFilePath = $extractPath . 'data.json';
            if (!is_file($jsonFilePath)) {
                throw new \Exception('File data.json tidak ditemukan dalam ZIP');
            }

            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);
            if (empty($data) || !isset($data['uji'])) {
                throw new \Exception('Format data.json tidak valid');
            }

            $uji = $data['uji'];
            $soalList = $data['soal'] ?? [];
            $pesertaList = $data['peserta'] ?? [];
            $mahasiswaList = $data['mahasiswa'] ?? [];

            // Gunakan Database Transaction
            $this->db->transStart();

            // 1. Ujian/Sesi (buat_teori)
            $existingUji = $this->db->table('buat_teori')->where('kode', $uji['kode'])->get()->getRowArray();
            if ($existingUji) {
                $this->db->table('buat_teori')->where('kode', $uji['kode'])->update($uji);
                $idUjian = $existingUji['id'];
            } else {
                $existingUjiById = $this->db->table('buat_teori')->where('id', $uji['id'])->get()->getRowArray();
                if ($existingUjiById) {
                    $this->db->table('buat_teori')->where('id', $uji['id'])->update($uji);
                    $idUjian = $uji['id'];
                } else {
                    $this->db->table('buat_teori')->insert($uji);
                    $idUjian = $this->db->insertID();
                }
            }

            // 2. Mahasiswa
            if (!empty($mahasiswaList)) {
                foreach ($mahasiswaList as $mhs) {
                    $existingMhs = $this->db->table('mahasiswa')->where('nim', $mhs['nim'])->get()->getRowArray();
                    if ($existingMhs) {
                        $this->db->table('mahasiswa')->where('nim', $mhs['nim'])->update($mhs);
                    } else {
                        $existingMhsById = $this->db->table('mahasiswa')->where('id', $mhs['id'])->get()->getRowArray();
                        if ($existingMhsById) {
                            $this->db->table('mahasiswa')->where('id', $mhs['id'])->update($mhs);
                        } else {
                            $this->db->table('mahasiswa')->insert($mhs);
                        }
                    }
                }
            }

            // 3. Admin CBT (Peserta)
            if (!empty($pesertaList)) {
                $this->db->table('admin_cbt')->where('kode', $uji['kode'])->delete();
                foreach ($pesertaList as &$p) {
                    unset($p['id']);
                }
                unset($p);
                $this->db->table('admin_cbt')->insertBatch($pesertaList);
            }

            // 4. Soal (ujian_teori)
            if (!empty($soalList)) {
                foreach ($soalList as &$soal) {
                    $soal['id_paket'] = $idUjian;
                    $existingSoal = $this->db->table('ujian_teori')->where('id', $soal['id'])->get()->getRowArray();
                    if ($existingSoal) {
                        $this->db->table('ujian_teori')->where('id', $soal['id'])->update($soal);
                    } else {
                        $this->db->table('ujian_teori')->insert($soal);
                    }
                }
                unset($soal);
            }

            // 5. Pindahkan file gambar/media dari hasil ekstrak
            $srcMediaDir = $extractPath . 'uploads/soal_teori/';
            $destMediaDir = FCPATH . 'uploads/soal_teori/';
            if (is_dir($srcMediaDir)) {
                if (!is_dir($destMediaDir)) {
                    mkdir($destMediaDir, 0777, true);
                }
                $files = scandir($srcMediaDir);
                foreach ($files as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $srcFile = $srcMediaDir . $f;
                    $destFile = $destMediaDir . $f;
                    if (is_file($srcFile)) {
                        copy($srcFile, $destFile);
                    }
                }
            }

            $this->db->transComplete();

            // Hapus folder sementara hasil ekstrak
            $this->_deleteDir($tempDir);

            if ($this->db->transStatus() === false) {
                return redirect()->to(site_url('admin/ujian/teori'))->with('error', 'Gagal menyimpan data ke database lokal');
            }

            return redirect()->to(site_url('admin/ujian/teori'))->with('success', 'Ujian offline berhasil di-import');

        } catch (\Exception $e) {
            if (is_dir($tempDir)) {
                $this->_deleteDir($tempDir);
            }
            return redirect()->to(site_url('admin/ujian/teori'))->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }

    public function importOfflinePraktek()
    {
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('admin/ujian/praktek'))->with('error', 'Metode request tidak diizinkan');
        }

        $file = $this->request->getFile('zip_file');
        if (!$file || !$file->isValid()) {
            return redirect()->to(site_url('admin/ujian/praktek'))->with('error', 'File tidak valid atau tidak ditemukan');
        }

        if ($file->getClientExtension() !== 'zip') {
            return redirect()->to(site_url('admin/ujian/praktek'))->with('error', 'Hanya file ZIP yang diperbolehkan');
        }

        // Pindahkan file zip ke folder sementara (writable/uploads/temp_import/)
        $tempDir = WRITEPATH . 'uploads/temp_import/' . uniqid('import_praktek_', true) . '/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        try {
            $zipName = $file->getRandomName();
            $file->move($tempDir, $zipName);
            $zipPath = $tempDir . $zipName;

            // Gunakan ZipArchive untuk mengekstrak file zip tersebut
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('Gagal membuka file ZIP');
            }

            $extractPath = $tempDir . 'extracted/';
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0777, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();

            // Baca isi data.json dan decode menjadi array PHP
            $jsonFilePath = $extractPath . 'data.json';
            if (!is_file($jsonFilePath)) {
                throw new \Exception('File data.json tidak ditemukan dalam ZIP');
            }

            $jsonData = file_get_contents($jsonFilePath);
            $data = json_decode($jsonData, true);
            if (empty($data) || (!isset($data['session']) && !isset($data['uji']) && !isset($data['osce']))) {
                throw new \Exception('Format data.json tidak valid');
            }

            $uji = $data['session'] ?? $data['uji'] ?? $data['osce'];
            $stations = $data['stations'] ?? $data['soal'] ?? $data['osce_soal'] ?? [];
            $pesertaList = $data['participants'] ?? $data['peserta'] ?? [];
            $mahasiswaList = $data['mahasiswa'] ?? [];
            $soalPraktekList = $data['soal_praktek'] ?? [];
            $aspekList = $data['aspek'] ?? [];

            // Gunakan Database Transaction
            $this->db->transStart();

            // 1. Ujian/Sesi (osce)
            $existingUji = null;
            if (!empty($uji['kode'])) {
                $existingUji = $this->db->table('osce')->where('kode', $uji['kode'])->get()->getRowArray();
            }

            if ($existingUji) {
                $this->db->table('osce')->where('kode', $uji['kode'])->update($uji);
                $idUjian = $existingUji['id'];
            } else {
                $existingUjiById = !empty($uji['id']) ? $this->db->table('osce')->where('id', $uji['id'])->get()->getRowArray() : null;
                if ($existingUjiById) {
                    $this->db->table('osce')->where('id', $uji['id'])->update($uji);
                    $idUjian = $uji['id'];
                } else {
                    $this->db->table('osce')->insert($uji);
                    $idUjian = $this->db->insertID();
                }
            }

            // 2. Mahasiswa
            if (!empty($mahasiswaList)) {
                foreach ($mahasiswaList as $mhs) {
                    $existingMhs = !empty($mhs['nim']) ? $this->db->table('mahasiswa')->where('nim', $mhs['nim'])->get()->getRowArray() : null;
                    if ($existingMhs) {
                        $this->db->table('mahasiswa')->where('nim', $mhs['nim'])->update($mhs);
                    } else {
                        $existingMhsById = !empty($mhs['id']) ? $this->db->table('mahasiswa')->where('id', $mhs['id'])->get()->getRowArray() : null;
                        if ($existingMhsById) {
                            $this->db->table('mahasiswa')->where('id', $mhs['id'])->update($mhs);
                        } else {
                            $this->db->table('mahasiswa')->insert($mhs);
                        }
                    }
                }
            }

            // 3. Admin CBT (Peserta)
            if (!empty($pesertaList) && !empty($uji['kode'])) {
                $this->db->table('admin_cbt')->where('kode', $uji['kode'])->delete();
                foreach ($pesertaList as &$p) {
                    unset($p['id']);
                }
                unset($p);
                $this->db->table('admin_cbt')->insertBatch($pesertaList);
            }

            // 4. Soal Praktek (ujian_praktek)
            if (!empty($soalPraktekList)) {
                foreach ($soalPraktekList as $soal) {
                    $existingSoal = null;
                    if (!empty($soal['id'])) {
                        $existingSoal = $this->db->table('ujian_praktek')->where('id', $soal['id'])->get()->getRowArray();
                    }
                    if (!$existingSoal && !empty($soal['register'])) {
                        $existingSoal = $this->db->table('ujian_praktek')->where('register', $soal['register'])->get()->getRowArray();
                    }

                    if ($existingSoal) {
                        $this->db->table('ujian_praktek')->where('id', $existingSoal['id'])->update($soal);
                    } else {
                        $this->db->table('ujian_praktek')->insert($soal);
                    }
                }
            }

            // 5. Aspek Rubrik Penilaian (aspek)
            if (!empty($aspekList)) {
                $soalPraktekIds = array_filter(array_column($soalPraktekList, 'id'));
                if (!empty($soalPraktekIds)) {
                    $this->db->table('aspek')->whereIn('soal_id', $soalPraktekIds)->delete();
                }
                foreach ($aspekList as $asp) {
                    if (!empty($asp['id'])) {
                        $existingAsp = $this->db->table('aspek')->where('id', $asp['id'])->get()->getRowArray();
                        if ($existingAsp) {
                            $this->db->table('aspek')->where('id', $asp['id'])->update($asp);
                            continue;
                        }
                    }
                    $this->db->table('aspek')->insert($asp);
                }
            }

            // 6. Stasiun/Soal OSCE (osce_soal)
            if (!empty($stations)) {
                $this->db->table('osce_soal')->where('osce_id', $idUjian)->delete();
                foreach ($stations as &$st) {
                    $st['osce_id'] = $idUjian;
                    unset($st['id']);
                }
                unset($st);
                $this->db->table('osce_soal')->insertBatch($stations);
            }

            // 7. Pindahkan file gambar/media dari hasil ekstrak
            $mediaFolders = ['uploads/soal_praktek/', 'uploads/osce_soal/', 'uploads/soal_teori/'];
            foreach ($mediaFolders as $relDir) {
                $srcMediaDir = $extractPath . $relDir;
                $destMediaDir = FCPATH . $relDir;
                if (is_dir($srcMediaDir)) {
                    if (!is_dir($destMediaDir)) {
                        mkdir($destMediaDir, 0777, true);
                    }
                    $files = scandir($srcMediaDir);
                    foreach ($files as $f) {
                        if ($f === '.' || $f === '..') continue;
                        $srcFile = $srcMediaDir . $f;
                        $destFile = $destMediaDir . $f;
                        if (is_file($srcFile)) {
                            copy($srcFile, $destFile);
                        }
                    }
                }
            }

            $this->db->transComplete();

            // Hapus folder sementara hasil ekstrak
            $this->_deleteDir($tempDir);

            if ($this->db->transStatus() === false) {
                return redirect()->to(site_url('admin/ujian/praktek'))->with('error', 'Gagal menyimpan data ke database lokal');
            }

            return redirect()->to(site_url('admin/ujian/praktek'))->with('success', 'Ujian offline praktek berhasil di-import');

        } catch (\Exception $e) {
            if (is_dir($tempDir)) {
                $this->_deleteDir($tempDir);
            }
            return redirect()->to(site_url('admin/ujian/praktek'))->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }

    private function _deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->_deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }


    // 2. API: Get Live Student Status for the Room Investigator
    public function getLiveStatus($id)
    {
        $ujian = $this->db->table('buat_teori')->where('id', $id)->get()->getRowArray();
        if (!$ujian) return $this->response->setJSON(['students' => []]);

        $kode_ujian = $ujian['kode'];

        // Join enrolled participants with their live attempt progress
        $query = $this->db->query("
            SELECT 
                c.no_ujian, 
                m.nama as nama_mahasiswa, 
                CASE 
                    WHEN a.status = 'ongoing' THEN 'mengerjakan'
                    WHEN a.status IN ('done', 'finished') THEN 'selesai'
                    ELSE 'belum mulai' 
                END as status_ujian,
                COALESCE(a.remaining_seconds, 0) as remaining_seconds,
                COALESCE(a.violations, 0) as violations,
                a.id as attempt_id
            FROM admin_cbt c
            LEFT JOIN mahasiswa m ON m.id = c.id_mahasiswa
            LEFT JOIN ujian_attempt a ON c.no_ujian = a.no_ujian AND a.kode = c.kode
            WHERE c.kode = ?
            ORDER BY c.no_ujian ASC
        ", [$kode_ujian]);

        $students = $query->getResultArray();

        // Convert seconds to minutes for the UI
        foreach ($students as &$stu) {
            $stu['remaining_time'] = floor($stu['remaining_seconds'] / 60);
        }

        return $this->response->setJSON(['students' => $students]);
    }

    private function _doPullExam(string $kode_ujian): string
    {
        $client = \Config\Services::curlrequest();
        $url = rtrim($this->managementUrl, '/') . '/api/sync/export-teori/' . $kode_ujian;

        try {
            $response = $client->request('GET', $url, [
                'headers' => ['X-API-KEY' => $this->syncApiKey, 'Accept' => 'application/json'],
                'verify' => env('CI_ENVIRONMENT') === 'production' ? true : false,
                'http_errors' => false,
                'timeout' => 15,
            ]);

            $body = json_decode($response->getBody(), true);
            if (($body['status'] ?? '') !== 'success') return 'error';

            $data = $body['data'];

            $this->db->transStart();
            $this->db->table('buat_teori')->where('kode', $kode_ujian)->delete();
            $this->db->table('admin_cbt')->where('kode', $kode_ujian)->delete();

            if (!empty($data['exam'])) $this->db->table('buat_teori')->insert($data['exam']);
            if (!empty($data['participants'])) {
                $participants = $data['participants'];
                foreach ($participants as &$p) {
                    unset($p['id']);
                }
                unset($p);
                $this->db->table('admin_cbt')->insertBatch($participants);
            }
            if (!empty($data['questions']))    $this->db->table('ujian_teori')->ignore(true)->insertBatch($data['questions']);
            $this->db->transComplete();

            return $this->db->transStatus() ? 'success' : 'db_error';
        } catch (\Exception $e) {
            return 'connection_error';
        }
    }

    public function pullExam($kode_ujian)
    {
        $result = $this->_doPullExam($kode_ujian);
        $messages = [
            'success'          => 'Sesi ujian berhasil disinkronkan.',
            'error'            => 'VPS mengembalikan error.',
            'db_error'         => 'Gagal menyimpan ke database lokal.',
            'connection_error' => 'Gagal terhubung ke VPS.',
        ];
        $status = $result === 'success' ? 'success' : 'error';
        return $this->response->setJSON(['status' => $status, 'message' => $messages[$result] ?? $result]);
    }

    public function autoSyncOnLogin()
    {
        $results = [];
        $today = date('Y-m-d');

        // Fetch sessions from VPS that are today or in the future
        $client = \Config\Services::curlrequest();
        $url = rtrim($this->managementUrl, '/') . '/api/sync/list-sessions';

        try {
            $response = $client->request('GET', $url, [
                'headers' => ['X-API-KEY' => $this->syncApiKey, 'Accept' => 'application/json'],
                'verify' => env('CI_ENVIRONMENT') === 'production' ? true : false,
                'http_errors' => false,
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody(), true);
                $sessions = $body['data'] ?? [];

                foreach ($sessions as $session) {
                    $kode = $session['kode'] ?? null;
                    if (!$kode) continue;
                    // Only pull sessions that are today or upcoming
                    if (isset($session['tanggal']) && $session['tanggal'] < $today) continue;

                    $pullResult = $this->_doPullExam($kode);
                    $results[] = ['kode' => $kode, 'status' => $pullResult];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Pull Teori failed: ' . $e->getMessage());
        }

        // Pull OSCE sessions:
        $osceSessions = [];
        try {
            $osceUrl = rtrim($this->managementUrl, '/') . '/api/sync/list-osce-sessions';
            $osceResponse = $client->request('GET', $osceUrl, [
                'headers' => ['X-API-KEY' => $this->syncApiKey],
                'verify'  => env('CI_ENVIRONMENT') === 'production' ? true : false,
                'timeout' => 10,
                'http_errors' => false,
            ]);
            if ($osceResponse->getStatusCode() === 200) {
                $osceBody = json_decode($osceResponse->getBody(), true);
                $osceSessions = $osceBody['data'] ?? [];
            }
        } catch (\Exception $e) {
            log_message('error', 'Pull OSCE list failed: ' . $e->getMessage());
        }

        foreach ($osceSessions as $s) {
            $kode = $s['kode'] ?? null;
            if (!$kode) continue;
            if (isset($s['tanggal']) && $s['tanggal'] < $today) continue;
            try { $this->_doPullOsce($kode); } catch (\Exception $e) {
                log_message('error', "Pull OSCE {$kode} failed: " . $e->getMessage());
            }
        }

        // Store sync summary in session flash
        session()->setFlashdata('sync_result', count($results) . ' sesi disinkronkan dari VPS.');
        return redirect()->to(site_url('admin/dashboard'));
    }

    private function _doPullOsce(string $kode): string
    {
        $client = \Config\Services::curlrequest();
        $url = rtrim($this->managementUrl, '/') . '/api/sync/export-osce/' . $kode;

        try {
            $response = $client->request('GET', $url, [
                'headers' => ['X-API-KEY' => $this->syncApiKey, 'Accept' => 'application/json'],
                'verify' => env('CI_ENVIRONMENT') === 'production' ? true : false,
                'http_errors' => false,
                'timeout' => 15,
            ]);

            $body = json_decode($response->getBody(), true);
            if (($body['status'] ?? '') !== 'success') return 'error';

            $data = $body['data'];
            $this->db->transStart();
            $this->db->table('osce')->where('kode', $kode)->delete();
            $this->db->table('admin_cbt')->where('kode', $kode)->delete();

            if (!empty($data['session']))      $this->db->table('osce')->insert($data['session']);
            if (!empty($data['participants'])) {
                $participants = $data['participants'];
                foreach ($participants as &$p) {
                    unset($p['id']);
                }
                unset($p);
                $this->db->table('admin_cbt')->insertBatch($participants);
            }
            
            // Delete existing station mappings before insertBatch
            if (!empty($data['session']['id'])) {
                $this->db->table('osce_soal')->where('osce_id', $data['session']['id'])->delete();
            }
            if (!empty($data['stations']))     $this->db->table('osce_soal')->insertBatch($data['stations']);
            
            $this->db->transComplete();

            return $this->db->transStatus() ? 'success' : 'db_error';
        } catch (\Exception $e) {
            return 'connection_error';
        }
    }

    public function pullOsce($kode)
    {
        $result = $this->_doPullOsce($kode);
        $messages = [
            'success'          => 'Sesi OSCE berhasil disinkronkan.',
            'error'            => 'VPS mengembalikan error.',
            'db_error'         => 'Gagal menyimpan ke database lokal.',
            'connection_error' => 'Gagal terhubung ke VPS.',
        ];
        $status = $result === 'success' ? 'success' : 'error';
        return $this->response->setJSON(['status' => $status, 'message' => $messages[$result] ?? $result]);
    }

    public function pushOsceResults($kode)
    {
        $session = $this->db->table('osce')->where('kode', $kode)->get()->getRowArray();
        if (!$session) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Sesi OSCE tidak ditemukan.']);
        }

        $results = $this->db->table('jawaban_osce')
            ->where('osce_id', $session['id'])
            ->where('synced_at IS NULL', null, false)
            ->get()->getResultArray();

        $participants = $this->db->table('admin_cbt')
            ->where('kode', $kode)
            ->get()->getResultArray();

        $stations = $this->db->table('osce_soal')
            ->where('osce_id', $session['id'])
            ->get()->getResultArray();

        if (empty($results) && empty($participants) && empty($stations)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data jawaban, peserta, atau station untuk dikirim.']);
        }

        foreach ($results as &$res) {
            $aspek = $this->db->table('jawaban_osce_aspek')
                ->where('jawaban_osce_id', $res['id'])
                ->get()->getResultArray();
            $res['aspek'] = $aspek;
        }

        $client = \Config\Services::curlrequest();
        $url = rtrim($this->managementUrl, '/') . '/api/sync/import-osce-results';

        try {
            $response = $client->request('POST', $url, [
                'headers'     => ['X-API-KEY' => $this->syncApiKey, 'Content-Type' => 'application/json'],
                'json'        => [
                    'results'      => $results,
                    'participants' => $participants,
                    'stations'     => $stations
                ],
                'verify'      => env('CI_ENVIRONMENT') === 'production' ? true : false,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody(), true);
            if ($response->getStatusCode() >= 400) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal push: HTTP ' . $response->getStatusCode()]);
            }

            $syncedIds = array_column($results, 'id');
            if (!empty($syncedIds)) {
                $this->db->table('jawaban_osce')
                    ->whereIn('id', $syncedIds)
                    ->update(['synced_at' => date('Y-m-d H:i:s')]);
            }

            return $this->response->setJSON(['status' => 'success', 'message' => $result['message'] ?? 'Berhasil.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function pushResults($kode_ujian)
    {
        $attempts = $this->db->table('ujian_attempt')
            ->where('kode', $kode_ujian)
            ->whereIn('status', ['done', 'finished', 'ongoing']) 
            ->where('synced_at IS NULL', null, false)
            ->get()->getResultArray();

        $participants = $this->db->table('admin_cbt')
            ->where('kode', $kode_ujian)
            ->get()->getResultArray();

        if (empty($attempts) && empty($participants)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No attempts or participants found to sync.']);
        }

        $client = \Config\Services::curlrequest();
        $url = rtrim($this->managementUrl, '/') . '/api/sync/import-results';

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'X-API-KEY' => $this->syncApiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'attempts' => $attempts,
                    'participants' => $participants
                ],
                'verify' => env('CI_ENVIRONMENT') === 'production' ? true : false,
                'http_errors' => false
            ]);

            $body = $response->getBody();
            $result = json_decode($body, true);

            if ($response->getStatusCode() >= 400) {
                if (is_array($result) && isset($result['messages']['error'])) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to push results: ' . $result['messages']['error']]);
                }
                if (is_array($result) && isset($result['error'])) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to push results: ' . $result['error']]);
                }
                return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to push results: HTTP ' . $response->getStatusCode() . ' - ' . $body]);
            }

            $syncedIds = [];
            foreach ($attempts as $a) {
                if (in_array($a['status'], ['done', 'finished'])) {
                    $syncedIds[] = $a['id'];
                }
            }
            if (!empty($syncedIds)) {
                $this->db->table('ujian_attempt')
                    ->whereIn('id', $syncedIds)
                    ->update(['synced_at' => date('Y-m-d H:i:s')]);
            }

            if (is_array($result) && isset($result['message'])) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Push Status: ' . $result['message']]);
            }
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to push results: Unexpected response: ' . $body]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to push results: ' . $e->getMessage()]);
        }
    }
    
    // 5. Command: Force submit a student who forgot to log out or was caught cheating
    public function forceSubmit($attempt_id)
    {
        $this->db->table('ujian_attempt')
             ->where('id', $attempt_id)
             ->update(['status' => 'done', 'remaining_seconds' => 0]);
             
        return $this->response->setJSON(['status' => 'success']);
    }

    private function makeCode(int $len = 6): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max   = strlen($chars) - 1;
        $code  = '';
        for ($i = 0; $i < $len; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

    /** generate kode unik (cek DB; retry) */
    private function generateUniqueKode(int $len = 6, ?string $prefix = null): string
    {
        $prefix = $prefix ? strtoupper($prefix) . '-' : '';
        $tbl    = $this->db->table('buat_teori');

        // maksimal 10 percobaan random, lalu pakai suffix hex
        for ($i = 0; $i < 10; $i++) {
            $k = $prefix . $this->makeCode($len);
            $exists = $tbl->select('id')->where('kode', $k)->get()->getFirstRow();
            if (! $exists) return $k;
        }
        return $prefix . $this->makeCode(max(4, $len - 2)) . strtoupper(dechex(random_int(0, 255)));
    }

    /** (opsional) endpoint untuk tombol "Generate" di UI */
    public function newKode()
    {
        $len    = max(4, min(16, (int)($this->request->getGet('len') ?? 6)));
        $prefix = $this->request->getGet('prefix');
        $kode   = $this->generateUniqueKode($len, $prefix);
        return $this->response->setJSON(['status'=>'ok','kode'=>$kode, 'csrf_token'=>csrf_hash()]);
    }
    
    // === LIST + FILTER + TABS (full page atau fragment AJAX) ===
    public function teori()
    {
        $r     = $this->request;
        $tab   = $r->getGet('tab') ?: 'mendatang';     // review|mendatang|selesai
        $page  = max(1, (int)$r->getGet('page'));
        $per   = 20;
        $today    = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));

        // filters
        $q       = trim((string)$r->getGet('q'));           // nama ujian
        $depId   = $r->getGet('departemen_id');             // id departemen
        $blokId  = $r->getGet('blok_id');                   // id blok
        $d1      = $r->getGet('d1');                        // yyyy-mm-dd
        $d2      = $r->getGet('d2');

        $b = $this->db->table('buat_teori u')
            ->select('u.id,u.nama,u.tanggal,u.mulai,u.selesai,u.kode,u.jumlah_soal,u.status,
                    d.nama AS departemen, bk.nama AS blok_nama')
            // ⬇️ SUBQUERY: hitung jumlah peserta per kode (aman utk ONLY_FULL_GROUP_BY)
            ->select('(SELECT COUNT(*) FROM admin_cbt ac WHERE ac.kode = u.kode) AS jml_peserta', false)
            ->join('departemen d','d.id = u.dapertemen_id','left')
            ->join('blok bk','bk.id = u.blok','left');

        // === scope berdasarkan tab ===
        switch ($tab) {
            case 'mendatang':
                $b->where('u.tanggal >=', $today)
                  ->where('u.tanggal <=', $nextWeek);
                break;
            case 'berlangsung':
                $b->where('u.tanggal =', $today);
                break;
            case 'selesai':
                $b->where('u.tanggal <', $today);
                break;
            default: // REVIEW = belum ACC
                $b->groupStart()
                    ->where('u.status', null)
                    ->orWhere('u.status', '')
                    ->orWhere('u.status', 0)
                    ->orWhere('u.status', '0')
                    ->orWhereIn('u.status', ['pending','review','Pending','Review'])
                ->groupEnd();
                break;
        }

        // === filters ===
        if ($q !== '')                        $b->like('u.nama', $q);
        if ($depId !== null && $depId!=='')   $b->where('u.dapertemen_id', (int)$depId);
        if ($blokId !== null && $blokId!=='') $b->where('u.blok', (int)$blokId);
        if ($d1)                              $b->where('u.tanggal >=', $d1);
        if ($d2)                              $b->where('u.tanggal <=', $d2);

        // === paging ===
        $bc    = clone $b;
        $total = (int) $bc->countAllResults();   // hitung total baris tanpa merusak builder utama

        $offset = ($page - 1) * $per;
        $rows   = $b->orderBy('u.tanggal','DESC')->limit($per, $offset)->get()->getResultArray();

        // data dropdown
        $deps = $this->db->table('departemen')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
        $blks = $this->db->table('blok')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();

        $data = [
            'title'      => 'Sesi Ujian Teori',
            'menuActive' => 'ujian_teori',
            'tab'        => $tab,
            'rows'       => $rows,
            'page'       => $page,
            'per'        => $per,
            'total'      => $total,
            'filters'    => ['q'=>$q,'depId'=>$depId,'blokId'=>$blokId,'d1'=>$d1,'d2'=>$d2],
            'departemen' => $deps,
            'blok'       => $blks,
        ];

        // fragment (AJAX) -> kembalikan tabel + pager saja, sekaligus refresh token via header
        if ($r->isAJAX() || $r->getGet('frag') === 'list') {
            $html = view('\Modules\Admin\Views\ujian\partials\teori_table', $data);
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setContentType('text/html')
                ->setBody($html);
        }

        // full page
        return view('\Modules\Admin\Views\ujian\teori_list', $data);
    }

        // === CREATE (modal, jQuery) ===
        public function teoriCreate()
        {
            if (! $this->request->is('post')) {
                return $this->response->setStatusCode(405)
                    ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
            }

            // normalisasi input tanggal/waktu (biar aman)
            $tanggal = $this->request->getPost('tanggal');
            $mulai   = $this->request->getPost('mulai');
            $selesai = $this->request->getPost('selesai');

            $tanggal = $tanggal ? date('Y-m-d', strtotime(str_replace('/','-',$tanggal))) : null;
            $mulai   = $mulai   ? date('H:i:s', strtotime($mulai)) : null;
            $selesai = $selesai ? date('H:i:s', strtotime($selesai)) : null;

            $kode = strtoupper(trim((string)$this->request->getPost('kode')));

            // jika kosong, generate otomatis
            if ($kode === '') {
                $kode = $this->generateUniqueKode(6); // atur panjang sesuai kebutuhan
            }

            $data = [
                'nama'           => trim((string)$this->request->getPost('nama')),
                'dapertemen_id'  => $this->request->getPost('dapertemen_id') ?: '',
                'blok'           => $this->request->getPost('blok') ?: '',
                'tanggal'        => $tanggal,
                'mulai'          => $mulai,
                'selesai'        => $selesai,
                'kode'           => $kode,
                'jumlah_soal'    => (int)$this->request->getPost('jumlah_soal'),
                'status'         => $this->request->getPost('status') ?: 'pending',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
                'nilai_minimum'  => $this->request->getPost('nilai_minimum') ?: 0,
            ];

            // validasi ringkas
            if ($data['nama'] === '' || empty($data['tanggal'])) {
                return $this->response->setStatusCode(422)
                    ->setJSON(['status'=>'error','message'=>'Nama & tanggal wajib diisi','csrf_token'=>csrf_hash()]);
            }

            // insert + handle race (jaga2 duplikat karena beban concurrent)
            for ($try=0; $try<2; $try++) {
                $this->db->table('buat_teori')->insert($data);
                $err = $this->db->error();
                if (empty($err['code'])) {
                    return $this->response->setStatusCode(201)
                        ->setJSON(['status'=>'ok','message'=>'Sesi tersimpan','id'=>$this->db->insertID(),'kode'=>$kode,'csrf_token'=>csrf_hash()]);
                }
                // kalau bentrok unik (duplicate key), regenerate lalu retry sekali
                if ((int)$err['code'] === 1062) { // MySQL duplicate entry
                    $data['kode'] = $this->generateUniqueKode(6);
                    continue;
                }
                // error lain
                return $this->response->setStatusCode(500)
                    ->setJSON(['status'=>'error','message'=>'Gagal menyimpan: '.$err['message'],'csrf_token'=>csrf_hash()]);
            }

            // jika tetap gagal setelah retry (nyaris tidak mungkin)
            return $this->response->setStatusCode(500)
                ->setJSON(['status'=>'error','message'=>'Gagal generate kode unik','csrf_token'=>csrf_hash()]);
        }
    public function teoriGet($id)
    {
        $row = $this->db->table('buat_teori u')
            ->select('u.*')
            ->where('u.id', $id)
            ->get()->getRowArray();

        if (!$row) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan']);
        }
        return $this->response->setJSON(['status'=>'ok','data'=>$row]);
    }

    public function teoriUpdate($id)
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan']);
        }

        // normalisasi input (sama seperti create)
        $rawTgl     = (string) $this->request->getPost('tanggal');
        $rawMulai   = (string) $this->request->getPost('mulai');
        $rawSelesai = (string) $this->request->getPost('selesai');

        $tanggal = $rawTgl     ? date('Y-m-d', strtotime(str_replace('/','-',$rawTgl))) : null;
        $mulai   = $rawMulai   ? date('H:i:s', strtotime($rawMulai)) : null;
        $selesai = $rawSelesai ? date('H:i:s', strtotime($rawSelesai)) : null;

        $data = [
            'nama'           => trim((string)$this->request->getPost('nama')),
            'dapertemen_id'  => $this->request->getPost('dapertemen_id') ?: null,
            'blok'           => $this->request->getPost('blok') ?: null,
            'tanggal'        => $tanggal,
            'mulai'          => $mulai,
            'selesai'        => $selesai,
            'kode'           => trim((string)$this->request->getPost('kode')),
            'jumlah_soal'    => (int) $this->request->getPost('jumlah_soal'),
            'status'         => $this->request->getPost('status') ?: 'pending',
            'updated_at'     => date('Y-m-d H:i:s'),
            'nilai_minimum'  => $this->request->getPost('nilai_minimum') ?: 0,
        ];

        if ($this->request->getPost('nama')==='' || empty($tanggal)) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'Nama & tanggal wajib diisi.']);
        }

        $this->db->table('buat_teori')->update($data, ['id'=>$id]);

        return $this->response->setJSON(['status'=>'ok','message'=>'Perubahan tersimpan.']);
    }

    // ===== DETAIL =====
    // public function teoriDetail(int $id)
    // {
    //     $db = $this->db;

    //     $uji = $db->table('buat_teori')->where('id', $id)->get()->getRowArray();
    //     if (!$uji) {
    //         return redirect()->to(site_url('admin/ujian/teori'))->with('error','Data tidak ditemukan');
    //     }

    //     // join referensi (opsional)
    //     $dep = null;
    //     if (!empty($uji['dapertemen_id'])) {
    //         $dep = $db->table('departemen')->select('nama')->where('id', $uji['dapertemen_id'])->get()->getRowArray();
    //     }
    //     $blok = null;
    //     if (!empty($uji['blok'])) {
    //         $blok = $db->table('blok')->select('nama')->where('id', $uji['blok'])->get()->getRowArray();
    //     }

    //     // hitung peserta
    //     $jml = $db->table('admin_cbt')->where('kode', $uji['kode'])->countAllResults();

    //     return view('\Modules\Admin\Views\ujian\teori_detail', [
    //         'title'      => $uji['nama'],
    //         'menuActive' => 'ujian_teori',
    //         'uji'        => $uji,
    //         'dep'        => $dep['nama'] ?? 'Semua Departemen',
    //         'blok'       => $blok['nama'] ?? 'Semua Blok',
    //         'jumlah'     => $jml,
    //     ]);
    // }

    // Modules/Admin/Controllers/UjianController.php (cuplikan)

    public function praktek()
    {
        $r     = $this->request;
        $tab   = $r->getGet('tab') ?: 'mendatang';     // mendatang|selesai
        $page  = max(1, (int)$r->getGet('page'));
        $per   = 20;
        $today    = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));

        // filters
        $q       = trim((string)$r->getGet('q'));          // nama ujian (nama_ujian)
        $depId   = $r->getGet('departemen_id');
        $blokId  = $r->getGet('blok_id');
        $d1      = $r->getGet('d1');                       // yyyy-mm-dd
        $d2      = $r->getGet('d2');

        $b = $this->db->table('osce u')
            ->select('u.id,u.kode,u.nama_ujian,u.tanggal,u.departemen_id,u.blok')
            ->select('d.nama AS departemen, bk.nama AS blok_nama')
            ->select('(SELECT COUNT(*) FROM admin_cbt ac WHERE ac.kode = u.kode) AS jml_peserta', false)
            ->join('departemen d','d.id = u.departemen_id','left')
            ->join('blok bk','bk.id = u.blok','left');

        // scope tab (berdasarkan tanggal)
        switch ($tab) {
            case 'mendatang':    $b->where('u.tanggal >=', $today)->where('u.tanggal <=', $nextWeek); break;
            case 'berlangsung':  $b->where('u.tanggal =', $today); break;
            case 'selesai':      $b->where('u.tanggal <', $today); break;
            default:             $b->where('u.tanggal =', $today); break;
        }

        // filters
        if ($q !== '')                        $b->like('u.nama_ujian', $q);
        if ($depId !== null && $depId!=='')   $b->where('u.departemen_id', (int)$depId);
        if ($blokId !== null && $blokId!=='') $b->where('u.blok', (int)$blokId);
        if ($d1)                              $b->where('u.tanggal >=', $d1);
        if ($d2)                              $b->where('u.tanggal <=', $d2);

        // paging
        $bc    = clone $b;
        $total = (int) $bc->countAllResults();

        $offset = ($page - 1) * $per;
        $rows   = $b->orderBy('u.tanggal','DESC')->limit($per, $offset)->get()->getResultArray();

        // dropdowns
        $deps = $this->db->table('departemen')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();
        $blks = $this->db->table('blok')->select('id,nama')->orderBy('nama','asc')->get()->getResultArray();

        $data = [
            'title'      => 'Sesi Ujian Praktek (OSCE)',
            'menuActive' => 'ujian_praktek',
            'tab'        => $tab,
            'rows'       => $rows,
            'page'       => $page,
            'per'        => $per,
            'total'      => $total,
            'filters'    => ['q'=>$q,'depId'=>$depId,'blokId'=>$blokId,'d1'=>$d1,'d2'=>$d2],
            'departemen' => $deps,
            'blok'       => $blks,
        ];

        // fragment untuk AJAX (table+paging saja)
        if ($r->isAJAX() || $r->getGet('frag') === 'list') {
            $html = view('\Modules\Admin\Views\ujian\partials\praktek_table', $data);
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setContentType('text/html')
                ->setBody($html);
        }

        // full page
        return view('\Modules\Admin\Views\ujian\praktek_list', $data);
    }


    // === CREATE (modal, jQuery) ===
    // osce TIDAK punya mulai/selesai/status/jumlah_soal
    public function praktekCreate()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
        }
    $uid = (int) (Auth::user()['id'] ?? 0);

        $tanggal = (string)$this->request->getPost('tanggal');
        $tanggal = $tanggal ? date('Y-m-d', strtotime(str_replace('/','-',$tanggal))) : null;

        $kode = strtoupper(trim((string)$this->request->getPost('kode')));
        if ($kode === '') {
            $kode = $this->generateUniqueKode(6);
        }

        $data = [
            'kode'          => $kode,
            'nama_ujian'    => trim((string)$this->request->getPost('nama')),
            'departemen_id' => $this->request->getPost('departemen_id') ?: '',
            'blok'          => $this->request->getPost('blok') ?: '',
            'tanggal'       => $tanggal,
            'created_by'    => $uid, // sesuaikan getter user id-mu
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($data['nama_ujian'] === '' || empty($data['tanggal'])) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'Nama & tanggal wajib diisi','csrf_token'=>csrf_hash()]);
        }

        // insert + handle duplikat kode
        for ($try=0; $try<2; $try++) {
            $this->db->table('osce')->insert($data);
            $err = $this->db->error();
            if (empty($err['code'])) {
                return $this->response->setStatusCode(201)
                    ->setJSON(['status'=>'ok','message'=>'Sesi tersimpan','id'=>$this->db->insertID(),'kode'=>$kode,'csrf_token'=>csrf_hash()]);
            }
            if ((int)$err['code'] === 1062) { // duplicate entry
                $data['kode'] = $kode = $this->generateUniqueKode(6);
                continue;
            }
            return $this->response->setStatusCode(500)
                ->setJSON(['status'=>'error','message'=>'Gagal menyimpan: '.$err['message'],'csrf_token'=>csrf_hash()]);
        }

        return $this->response->setStatusCode(500)
            ->setJSON(['status'=>'error','message'=>'Gagal generate kode unik','csrf_token'=>csrf_hash()]);
    }


    public function praktekGet($id)
    {
        $row = $this->db->table('osce u')->select('u.*')->where('u.id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()]);
        }
        return $this->response->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
    }


    public function praktekUpdate($id)
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405)
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
        }

        $rawTgl  = (string) $this->request->getPost('tanggal');
        $tanggal = $rawTgl ? date('Y-m-d', strtotime(str_replace('/','-',$rawTgl))) : null;

        $nama = $this->request->getPost('nama_ujian') !== null && $this->request->getPost('nama_ujian') !== ''
            ? $this->request->getPost('nama_ujian')
            : $this->request->getPost('nama');
        $dep  = $this->request->getPost('departemen_id') ?: $this->request->getPost('dapertemen_id');

        $data = [
            'kode'          => strtoupper(trim((string)$this->request->getPost('kode'))),
            'nama_ujian'    => trim((string)$nama),
            'departemen_id' => $dep ?: null,
            'blok'          => $this->request->getPost('blok') ?: null,
            'tanggal'       => $tanggal,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if (empty($data['nama_ujian']) || empty($tanggal)) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'Nama & tanggal wajib diisi.']);
        }

        $this->db->table('osce')->update($data, ['id'=>$id]);
        return $this->response->setJSON(['status'=>'ok','message'=>'Perubahan tersimpan.','csrf_token'=>csrf_hash()]);
    }


    // ===== DETAIL PRAKTEK =====
    public function praktekDetail(int $id)
    {
        $db  = $this->db;
        $uji = $db->table('osce')->where('id', $id)->get()->getRowArray();
        if (!$uji) {
            return redirect()->to(site_url('admin/ujian/praktek'))->with('error','Data tidak ditemukan');
        }

        $dep = null;
        if (!empty($uji['departemen_id'])) {
            $dep = $db->table('departemen')->select('nama')->where('id', $uji['departemen_id'])->get()->getRowArray();
        }
        $blok = null;
        if (!empty($uji['blok'])) {
            $blok = $db->table('blok')->select('nama')->where('id', $uji['blok'])->get()->getRowArray();
        }

        $jml = $db->table('admin_cbt')->where('kode', $uji['kode'])->countAllResults();

        return view('\Modules\Admin\Views\ujian\praktek_detail', [
            'title'      => $uji['nama_ujian'],
            'menuActive' => 'ujian_praktek',
            'uji'        => $uji,
            'dep'        => $dep['nama'] ?? 'Semua Departemen',
            'blok'       => $blok['nama'] ?? 'Semua Blok',
            'jumlah'     => $jml,
        ]);
    }

    public function exportPraktekPdf($id)
    {
        helper(['date', 'text']);
        $id = (int)$id;

        $uji = $this->db->table('osce')->where('id', $id)->get()->getRowArray();
        if (!$uji) {
            return $this->response->setStatusCode(404)->setBody('Ujian OSCE tidak ditemukan');
        }

        $depId = $uji['departemen_id'] ?? $uji['departemen'] ?? 0;
        $dep = !empty($depId) ? $this->db->table('departemen')->select('nama')->where('id', (int)$depId)->get()->getRowArray() : null;
        $blok = !empty($uji['blok']) ? $this->db->table('blok')->select('nama')->where('id', (int)$uji['blok'])->get()->getRowArray() : null;
        $depNama = $dep['nama'] ?? 'Semua Departemen';
        $blokNama = $blok['nama'] ?? 'Semua Blok';

        // Fetch stations under this OSCE exam session
        $stations = $this->db->table('osce_soal s')
            ->select('s.*')
            ->where('s.osce_id', $id)
            ->orderBy('s.kode', 'asc')
            ->get()->getResultArray();

        // Fetch registered candidates
        $candidates = $this->db->table('admin_cbt ac')
            ->select('m.id, m.nim, m.nama, m.kelas')
            ->join('mahasiswa m', 'm.id = ac.id_mahasiswa', 'left')
            ->where('ac.kode', $uji['kode'])
            ->orderBy('m.nama', 'asc')
            ->get()->getResultArray();

        $logoPath = FCPATH . 'assets/img/logo_unhas.png';
        $logoData = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoData = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        $html = '
          <table width="100%" style="border-bottom:3px solid #000;margin-bottom:6px">
            <tr>
              <td width="80">
                ' . ($logoData ? '<img src="' . $logoData . '" style="height:60px">' : '') . '
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
          <div style="text-align:center;font-weight:bold;margin:8px 0 12px 0;font-size:12pt">
            LAPORAN HASIL UJIAN OSCE
          </div>

          <table width="100%" cellspacing="2" cellpadding="2" style="font-size:10pt;margin-bottom:14px;border:1px solid #ccc;padding:6px">
            <tr>
              <td style="width:16%;font-weight:bold">Ujian OSCE</td><td style="width:2%">:</td><td style="width:32%">' . esc($uji['nama_ujian'] ?? '-') . '</td>
              <td style="width:18%;font-weight:bold">Departemen</td><td style="width:2%">:</td><td>' . esc($depNama) . '</td>
            </tr>
            <tr>
              <td style="font-weight:bold">Kode Ujian</td><td>:</td><td>' . esc($uji['kode'] ?? '-') . '</td>
              <td style="font-weight:bold">Blok</td><td>:</td><td>' . esc($blokNama) . '</td>
            </tr>
            <tr>
              <td style="font-weight:bold">Tanggal</td><td>:</td><td>' . (function_exists('tgl_id') ? tgl_id($uji['tanggal']) : esc($uji['tanggal'] ?? '-')) . '</td>
              <td style="font-weight:bold">Jlh. Peserta</td><td>:</td><td>' . count($candidates) . ' Mahasiswa</td>
            </tr>
          </table>';

        if (empty($stations)) {
            $html .= '<div style="text-align:center;padding:12px;font-style:italic">Belum ada station terdaftar untuk ujian ini.</div>';
        } else {
            foreach ($stations as $st) {
                // Fetch answers for this station
                $joRows = $this->db->table('jawaban_osce jo')
                    ->select('jo.mahasiswa_id, jo.global_skor, jo.gps, jo.keterangan')
                    ->select("
                        CASE CAST(jo.gps AS UNSIGNED)
                          WHEN 0 THEN 'Tidak Lulus'
                          WHEN 1 THEN 'Borderline'
                          WHEN 2 THEN 'Lulus'
                          ELSE '-'
                        END AS gps_text
                    ", false)
                    ->where('jo.osce_id', (int)$uji['id'])
                    ->where('jo.soal_id', (int)$st['soal_id'])
                    ->get()->getResultArray();

                $scoreMap = [];
                foreach ($joRows as $jr) {
                    $scoreMap[$jr['mahasiswa_id']] = $jr;
                }
                $pengawasNama = trim((string)($st['nama_pengawas'] ?? '')) ?: ($st['nip_pengawas'] ?? '-');

                $html .= '
                <table width="100%" cellspacing="0" cellpadding="5" style="margin-top:14px;margin-bottom:6px;border:1px solid #cbd5e1;background:#f8fafc;font-size:9.5pt;border-left:4px solid #0EA5A5">
                  <tr>
                    <td style="width:50%;vertical-align:top;padding:6px 8px">
                      <div style="font-weight:bold;color:#475569;font-size:8pt;text-transform:uppercase;margin-bottom:2px">Station</div>
                      <div style="font-weight:bold;font-size:10pt;color:#0f172a">' . esc($st['nama_station']) . ' (' . esc($st['kode']) . ')</div>
                    </td>
                    <td style="width:50%;vertical-align:top;padding:6px 8px">
                      <div style="font-weight:bold;color:#475569;font-size:8pt;text-transform:uppercase;margin-bottom:2px">Penguji</div>
                      <div style="font-weight:bold;font-size:10pt;color:#0f172a">' . esc($pengawasNama) . '</div>
                    </td>
                  </tr>
                </table>
                <table width="100%" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;font-size:9.5pt;margin-bottom:12px">
                  <thead>
                    <tr style="background:#e9f2ff">
                      <th style="width:30px;text-align:center">No</th>
                      <th style="width:120px">NIM</th>
                      <th>Nama Mahasiswa</th>
                      <th style="width:80px;text-align:center">Global Skor</th>
                      <th style="width:90px;text-align:center">GPS</th>
                      <th style="width:140px">Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>';

                if (empty($candidates)) {
                    $html .= '<tr><td colspan="6" style="text-align:center;padding:8px">Belum ada peserta terdaftar.</td></tr>';
                } else {
                    $i = 1;
                    foreach ($candidates as $cand) {
                        $sc = $scoreMap[$cand['id']] ?? null;
                        $skor = ($sc && !is_null($sc['global_skor'])) ? (float)$sc['global_skor'] : '-';
                        $gpsTxt = $sc['gps_text'] ?? '-';
                        $ket = $sc['keterangan'] ?? '-';

                        $html .= '<tr>
                          <td style="text-align:center">' . $i++ . '</td>
                          <td>' . esc($cand['nim']) . '</td>
                          <td>' . esc($cand['nama']) . '</td>
                          <td style="text-align:center">' . $skor . '</td>
                          <td style="text-align:center">' . esc($gpsTxt) . '</td>
                          <td>' . esc($ket) . '</td>
                        </tr>';
                    }
                }

                $html .= '</tbody></table>';
            }
        }

        while (ob_get_level() > 0) { ob_end_clean(); }

        $opts = new \Dompdf\Options();
        $opts->set('isRemoteEnabled', true);
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($opts);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml(
            '<style>
               body{font-family:DejaVu Sans, Arial, sans-serif;font-size:10pt}
             </style>' . $html
        );
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setHeader('Pragma', 'public')
            ->setBody($dompdf->output());
    }


        // ===== FRAG: TABEL PESERTA =====
    public function pesertaTable(string $kode)
    {
        $db = $this->db;

        // 1) Peserta
        $rows = $db->table('admin_cbt p')
            ->select('p.id, p.no_ujian, m.id AS mid, m.nim, m.nama, m.kelas')
            ->join('mahasiswa m', 'm.id = p.id_mahasiswa', 'left')
            ->where('p.kode', $kode)
            ->orderBy('m.nama', 'asc')
            ->get()->getResultArray();

        // 2) Ringkasan attempt per no_ujian
        $attempts = $db->table('ujian_attempt')
            ->select('no_ujian, MAX(benar) AS benar, MAX(salah) AS salah, MAX(kosong) AS kosong, MAX(nilai) AS nilai')
            ->where('kode', $kode)
            ->groupBy('no_ujian')
            ->get()->getResultArray();

        $map = [];
        foreach ($attempts as $a) {
            $map[$a['no_ujian']] = [
                'benar'  => (int) $a['benar'],
                'salah'  => (int) $a['salah'],
                'kosong' => (int) $a['kosong'],
                'nilai'  => (int) $a['nilai'],
            ];
        }

        // 3) Gabungkan + flag has_attempt
        foreach ($rows as &$r) {
            $att = $map[$r['no_ujian']] ?? null;
            $r['has_attempt'] = $att !== null;      // << flag penting
            $r['benar']  = $att['benar']  ?? 0;
            $r['salah']  = $att['salah']  ?? 0;
            $r['kosong'] = $att['kosong'] ?? 0;
            $r['nilai']  = $att['nilai']  ?? 0;
        }
        unset($r);

        // passing grade
        $bt  = $db->table('buat_teori')->select('nilai_minimum')->where('kode', $kode)->get()->getRowArray() ?: [];
        $min = (int)($bt['nilai_minimum'] ?? 0);

        return view('\Modules\Admin\Views\ujian\partials\peserta_table', [
            'kode' => $kode,
            'rows' => $rows,
            'min'  => $min,
        ]);
    }


    public function pesertaOsceTable(string $kode)
    {
        $db = $this->db;
        $rows = $db->table('admin_cbt p')
                ->select('p.id,p.kode,o.id as station_id, m.id as mid, m.nim, m.nama, m.kelas')
                ->join('mahasiswa m', 'm.id=p.id_mahasiswa')
                    ->join('osce o', 'o.kode=p.kode')
                ->where('p.kode', $kode)
                ->orderBy('m.nama','asc')
                ->get()->getResultArray();

        return view('Modules\Admin\Views\ujian\partials\peserta_osce_table', [
            'kode' => $kode,
            'rows' => $rows,
        ]);
    }


        // ===== FRAG: MODAL LIST MAHASISWA BELUM TERDAFTAR =====
    public function pilihMahasiswa(string $kode)
    {
        $db  = $this->db;
        $q   = trim((string) $this->request->getGet('q'));
        $pg  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $per = 10;

        // subquery: yang sudah terdaftar di sesi ini
        $sub = $db->table('admin_cbt')->select('id_mahasiswa')->where('kode', $kode);

        $builder = $db->table('mahasiswa')->whereNotIn('id', $sub);
        if ($q !== '') {
            $builder->groupStart()
                    ->like('nim', $q)
                    ->orLike('nama', $q)
                    ->groupEnd();
        }

        // hitung total (pakai clone agar builder utama tidak berubah)
        $total = (clone $builder)->countAllResults();

        $rows = $builder->orderBy('nama', 'asc')
                        ->limit($per, ($pg - 1) * $per)
                        ->get()->getResultArray();

        $html = view('\Modules\Admin\Views\ujian\partials\modal_mahasiswa', [
            'kode'  => $kode,
            'rows'  => $rows,
            'q'     => $q,
            'page'  => $pg,
            'per'   => $per,
            'total' => $total,
        ]);

        // KIRIM token baru via header meski responsenya HTML
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setContentType('text/html')
            ->setBody($html);
    }

    // ===== ACTION: TAMBAH PESERTA =====
    public function pesertaAdd(string $kode, int $mahasiswaId)
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['status' => 'error', 'message' => 'Metode tidak diizinkan', 'csrf_token' => csrf_hash()]);
        }

        // pastikan ujian ada (di buat_teori atau osce)
        $exists = $this->db->query(
            'SELECT 1 FROM buat_teori WHERE kode=? UNION SELECT 1 FROM osce WHERE kode=? LIMIT 1',
            [$kode, $kode]
        )->getFirstRow();

        if (!$exists) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status'=>'error','message'=>'Ujian tidak ditemukan','csrf_token'=>csrf_hash()]);
        }

        // Cegah duplikasi peserta untuk ujian yg sama
        $dup = $this->db->table('admin_cbt')
            ->where('kode', $kode)->where('id_mahasiswa', $mahasiswaId)->countAllResults();
        if ($dup > 0) {
            return $this->response->setStatusCode(409)
                ->setJSON(['status'=>'error','message'=>'Peserta sudah terdaftar','csrf_token'=>csrf_hash()]);
        }

        // ===== Generate no_ujian: {$kode}{running number 4 digit} =====
        $PAD = 4; // ubah jadi 3/5 dst jika butuh
        try {
            // Mulai transaksi & kunci hitungan agar tidak bentrok saat konkuren
            $this->db->transException(true)->transStart();

            // Hitung jumlah peserta saat ini untuk kode tsb, kunci baris (InnoDB)
            $row = $this->db->query(
                'SELECT COUNT(*) AS c FROM admin_cbt WHERE kode = ? FOR UPDATE',
                [$kode]
            )->getRowArray();

            $seq = (int)($row['c'] ?? 0) + 1;
            $noUjian = $kode . str_pad((string)$seq, $PAD, '0', STR_PAD_LEFT);

            // Insert
            $this->db->table('admin_cbt')->insert([
                'kode'         => $kode,
                'id_mahasiswa' => $mahasiswaId,
                'no_ujian'     => $noUjian,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            $this->db->transComplete();

            return $this->response->setJSON([
                'status'     => 'ok',
                'message'    => 'Peserta ditambahkan',
                'no_ujian'   => $noUjian,
                'csrf_token' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            // Rollback otomatis karena transException(true)
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Gagal menambah peserta: '.$e->getMessage(),
                    'csrf_token' => csrf_hash()
                ]);
        }
    }


        // ===== ACTION: HAPUS PESERTA =====
        public function pesertaDel(string $kode, int $mahasiswaId)
        {
            if (!$this->request->is('post')) {
                return $this->response->setStatusCode(405)
                    ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
            }

            $this->db->table('admin_cbt')
                    ->where('kode', $kode)
                    ->where('id_mahasiswa', $mahasiswaId)
                    ->delete();

            return $this->response->setJSON(['status'=>'ok','message'=>'Peserta dihapus','csrf_token'=>csrf_hash()]);
        }

        // ----- EXPORT EXCEL (per blok) -----
        public function teoriExport()
        {
            $blokId = $this->request->getGet('blok_id');
            $tab    = $this->request->getGet('tab') ?: 'review';
            $today    = date('Y-m-d');
            $nextWeek = date('Y-m-d', strtotime('+7 days'));

            $b = $this->db->table('ujian_teori u')
                ->select('u.nama_ujian,u.tanggal,u.waktu_mulai,u.waktu_selesai,
                        d.nama as departemen,b.nama as blok,u.jml_peserta,u.review_acc')
                ->join('departemen d','d.id=u.departemen_id','left')
                ->join('blok b','b.id=u.blok_id','left');

            if ($blokId) $b->where('u.blok_id', (int)$blokId);

            switch ($tab) {
                case 'mendatang':   $b->where('u.tanggal >=', $today)->where('u.tanggal <=', $nextWeek); break;
                case 'berlangsung': $b->where('u.tanggal =', $today); break;
                case 'selesai':     $b->where('u.tanggal <', $today); break;
                default:            $b->where('u.review_acc', 0);     break;
            }

            $data = $b->orderBy('u.tanggal','desc')->get()->getResultArray();

            // Build Excel
            $ss = new Spreadsheet();
            $sheet = $ss->getActiveSheet();
            $sheet->fromArray(['Nama Ujian','Departemen','Blok','Tanggal','Mulai','Selesai','Peserta','ACC Reviewer'], null, 'A1');
            $r = 2;
            foreach ($data as $row) {
                $sheet->setCellValue("A{$r}", $row['nama_ujian']);
                $sheet->setCellValue("B{$r}", $row['departemen'] ?: 'Semua Departemen');
                $sheet->setCellValue("C{$r}", $row['blok'] ?: 'Semua Blok');
                $sheet->setCellValue("D{$r}", $row['tanggal']);
                $sheet->setCellValue("E{$r}", $row['waktu_mulai']);
                $sheet->setCellValue("F{$r}", $row['waktu_selesai']);
                $sheet->setCellValue("G{$r}", $row['jml_peserta'] ?? 0);
                $sheet->setCellValue("H{$r}", (int)$row['review_acc'] ? 'Ya' : 'Belum');
                $r++;
            }
            foreach (range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

            $filename = 'ujian_teori_'.$tab.'_'.date('Ymd_His').'.xlsx';
            $writer = new Xlsx($ss);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }
        public function teoriDelete($id = null)
        {
            $id = (int) $id;

            if (!$id) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'ID tidak valid'
                ]);
            }

            $builder = $this->db->table('buat_teori');

            // cek dulu apakah data ada
            $cek = $builder->where('id', $id)->get()->getRowArray();
            if (!$cek) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Data tidak ditemukan'
                ]);
            }

            // hapus data
            $builder->where('id', $id)->delete();

            return $this->response->setJSON([
                'status'  => 'ok',
                'message' => 'Data teori berhasil dihapus'
            ]);
        }

        /**
         * Hapus data dari tabel osce (ujian praktek)
         */
    public function osceDelete($id = null)
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['status'=>'error','message'=>'Method not allowed'])
                ->setHeader('X-CSRF-TOKEN', csrf_hash());
        }

        $id = (int) $id;
        if (! $id) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status'=>'error','message'=>'ID tidak valid'])
                ->setHeader('X-CSRF-TOKEN', csrf_hash());
        }

        $builder = $this->db->table('osce');
        $cek = $builder->where('id', $id)->get()->getRowArray();
        if (! $cek) {
            return $this->response->setStatusCode(404)
                ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan'])
                ->setHeader('X-CSRF-TOKEN', csrf_hash());
        }

        // hapus parent
        $builder->where('id', $id)->delete();

        // contoh: hapus anak jika perlu
        // $this->db->table('osce_soal')->where('osce_id', $id)->delete();
        // $this->db->table('jawaban_osce')->where('osce_id', $id)->delete();

        return $this->response
            ->setJSON(['status'=>'ok','message'=>'Data ujian praktek berhasil dihapus'])
            ->setHeader('X-CSRF-TOKEN', csrf_hash());
    }

    // =========================================================================
    // FITUR PILIH SOAL MASSAL (TEORI)
    // =========================================================================

    /**
     * Fragment: Tabel soal yang sudah terpilih untuk paket ini
     */
    public function soalList(int $paketId)
    {
        helper('text');
        $rows = $this->db->table('ujian_teori')
                ->select('id, vignette, pertanyaan')
                ->where('id_paket', $paketId)
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();

        return view('\Modules\Admin\Views\ujian\partials\soal_table', [
            'rows' => $rows
        ]);
    }

    /**
     * Fragment: Modal list soal dari Bank Soal (yang belum masuk paket ini)
     */
    public function pilihSoal(int $paketId)
    {
        helper(['text', 'url']);
        $db  = $this->db;
        $q   = trim((string) $this->request->getGet('q'));
        $pg  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $per = 10;

        $builder = $db->table('ujian_teori')
                    ->groupStart()
                        ->where('id_paket !=', $paketId)
                        ->orWhere('id_paket IS NULL')
                        ->orWhere('id_paket', 0)
                        ->orWhere('id_paket', '')
                    ->groupEnd()
                    ->where('status', 2); 

        if ($q !== '') {
            $builder->groupStart()
                    ->like('vignette', $q)
                    ->orLike('pertanyaan', $q)
                    ->orLike('register', $q)
                    ->groupEnd();
        }

        $total = (clone $builder)->countAllResults();
        $rows  = $builder->orderBy('id', 'desc')
                        ->limit($per, ($pg - 1) * $per)
                        ->get()->getResultArray();

        return view('\Modules\Admin\Views\ujian\partials\modal_soal', [
            'paketId' => $paketId,
            'rows'    => $rows,
            'q'       => $q,
            'page'    => $pg,
            'per'     => $per,
            'total'   => $total,
        ]);
    }

    /**
     * Action: Tambahkan soal ke paket
     */
    public function soalAdd(int $paketId, int $soalId)
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['status' => 'error', 'message' => 'Metode tidak diizinkan', 'csrf_token' => csrf_hash()]);
        }

        // Update id_paket pada soal terpilih
        $this->db->table('ujian_teori')
                ->where('id', $soalId)
                ->update(['id_paket' => $paketId, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON([
            'status'     => 'ok',
            'message'    => 'Soal ditambahkan ke paket',
            'csrf_token' => csrf_hash(),
        ]);
    }

    /**
     * Action: Hapus soal dari paket (set id_paket jadi null/0)
     */
    public function soalDel(int $soalId)
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)
                ->setJSON(['status' => 'error', 'message' => 'Metode tidak diizinkan', 'csrf_token' => csrf_hash()]);
        }

        $this->db->table('ujian_teori')
                ->where('id', $soalId)
                ->update(['id_paket' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON([
            'status'     => 'ok',
            'message'    => 'Soal dihapus dari paket',
            'csrf_token' => csrf_hash(),
        ]);
    }

    /**
     * Halaman Mass Assign Soal (Teori)
     */
    public function massAssignSoal(int $id)
    {
        $db = $this->db;
        $uji = $db->table('buat_teori')->where('id', $id)->get()->getRowArray();
        if (!$uji) {
            return redirect()->to(site_url('admin/ujian/teori'))->with('error', 'Sesi ujian tidak ditemukan');
        }

        $q      = trim((string)$this->request->getGet('q'));
        $depId  = $this->request->getGet('departemen_id');
        $blokId = $this->request->getGet('blok_id');
        $page   = max(1, (int)($this->request->getGet('page') ?? 1));
        $per    = 20;

        $builder = $db->table('ujian_teori t')
            ->select('t.id, t.register, t.vignette, t.pertanyaan, t.status, t.id_paket')
            ->where('t.status', 2); // Hanya yang sudah PUBLISH

        if ($q !== '') {
            $builder->groupStart()
                ->like('t.register', $q)
                ->orLike('t.vignette', $q)
                ->orLike('t.pertanyaan', $q)
                ->groupEnd();
        }
        if ($depId) $builder->where('t.departemen', $depId);
        if ($blokId) $builder->where('t.blok', $blokId);

        $total = (clone $builder)->countAllResults();
        $rows  = $builder->orderBy('t.id', 'DESC')
                        ->limit($per, ($page - 1) * $per)
                        ->get()->getResultArray();

        $deps = $db->table('departemen')->select('id,nama')->orderBy('nama', 'ASC')->get()->getResultArray();
        $blks = $db->table('blok')->select('id,nama')->orderBy('nama', 'ASC')->get()->getResultArray();

        return view('\Modules\Admin\Views\ujian\mass_assign_soal', [
            'title'      => 'Mass Assign Soal: ' . $uji['nama'],
            'menuActive' => 'ujian_teori',
            'uji'        => $uji,
            'rows'       => $rows,
            'departemen' => $deps,
            'blok'       => $blks,
            'filters'    => ['q' => $q, 'depId' => $depId, 'blokId' => $blokId],
            'page'       => $page,
            'per'        => $per,
            'total'      => $total,
            'pages'      => max(1, (int)ceil($total / $per))
        ]);

    }
    /**
     * Simpan Mass Assign Soal (Surgical Sync)
     */
    public function massAssignSoalSave(int $id)
    {
        if (!$this->request->is('post')) return redirect()->back();

        $visibleIds = $this->request->getPost('visible_ids') ?: []; // semua ID yang tampil di tabel tadi
        $soalIds    = $this->request->getPost('soal_ids') ?: [];    // ID yang diceklis

        $db = $this->db;
        $db->transStart();

        // 1. Lepas tautan soal yang SEDANG TAMPIL tapi TIDAK DICEKLIS (hanya jika sebelumnya milik paket ini)
        $uncheckIds = array_diff($visibleIds, $soalIds);
        if (!empty($uncheckIds)) {
            $db->table('ujian_teori')
            ->where('id_paket', $id)
            ->whereIn('id', $uncheckIds)
            ->update(['id_paket' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        // 2. Tautkan soal yang DICEKLIS
        if (!empty($soalIds)) {
            $db->table('ujian_teori')
            ->whereIn('id', $soalIds)
            ->update(['id_paket' => $id, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Gagal menyimpan perubahan');
        }

        return redirect()->to(site_url('admin/ujian/teori/detail/' . $id))
                        ->with('success', 'Perubahan daftar soal berhasil disimpan');
    }
}