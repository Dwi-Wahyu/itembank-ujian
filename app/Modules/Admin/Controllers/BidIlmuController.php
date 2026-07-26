<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class BidIlmuController extends BaseController
{
  
    public function __construct()
    {
       
    }

    /** LIST PAGE (dan fragment list ketika ?frag=list) */
   public function index()
{
    $q    = trim((string) $this->request->getGet('q'));
    $page = max(1, (int) ($this->request->getGet('page') ?: 1));
    $per  = 20;

    $tb   = $this->db->table('bid_ilmu');

    // total
    if ($q !== '') {
        $tb->groupStart()->like('kode', $q)->orLike('nama', $q)->groupEnd();
    }
    $total = (clone $tb)->select('COUNT(*) AS c')->get()->getRow('c') ?? 0;

    // rows
    $tb2 = $this->db->table('bid_ilmu');
    if ($q !== '') {
        $tb2->groupStart()->like('kode', $q)->orLike('nama', $q)->groupEnd();
    }
    $rows = $tb2->orderBy('kode', 'ASC')
        ->limit($per, ($page - 1) * $per)
        ->get()->getResultArray();

    // flag aktif untuk menu
    $menuActive = 'master_bid_ilmu';

    $data = compact('rows', 'total', 'page', 'per', 'q', 'menuActive');

    // jika fragment (partial tabel)
    if ($this->request->getGet('frag') === 'list') {
        return view('\Modules\Admin\Views\ref\partials\bid_ilmu_table', $data);
    }

    // halaman penuh
    return view('\Modules\Admin\Views\ref\bid_ilmu_index', $data);
}

    /** Ambil 1 baris (untuk Edit) */
    public function get($id)
    {
        $row = $this->db->table('bid_ilmu')->where('id', (int)$id)->get()->getRowArray();
        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'csrf_token' => csrf_hash(),
            ]);
        }
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status' => 'ok', 'data' => $row, 'csrf_token' => csrf_hash()]);
    }

    /** Create */
    public function create()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan']);
        }

        $kode = trim((string)$this->request->getPost('kode'));
        $nama = trim((string)$this->request->getPost('nama'));

        // validasi
        if ($kode === '' || $nama === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Kode dan Nama wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }
        // unik kode
        $ada = $this->db->table('bid_ilmu')->where('kode', $kode)->countAllResults();
        if ($ada) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Kode sudah digunakan','csrf_token'=>csrf_hash()
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('bid_ilmu')->insert([
            'kode'       => $kode,
            'nama'       => $nama,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','message'=>'Data ditambahkan','csrf_token'=>csrf_hash()]);
    }

    /** Update */
    public function update($id)
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan']);
        }

        $id   = (int)$id;
        $kode = trim((string)$this->request->getPost('kode'));
        $nama = trim((string)$this->request->getPost('nama'));

        if ($kode === '' || $nama === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Kode dan Nama wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }

        // unik kode (kecuali dirinya)
        $ada = $this->db->table('bid_ilmu')
            ->where('kode', $kode)->where('id !=', $id)->countAllResults();
        if ($ada) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Kode sudah digunakan','csrf_token'=>csrf_hash()
            ]);
        }

        $this->db->table('bid_ilmu')->where('id',$id)->update([
            'kode'       => $kode,
            'nama'       => $nama,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','message'=>'Perubahan disimpan','csrf_token'=>csrf_hash()]);
    }

    /** Delete */
    public function delete($id)
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan']);
        }
        $this->db->table('bid_ilmu')->where('id', (int)$id)->delete();
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','message'=>'Data dihapus','csrf_token'=>csrf_hash()]);
    }
}
