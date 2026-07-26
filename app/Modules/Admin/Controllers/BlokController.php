<?php
// app/Modules/Admin/Controllers/BlokController.php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class BlokController extends BaseController
{
    // JANGAN redeclare $db / $session (sudah ada di BaseController)

    public function index()
    {
        $q    = trim((string) $this->request->getGet('q'));
        $page = max(1, (int)($this->request->getGet('page') ?: 1));
        $per  = 20;

        // total
        $tb = $this->db->table('blok');
        if ($q !== '') {
            $tb->like('nama', $q);
        }
        $total = (int) ((clone $tb)->select('COUNT(*) AS c', false)->get()->getRow('c') ?? 0);

        // rows
        $tb2 = $this->db->table('blok');
        if ($q !== '') {
            $tb2->like('nama', $q);
        }
        $rows = $tb2->orderBy('nama', 'ASC')
            ->limit($per, ($page - 1) * $per)
            ->get()->getResultArray();

        $menuActive = 'master_blok';
        $data       = compact('rows', 'total', 'page', 'per', 'q', 'menuActive');

        // fragment tabel (AJAX)
        if ($this->request->getGet('frag') === 'list' || $this->request->isAJAX()) {
            return view('\Modules\Admin\Views\ref\partials\blok_table', $data);
        }

        // halaman penuh
        return view('\Modules\Admin\Views\ref\blok_index', $data);
    }

    public function get($id = null)
    {
        $id  = (int) $id;
        $row = $this->db->table('blok')->where('id', $id)->get()->getRowArray();

        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'     => 'error',
                'message'    => 'Data tidak ditemukan',
                'csrf_token' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status'     => 'ok',
            'data'       => $row,
            'csrf_token' => csrf_hash(),
        ]);
    }

    public function save()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON([
                'status'     => 'error',
                'message'    => 'Method tidak diizinkan',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $id   = (int) $this->request->getPost('id');
        $nama = trim((string) $this->request->getPost('nama'));

        if ($nama === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'     => 'error',
                'message'    => 'Nama wajib diisi',
                'csrf_token' => csrf_hash(),
            ]);
        }

        // cek unik nama (case-insensitive)
        $exists = $this->db->table('blok')
            ->select('COUNT(*) AS c', false)
            ->groupStart()
                ->where('LOWER(nama)', mb_strtolower($nama))
            ->groupEnd()
            ->where($id ? 'id !=' : 'id >=', $id ?: 0)
            ->get()->getRow('c');

        if ((int)$exists > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'     => 'error',
                'message'    => 'Nama sudah ada',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $now = date('Y-m-d H:i:s');

        if ($id) {
            $this->db->table('blok')->where('id', $id)->update([
                'nama'       => $nama,
                'updated_at' => $now,
            ]);
        } else {
            $this->db->table('blok')->insert([
                'nama'       => $nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $this->db->insertID();
        }

        return $this->response->setJSON([
            'status'     => 'ok',
            'id'         => $id,
            'csrf_token' => csrf_hash(),
        ]);
    }

    public function delete($id = null)
    {
        $id = (int) $id;
        if (! $id) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'     => 'error',
                'message'    => 'ID tidak valid',
                'csrf_token' => csrf_hash(),
            ]);
        }

        $this->db->table('blok')->where('id', $id)->delete();

        return $this->response->setJSON([
            'status'     => 'ok',
            'csrf_token' => csrf_hash(),
        ]);
    }
}
