<?php
// app/Modules/Admin/Controllers/DosenController.php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class DosenController extends BaseController
{
    public function index()
    {
        $q    = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) ($this->request->getGet('page') ?: 1));
        $per  = 20;

        // total
        $tb = $this->db->table('dosen');
        if ($q !== '') {
            $tb->groupStart()->like('nip', $q)->orLike('nama', $q)->groupEnd();
        }
        $total = (int) ((clone $tb)->select('COUNT(*) AS c', false)->get()->getRow('c') ?? 0);

        // rows
        $tb2 = $this->db->table('dosen');
        if ($q !== '') {
            $tb2->groupStart()->like('nip', $q)->orLike('nama', $q)->groupEnd();
        }
        $rows = $tb2->orderBy('nama','ASC')
                    ->limit($per, ($page-1)*$per)
                    ->get()->getResultArray();

        $menuActive = 'master_dosen';
        $data     = compact('rows','total','page','per','q','menuActive');

        // fragment (AJAX)
        if ($this->request->getGet('frag') === 'list' || $this->request->isAJAX()) {
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setBody(view('\Modules\Admin\Views\ref\partials\dosen_table', $data));
        }

        // halaman penuh
        return view('\Modules\Admin\Views\ref\dosen_index', $data);
    }

    public function get($id)
    {
        $id  = (int) $id;
        $row = $this->db->table('dosen')->where('id',$id)->get()->getRowArray();
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
        $nip  = strtoupper(trim((string)$this->request->getPost('nip')));
        $nama = trim((string)$this->request->getPost('nama'));

        if ($nip === '' || $nama === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'NIP & Nama wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }

        // cek unik NIP (abaikan baris sendiri saat edit)
        $b = $this->db->table('dosen')->select('COUNT(*) AS c', false)->where('nip', $nip);
        if ($id) $b->where('id !=', $id);
        $exists = (int) ($b->get()->getRow('c') ?? 0);
        if ($exists > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'NIP sudah digunakan','csrf_token'=>csrf_hash()
            ]);
        }

        $now = date('Y-m-d H:i:s');

        if ($id) {
            $this->db->table('dosen')->where('id',$id)->update([
                'nip'=>$nip, 'nama'=>$nama, 'updated_at'=>$now
            ]);
        } else {
            $this->db->table('dosen')->insert([
                'nip'=>$nip, 'nama'=>$nama, 'created_at'=>$now, 'updated_at'=>$now
            ]);
            $id = (int) $this->db->insertID();
        }

        return $this->response->setJSON(['status'=>'ok','id'=>$id,'csrf_token'=>csrf_hash()]);
    }

    public function delete($id)
    {
        $id = (int) $id;
        if (! $id) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'ID tidak valid','csrf_token'=>csrf_hash()
            ]);
        }

        $this->db->table('dosen')->where('id',$id)->delete();
        return $this->response->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }
}
