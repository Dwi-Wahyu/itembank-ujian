<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

use Modules\Auth\Libraries\Auth;

class DashboardController extends BaseController
{
    public function index()
    {
       

        // Filter URL
       $idDepartemen = (int) ($this->request->getGet('id_departemen') ?? 0);
        $dateStartRaw = (string) ($this->request->getGet('start_date') ?? '');
        $dateEndRaw   = (string) ($this->request->getGet('end_date')   ?? '');

        // Normalisasi tanggal (YYYY-MM-DD)
        $dateStart = $dateStartRaw ? date('Y-m-d', strtotime($dateStartRaw)) : '';
        $dateEnd   = $dateEndRaw   ? date('Y-m-d', strtotime($dateEndRaw))   : '';

        // ===== Auth & Role Filter =====
         $uid    = (int) (Auth::user()['id']      ?? 0 );
        $roleId = (int) (Auth::user()['role_id'] ?? -1 );

        // helper: filter role
        $applyRoleFilterTeori = function($b) use ($roleId, $uid) {
            if ($roleId === 1) {                // Dosen
                $b->where('t.insert_by', $uid);
            } elseif ($roleId === 4) {          // Reviewer
                $b->where('t.revisi_by', $uid);
            }
            return $b;
        };
        $applyRoleFilterPraktek = function($b) use ($roleId, $uid) {
            if ($roleId === 1) {                // Dosen
                $b->where('p.insert_by', $uid);
            } elseif ($roleId === 4) {          // Reviewer
                $b->where('p.revisi_by', $uid);
            }
            return $b;
        };

        // helper: filter tanggal (created_at between)
        $applyDateRange = function($b, string $alias) use ($dateStart, $dateEnd) {
            if ($dateStart && $dateEnd) {
                $b->where("$alias.created_at >=", $dateStart.' 00:00:00')
                  ->where("$alias.created_at <=", $dateEnd  .' 23:59:59');
            } elseif ($dateStart) {
                $b->where("$alias.created_at >=", $dateStart.' 00:00:00');
            } elseif ($dateEnd) {
                $b->where("$alias.created_at <=", $dateEnd  .' 23:59:59');
            }
            return $b;
        };

        // =========================
        // AGREGAT KARTU
        // =========================
        // Teori
        $qT = $this->db->table('ujian_teori t')->select("
            COUNT(*) AS total,
            SUM(CASE WHEN t.status=0 THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN t.status=1 THEN 1 ELSE 0 END) AS review,
            SUM(CASE WHEN t.status=2 THEN 1 ELSE 0 END) AS publish,
            SUM(CASE WHEN t.status=3 THEN 1 ELSE 0 END) AS reject
        ", false);
        if ($idDepartemen > 0) $qT->where('t.departemen', $idDepartemen);
        $applyRoleFilterTeori($qT); $applyDateRange($qT, 't');
        $teoriAgg = $qT->get()->getRowArray() ?: ['total'=>0,'draft'=>0,'review'=>0,'publish'=>0,'reject'=>0];

        // Praktek
        $qP = $this->db->table('ujian_praktek p')->select("
            COUNT(*) AS total,
            SUM(CASE WHEN p.status=0 THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN p.status=1 THEN 1 ELSE 0 END) AS review,
            SUM(CASE WHEN p.status=2 THEN 1 ELSE 0 END) AS publish,
            SUM(CASE WHEN p.status=3 THEN 1 ELSE 0 END) AS reject
        ", false);
        if ($idDepartemen > 0) $qP->where('p.departemen', $idDepartemen);
        $applyRoleFilterPraktek($qP); $applyDateRange($qP, 'p');
        $praktekAgg = $qP->get()->getRowArray() ?: ['total'=>0,'draft'=>0,'review'=>0,'publish'=>0,'reject'=>0];

        // Kartu
        $totalTeori     = (int)($teoriAgg['total']   ?? 0);
        $totalPraktek   = (int)($praktekAgg['total'] ?? 0);
        $totalDraft     = (int)($teoriAgg['draft']   ?? 0) + (int)($praktekAgg['draft']   ?? 0);
        $totalReview    = (int)($teoriAgg['review']  ?? 0) + (int)($praktekAgg['review']  ?? 0);
        $totalPublish   = (int)($teoriAgg['publish'] ?? 0) + (int)($praktekAgg['publish'] ?? 0);
        $totalReject    = (int)($teoriAgg['reject']  ?? 0) + (int)($praktekAgg['reject']  ?? 0);

        $totalTerkirim = $totalDraft;   // 0
        $totalDiterima = $totalPublish; // 2
        $totalDitolak  = $totalReject;  // 3
        $totalRevisi   = $totalReview;  // 1

        // =========================
        // GRAFIK PER DEPARTEMEN
        // =========================
        // TEORI
        $gt = $this->db->table('ujian_teori t')->select("
            COALESCE(d.nama, '(Tanpa Departemen)') AS departemen,
            SUM(CASE WHEN t.status=0 THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN t.status=1 THEN 1 ELSE 0 END) AS review,
            SUM(CASE WHEN t.status=2 THEN 1 ELSE 0 END) AS publish,
            SUM(CASE WHEN t.status=3 THEN 1 ELSE 0 END) AS reject
        ", false)->join('departemen d', 'd.id = t.departemen', 'left');
        if ($idDepartemen > 0) $gt->where('t.departemen', $idDepartemen);
        $applyRoleFilterTeori($gt); $applyDateRange($gt, 't');
        $gt = $gt->groupBy('departemen')->get()->getResultArray();

        // PRAKTEK
        $gp = $this->db->table('ujian_praktek p')->select("
            COALESCE(d.nama, '(Tanpa Departemen)') AS departemen,
            SUM(CASE WHEN p.status=0 THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN p.status=1 THEN 1 ELSE 0 END) AS review,
            SUM(CASE WHEN p.status=2 THEN 1 ELSE 0 END) AS publish,
            SUM(CASE WHEN p.status=3 THEN 1 ELSE 0 END) AS reject
        ", false)->join('departemen d', 'd.id = p.departemen', 'left');
        if ($idDepartemen > 0) $gp->where('p.departemen', $idDepartemen);
        $applyRoleFilterPraktek($gp); $applyDateRange($gp, 'p');
        $gp = $gp->groupBy('departemen')->get()->getResultArray();

        // Bentuk array untuk Chart.js (dua chart)
        $labelsTeori=$draftTeori=$reviewTeori=$publishTeori=$rejectTeori=[];
        usort($gt, fn($a,$b)=> strcasecmp((string)$a['departemen'], (string)$b['departemen']));
        foreach ($gt as $r) {
            $labelsTeori[]   = (string)($r['departemen'] ?? '-');
            $draftTeori[]    = (int)($r['draft']   ?? 0);
            $reviewTeori[]   = (int)($r['review']  ?? 0);
            $publishTeori[]  = (int)($r['publish'] ?? 0);
            $rejectTeori[]   = (int)($r['reject']  ?? 0);
        }

        $labelsPraktek=$draftPraktek=$reviewPraktek=$publishPraktek=$rejectPraktek=[];
        usort($gp, fn($a,$b)=> strcasecmp((string)$a['departemen'], (string)$b['departemen']));
        foreach ($gp as $r) {
            $labelsPraktek[]   = (string)($r['departemen'] ?? '-');
            $draftPraktek[]    = (int)($r['draft']   ?? 0);
            $reviewPraktek[]   = (int)($r['review']  ?? 0);
            $publishPraktek[]  = (int)($r['publish'] ?? 0);
            $rejectPraktek[]   = (int)($r['reject']  ?? 0);
        }

        // =========================
        // GRAFIK JUMLAH SOAL PER DOSEN (insert_by) – Teori & Praktek
        // =========================
        // Teori by dosen
        $td = $this->db->table('ujian_teori t')
        ->select("COALESCE(ds.nama, '(Tanpa Dosen)') AS dosen, COUNT(*) AS jml", false)
        ->join('dosen ds', 'ds.id = t.insert_by', 'left');
    if ($idDepartemen > 0) $td->where('t.departemen', $idDepartemen);
    $applyRoleFilterTeori($td); $applyDateRange($td, 't');
    $td = $td->groupBy('dosen')->orderBy('dosen','asc')->get()->getResultArray();

    $labelsDosenTeori = array_map(fn($r)=> (string)$r['dosen'], $td);
    $countsDosenTeori = array_map(fn($r)=> (int)$r['jml'],   $td);

    // =========================
    // JUMLAH SOAL PER DOSEN — PRAKTEK (sendiri)
    // =========================
    $pd = $this->db->table('ujian_praktek p')
        ->select("COALESCE(ds.nama, '(Tanpa Dosen)') AS dosen, COUNT(*) AS jml", false)
        ->join('dosen ds', 'ds.id = p.insert_by', 'left');
    if ($idDepartemen > 0) $pd->where('p.departemen', $idDepartemen);
    $applyRoleFilterPraktek($pd); $applyDateRange($pd, 'p');
    $pd = $pd->groupBy('dosen')->orderBy('dosen','asc')->get()->getResultArray();

    $labelsDosenPraktek = array_map(fn($r)=> (string)$r['dosen'], $pd);
    $countsDosenPraktek = array_map(fn($r)=> (int)$r['jml'],   $pd);

        // ===== kirim ke view =====
        $data = [
            'title'          => 'Dashboard',
            'menuActive'     => 'dashboard',

            // kartu
            'totalTeori'     => $totalTeori,
            'totalPraktek'   => $totalPraktek,
            'totalTerkirim'  => $totalTerkirim,
            'totalDiterima'  => $totalDiterima,
            'totalDitolak'   => $totalDitolak,
            'totalRevisi'    => $totalRevisi,

            // chart per departemen
            'chartTeori' => [
                'labels'  => $labelsTeori,
                'draft'   => $draftTeori,
                'review'  => $reviewTeori,
                'publish' => $publishTeori,
                'reject'  => $rejectTeori,
            ],
            'chartPraktek' => [
                'labels'  => $labelsPraktek,
                'draft'   => $draftPraktek,
                'review'  => $reviewPraktek,
                'publish' => $publishPraktek,
                'reject'  => $rejectPraktek,
            ],

            // chart per dosen
           'chartDosenTeori'   => ['labels'=>$labelsDosenTeori,   'counts'=>$countsDosenTeori],
        'chartDosenPraktek' => ['labels'=>$labelsDosenPraktek, 'counts'=>$countsDosenPraktek],

            // info filter utk UI
            'idDepartemen' => $idDepartemen,
            'start_date'   => $dateStart, // untuk value input
            'end_date'     => $dateEnd,
        ];

        return view('\Modules\Admin\Views\dashboard', $data);
    

    }
}