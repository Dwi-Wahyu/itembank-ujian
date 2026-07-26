<?php
// app/Modules/Admin/Controllers/KomUtamaController.php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class KomUtamaController extends BaseController
{
    public function index()
    {
        $q    = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) ($this->request->getGet('page') ?: 1));
        $per  = 20;

        // total
        $tb = $this->db->table('kom_utama');
        if ($q !== '') {
            $tb->groupStart()->like('kode', $q)->orLike('nama', $q)->groupEnd();
        }
        $total = (int) ((clone $tb)->select('COUNT(*) AS c', false)->get()->getRow('c') ?? 0);

        // rows
        $tb2 = $this->db->table('kom_utama');
        if ($q !== '') {
            $tb2->groupStart()->like('kode', $q)->orLike('nama', $q)->groupEnd();
        }
        $rows = $tb2->orderBy('kode','ASC')
                    ->limit($per, ($page-1)*$per)
                    ->get()->getResultArray();

        $menuActive = 'master_kom_utama';
        $data     = compact('rows','total','page','per','q','menuActive');

        if ($this->request->getGet('frag') === 'list' || $this->request->isAJAX()) {
            return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
                                   ->setBody(view('\Modules\Admin\Views\ref\partials\kom_utama_table', $data));
        }

        return view('\Modules\Admin\Views\ref\kom_utama_index', $data);
    }

    public function get($id)
    {
        $id  = (int) $id;
        $row = $this->db->table('kom_utama')->where('id',$id)->get()->getRowArray();
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()
            ]);
        }
        return $this->response->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
    }

    public function save()
    {
        if (! $this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON([
                'status'=>'error','message'=>'Method tidak diizinkan','csrf_token'=>csrf_hash()
            ]);
        }

        $id   = (int) $this->request->getPost('id');
        $kode = strtoupper(trim((string)$this->request->getPost('kode')));
        $nama = trim((string)$this->request->getPost('nama'));

        if ($kode === '' || $nama === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Kode & Nama wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }

        // cek unik (case-insensitive), abaikan baris sendiri saat update
        $builder = $this->db->table('kom_utama')
            ->select('COUNT(*) AS c', false)
            ->groupStart()
                ->where('LOWER(kode)', mb_strtolower($kode))
                ->orWhere('LOWER(nama)', mb_strtolower($nama))
            ->groupEnd();
        if ($id) $builder->where('id !=', $id);
        $exists = (int) ($builder->get()->getRow('c') ?? 0);

        if ($exists > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'Kode atau Nama sudah digunakan','csrf_token'=>csrf_hash()
            ]);
        }

        $now = date('Y-m-d H:i:s');

        if ($id) {
            $this->db->table('kom_utama')->where('id',$id)->update([
                'kode'=>$kode,'nama'=>$nama,'updated_at'=>$now
            ]);
        } else {
            $this->db->table('kom_utama')->insert([
                'kode'=>$kode,'nama'=>$nama,'created_at'=>$now,'updated_at'=>$now
            ]);
            $id = (int) $this->db->insertID();
        }

        return $this->response->setJSON([
            'status'=>'ok','id'=>$id,'csrf_token'=>csrf_hash()
        ]);
    }

    public function delete($id)
    {
        $id = (int) $id;
        if (! $id) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'ID tidak valid','csrf_token'=>csrf_hash()
            ]);
        }
        $this->db->table('kom_utama')->where('id',$id)->delete();

        return $this->response->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }
}
