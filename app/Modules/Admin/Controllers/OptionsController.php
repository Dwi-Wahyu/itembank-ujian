<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class OptionsController extends BaseController
{
    public function departemen()
    {
       
        $q  = trim((string)$this->request->getGet('q'));
        $id = (int)($this->request->getGet('id') ?? 0);

        $b = $this->db->table('departemen')->select('id, nama');

        if ($id > 0) {
            $b->where('id', $id);
        } elseif ($q !== '') {
            $b->like('nama', $q);
        } else {
            $b->limit(20);
        }

        $rows = $b->orderBy('nama','asc')->get()->getResultArray();
        $out  = ['results' => array_map(fn($r)=>[
            'id'   => (int)$r['id'],
            'text' => (string)$r['nama'],
        ], $rows)];

        return $this->response->setJSON($out);
    }
}
