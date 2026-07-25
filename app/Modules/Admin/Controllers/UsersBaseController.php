<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Admin\Models\UsersModel;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

abstract class UsersBaseController extends BaseController
{
    protected int $ROLE_ID;           // override di child
    protected string $TITLE;          // override di child
    protected string $routeBase;      // override di child, ex: 'master/pengguna-dosen'
    protected string $menuActive;     // override di child, ex: 'pengguna-dosen'

    protected UsersModel $M;

    public function __construct()
    {
        $this->M = new UsersModel();
    }

    public function index()
    {
        $q   = $this->request->getGet('q');
        $frag= $this->request->getGet('frag');

        $rows = $this->M->listByRole($this->ROLE_ID, $q);
        
        // Ambil data untuk dropdown
        $db = \Config\Database::connect();
        $bloks = $db->table('blok')->select('id, nama')->orderBy('nama', 'asc')->get()->getResultArray();
        $deps  = $db->table('departemen')->select('id, nama')->orderBy('nama', 'asc')->get()->getResultArray();

        $data = [
            'title'      => $this->TITLE,
            'routeBase'  => $this->routeBase,
            'menuActive' => $this->menuActive ?? '',
            'rows'       => $rows,
            'q'          => $q,
            'bloks'      => $bloks,
            'deps'       => $deps,
        ];

        if ($frag === 'list') {
            return view('\Modules\Admin\Views\users\partials\user_table', $data);
        }
        return view('\Modules\Admin\Views\users\index', $data);
    }

    public function get($id)
    {
        $row = $this->M->getInRole((int)$id, $this->ROLE_ID);
        if (!$row) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan']);
        }
        return $this->response->setJSON(['status'=>'ok','data'=>$row]);
    }

    public function save()
    {
        $id = (int)($this->request->getPost('id') ?? 0);

        $data = [
            'name'       => trim((string)$this->request->getPost('name')),
            'username'   => trim((string)$this->request->getPost('username')),
            'email'      => trim((string)$this->request->getPost('email')),
            'blok'       => (string)$this->request->getPost('blok'),
            'departemen' => (string)$this->request->getPost('departemen'),
            'old'        => (int)($this->request->getPost('old') ?? 0),
            'kordinator' => (string)$this->request->getPost('kordinator'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Handle Avatar Upload
        $file = $this->request->getFile('avatar_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/avatars', $newName);
            $data['thumb_avatar'] = base_url('uploads/avatars/' . $newName);
        }

        // password hanya jika diisi pada create/edit
        $password = (string)$this->request->getPost('password');
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($id > 0) {
            // pastikan user berada di role yg benar
            if (!$this->M->getInRole($id, $this->ROLE_ID)) {
                return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                    ->setJSON(['status'=>'error','message'=>'Role tidak sesuai', 'csrf_token' => csrf_hash()]);
            }
            if ($this->M->update($id, $data)) {
                return $this->response->setJSON(['status'=>'ok','message'=>'Data berhasil diperbarui', 'csrf_token' => csrf_hash()]);
            }
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['status'=>'error','message'=>$this->M->errors() ?: 'Gagal update', 'csrf_token' => csrf_hash()]);
        }

        // CREATE
        $data['role_id'] = $this->ROLE_ID;
        $data['created_at'] = date('Y-m-d H:i:s');
        if ($password === '') {
            // default password saat create jika tidak diisi
            $data['password'] = password_hash('123456', PASSWORD_DEFAULT);
        }
        $newId = $this->M->insert($data, true);
        if ($newId) {
            return $this->response->setJSON(['status'=>'ok','id'=>$newId,'message'=>'Pengguna berhasil dibuat', 'csrf_token' => csrf_hash()]);
        }
        return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
            ->setJSON(['status'=>'error','message'=>$this->M->errors() ?: 'Gagal create', 'csrf_token' => csrf_hash()]);
    }

    public function delete($id)
    {
        $id = (int)$id;
        if (!$this->M->getInRole($id, $this->ROLE_ID)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan']);
        }
        $this->M->delete($id);
        return $this->response->setJSON(['status'=>'ok']);
    }

    public function resetPassword($id)
    {
        $id = (int)$id;
        if (!$this->M->getInRole($id, $this->ROLE_ID)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['status'=>'error','message'=>'Data tidak ditemukan']);
        }
        $new = '123456'; // bisa diganti generator acak
        $ok  = $this->M->update($id, ['password'=>password_hash($new, PASSWORD_DEFAULT)]);
        if ($ok) {
            return $this->response->setJSON(['status'=>'ok','new_password'=>$new]);
        }
        return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
            ->setJSON(['status'=>'error','message'=>'Gagal reset password']);
    }

    public function export()
    {
        $q     = $this->request->getGet('q');
        $rows  = $this->M->listByRole($this->ROLE_ID, $q);

        // mapping role teks
        $roleMap = [0=>'Administrator', 1=>'Dosen', 2=>'Manajemen', 4=>'Reviewer', 6=>'Operator Ujian'];
        $roleTxt = $roleMap[$this->ROLE_ID] ?? ('Role-'.$this->ROLE_ID);

        // siapkan data kolom (jangan export password)
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'ID'         => (int)$r['id'],
                'Nama'       => (string)$r['name'],
                'Username'   => (string)$r['username'],
                'Email'      => (string)($r['email'] ?? ''),
                'Role'       => $roleTxt,
                'Blok'       => (string)($r['blok_nama'] ?? $r['blok'] ?? ''),
                'Departemen' => (string)($r['dep_nama'] ?? $r['departemen'] ?? ''),
                'Kordinator' => (string)($r['kordinator'] ?? ''),
                'Umur'       => (int)($r['old'] ?? 0),
                'Created At' => (string)($r['created_at'] ?? ''),
                'Updated At' => (string)($r['updated_at'] ?? ''),
            ];
        }

        $slug = strtolower(str_replace(' ', '_', $roleTxt));
        $fname = "users_{$slug}_" . date('Ymd_His');

        // Jika PhpSpreadsheet ada -> XLSX, kalau tidak -> CSV
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $ss    = new Spreadsheet();
            $sheet = $ss->getActiveSheet();

            // Header
            $headers = array_keys($data[0] ?? [
                'ID','Nama','Username','Email','Role','Blok','Departemen','Kordinator','Umur','Created At','Updated At'
            ]);
            $sheet->fromArray($headers, null, 'A1');

            // Data rows (urut sesuai header)
            $rowsArr = [];
            foreach ($data as $record) {
                $rowsArr[] = array_map(fn($h) => $record[$h] ?? '', $headers);
            }
            if (!empty($rowsArr)) {
                $sheet->fromArray($rowsArr, null, 'A2');
            }

            // Bold header + autosize
            $lastCol = Coordinate::stringFromColumnIndex(count($headers)); // e.g. 'K'
            $sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setBold(true);
            for ($i = 1; $i <= count($headers); $i++) {
                $col = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Output
            $this->response->setHeader('Content-Type','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->response->setHeader('Content-Disposition','attachment; filename="'.$fname.'.xlsx"');
            $this->response->setHeader('Cache-Control','max-age=0');

            $writer = new Xlsx($ss);
            ob_start();
            $writer->save('php://output');
            $output = ob_get_clean();
            return $this->response->setBody($output);
        }
    }
}
