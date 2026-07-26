<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Admin\Models\AspekModel;
use Modules\Auth\Libraries\Auth;

class Aspek extends BaseController
{
    protected $db;
    protected $aspek;

    public function __construct()
    {
        $this->db    = db_connect();
        $this->aspek = new AspekModel();
    }

    public function index()
    {
        // dropdown (opsional mengikuti modul lain)
        $data['komp']       = $this->db->table('kom_utama')->orderBy('nama')->get()->getResultArray();
        $data['sakit']      = $this->db->table('penyakit')->orderBy('nama')->get()->getResultArray();
        $data['bidang']     = $this->db->table('bid_ilmu')->orderBy('nama')->get()->getResultArray();

        $data += $this->buildQuery();
        return view('\Modules\Admin\Views\aspek\aspek_list', $data);
    }

    public function table()
    {
        $data = $this->buildQuery();
        return view('\Modules\Admin\Views\aspek\partials\aspek_table', $data);
    }

    private function buildQuery(): array
    {
        $page = max(1, (int)($this->request->getGet('page') ?: 1));
        $per  = max(5, (int)($this->request->getGet('per')  ?: 10));

        $b = $this->db->table('aspek a')
            ->select("a.*, up.register AS soal_register");

        // join register soal (unjuk di list)
        $b->join('ujian_praktek up', 'up.id = a.soal_id', 'left');

        // filter
        if ($q = trim((string)$this->request->getGet('q'))) {
            $b->groupStart()
              ->like('a.aspek', $q)
              ->orLike('a.keterangan', $q)
              ->orLike('up.register', $q)
              ->groupEnd();
        }
        if ($sid = (int)$this->request->getGet('soal_id')) $b->where('a.soal_id', $sid);
        if ($t1  = $this->request->getGet('t1'))          $b->where('a.t1', $t1);
        if ($t2  = $this->request->getGet('t2'))          $b->where('a.t2', $t2);
        if ($t3  = $this->request->getGet('t3'))          $b->where('a.t3', $t3);

        $total = (clone $b)->countAllResults(false);
        $rows  = $b->orderBy('a.created_at','DESC')->limit($per, ($page-1)*$per)->get()->getResultArray();

        return compact('rows','page','per','total');
    }

    public function add($id_soal)
    {
        $data = [
            'mode'       => 'add',
             'id_soal'       => $id_soal,
            'row'        => null,
            'komp'       => $this->db->table('kom_utama')->orderBy('nama')->get()->getResultArray(),
            'sakit'      => $this->db->table('penyakit')->orderBy('nama')->get()->getResultArray(),
            'bidang'     => $this->db->table('bid_ilmu')->orderBy('nama')->get()->getResultArray(),
            'soal'       => $this->db->table('ujian_praktek')->select('id,register')->orderBy('created_at','DESC')->get()->getResultArray(),
        ];
        return view('\Modules\Admin\Views\aspek\aspek_form', $data);
    }

 public function create()
{
    if (!$this->request->is('post')) return $this->fail405();
    $uid = (int)(Auth::user()['id'] ?? 0);

    $rules = [
        'soal_id' => 'required|is_natural_no_zero',
        'aspek'   => 'required|min_length[3]',
    ];
    if (! $this->validate($rules)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'=>'error','message'=> implode("\n",$this->validator->getErrors()), 'csrf_token'=>csrf_hash()
        ]);
    }

    // multiple files
    $fileNames = [];
    $files = $this->request->getFiles();
    if (isset($files['files'])) {
        foreach ($files['files'] as $f) {
            if ($f->isValid() && !$f->hasMoved()) {
                $name = $f->getRandomName();
                $f->move('uploads/aspek', $name);
                $fileNames[] = $name;
            }
        }
    }

    $data = [
        'soal_id'   => (int)$this->request->getPost('soal_id'),
        't1'        => $this->request->getPost('t1') ?: null,
        't2'        => $this->request->getPost('t2') ?: null,
        't3'        => $this->request->getPost('t3') ?: null,
        'aspek'     => trim((string)$this->request->getPost('aspek')),
        'keterangan'=> (string)$this->request->getPost('keterangan'),
        'file'      => $fileNames ? json_encode($fileNames) : null,
        'insert_by' => $uid,
    ];
    $this->aspek->insert($data);

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','id'=>$this->aspek->getInsertID(),'csrf_token'=>csrf_hash()]);
}


    public function edit($id=null)
    {
        $id  = (int)$id;
        $row = $this->aspek->find($id);
        if (!$row) return redirect()->to(site_url('admin/aspek'))->with('error','Data tidak ditemukan');

        $data = [
            'mode'   => 'edit',
            'row'    => $row,
            'komp'   => $this->db->table('kom_utama')->orderBy('nama')->get()->getResultArray(),
            'sakit'  => $this->db->table('penyakit')->orderBy('nama')->get()->getResultArray(),
            'bidang' => $this->db->table('bid_ilmu')->orderBy('nama')->get()->getResultArray(),
            'soal'   => $this->db->table('ujian_praktek')->select('id,register')->orderBy('created_at','DESC')->get()->getResultArray(),
        ];
        return view('\Modules\Admin\Views\aspek\aspek_form', $data);
    }
public function update($id=null)
{
    if (!$this->request->is('post')) return $this->fail405();
    $id = (int)$id; if ($id<=0) return $this->fail422('ID tidak valid');

    $rules = [
        'soal_id' => 'required|is_natural_no_zero',
        'aspek'   => 'required|min_length[3]',
    ];
    if (! $this->validate($rules)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status'=>'error','message'=> implode("\n",$this->validator->getErrors()), 'csrf_token'=>csrf_hash()
        ]);
    }

    $row = $this->aspek->find($id);
    if (!$row) {
        return $this->response->setStatusCode(404)->setJSON([
            'status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()
        ]);
    }

    // ambil list lama (dipertahankan), lalu gabung dengan yang baru
    $oldFiles = [];
    if (!empty($row['file'])) {
        $oldFiles = json_decode($row['file'], true);
        if (!is_array($oldFiles)) $oldFiles = [$row['file']];
    }

    $newFiles = [];
    $files = $this->request->getFiles();
    if (isset($files['files'])) {
        foreach ($files['files'] as $f) {
            if ($f->isValid() && !$f->hasMoved()) {
                $name = $f->getRandomName();
                $f->move('uploads/aspek', $name);
                $newFiles[] = $name;
            }
        }
    }
    $allFiles = array_values(array_filter(array_merge($oldFiles, $newFiles)));

    $data = [
        'soal_id'   => (int)$this->request->getPost('soal_id'),
        't1'        => $this->request->getPost('t1') ?: null,
        't2'        => $this->request->getPost('t2') ?: null,
        't3'        => $this->request->getPost('t3') ?: null,
        'aspek'     => trim((string)$this->request->getPost('aspek')),
        'keterangan'=> (string)$this->request->getPost('keterangan'),
        'file'      => $allFiles ? json_encode($allFiles) : null,
    ];
    $this->aspek->update($id, $data);

    return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
        ->setJSON(['status'=>'ok','id'=>$id,'csrf_token'=>csrf_hash()]);
}


    public function delete($id=null)
    {
        if (!$this->request->is('post')) return $this->fail405();
        $id = (int)$id;
        if ($id<=0) return $this->fail422('ID tidak valid');

        $this->aspek->delete($id);

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON(['status'=>'ok','csrf_token'=>csrf_hash()]);
    }

    public function get($id=null)
    {
        $id  = (int)$id;
        $row = $this->aspek->find($id);
        if (!$row) return $this->response->setStatusCode(404)->setJSON(['status'=>'error','message'=>'Data tidak ditemukan','csrf_token'=>csrf_hash()]);
        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON(['status'=>'ok','data'=>$row,'csrf_token'=>csrf_hash()]);
    }

    private function fail405()
    {
        return $this->response->setStatusCode(405)->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }
    private function fail422($m)
    {
        return $this->response->setStatusCode(422)->setJSON(['status'=>'error','message'=>$m,'csrf_token'=>csrf_hash()]);
    }
}
