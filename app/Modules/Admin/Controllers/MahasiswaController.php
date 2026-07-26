<?php
// app/Modules/Admin/Controllers/MahasiswaController.php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class MahasiswaController extends BaseController
{
    public function index()
    {
        $q    = trim((string) $this->request->getGet('q'));
        $page = max(1, (int) ($this->request->getGet('page') ?: 1));
        $per  = 20;

        // total
        $tb = $this->db->table('mahasiswa');
        if ($q !== '') {
            $tb->groupStart()
                   ->like('nim', $q)
                   ->orLike('nama', $q)
                   ->orLike('kelas', $q)
               ->groupEnd();
        }
        $total = (int) ((clone $tb)->select('COUNT(*) AS c', false)->get()->getRow('c') ?? 0);

        // rows
        $tb2 = $this->db->table('mahasiswa');
        if ($q !== '') {
            $tb2->groupStart()
                    ->like('nim', $q)
                    ->orLike('nama', $q)
                    ->orLike('kelas', $q)
                ->groupEnd();
        }
        $rows = $tb2->orderBy('nim','ASC')
                    ->limit($per, ($page-1)*$per)
                    ->get()->getResultArray();

        $menuActive = 'master_mahasiswa';
        $data     = compact('rows','total','page','per','q','menuActive');

        if ($this->request->getGet('frag') === 'list' || $this->request->isAJAX()) {
            return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
                                   ->setBody(view('\Modules\Admin\Views\ref\partials\mahasiswa_table', $data));
        }

        return view('\Modules\Admin\Views\ref\mahasiswa_index', $data);
    }

    public function get($id)
    {
        $id  = (int) $id;
        $row = $this->db->table('mahasiswa')->where('id',$id)->get()->getRowArray();
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
        $nim  = strtoupper(trim((string)$this->request->getPost('nim')));
        $nama = trim((string)$this->request->getPost('nama'));
        $kelas= trim((string)$this->request->getPost('kelas'));
        $angk = trim((string)$this->request->getPost('old_angkatan'));

        if ($nim === '' || $nama === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'NIM & Nama wajib diisi','csrf_token'=>csrf_hash()
            ]);
        }

        // cek unik NIM (abaikan baris sendiri saat edit)
        $b = $this->db->table('mahasiswa')->select('COUNT(*) AS c', false)->where('nim', $nim);
        if ($id) $b->where('id !=', $id);
        $exists = (int) ($b->get()->getRow('c') ?? 0);
        if ($exists > 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'=>'error','message'=>'NIM sudah digunakan','csrf_token'=>csrf_hash()
            ]);
        }

        $now = date('Y-m-d H:i:s');

        if ($id) {
            $this->db->table('mahasiswa')->where('id',$id)->update([
                'nim'=>$nim, 'nama'=>$nama, 'kelas'=>$kelas, 'old_angkatan'=>$angk, 'updated_at'=>$now
            ]);
        } else {
            $this->db->table('mahasiswa')->insert([
                'nim'=>$nim, 'nama'=>$nama, 'kelas'=>$kelas, 'old_angkatan'=>$angk,
                'created_at'=>$now, 'updated_at'=>$now
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
        $this->db->table('mahasiswa')->where('id',$id)->delete();

        return $this->response->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }
}
