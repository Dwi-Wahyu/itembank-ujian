<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Auth\Libraries\Auth;
use Config\Custom;
class AdminAuth implements FilterInterface
{

     public function __construct()
    {
        $this->ADMIN_ROLES = config(Custom::class)->adminRoles ?? [0,1,2,3,4];
    }
    // sesuaikan dengan skema kamu
// App/Filters/AdminAuth.php
// App/Filters/AdminAuth.php (cuplikan)
public function before(RequestInterface $request, $arguments = null)
{
    $u = \Modules\Auth\Libraries\Auth::user();
    if (!$u && ! \Modules\Auth\Libraries\Auth::tryAutoLogin($request)) {
        return redirect()->to(site_url('admin'));
    }
    
    $u    ??= \Modules\Auth\Libraries\Auth::user();
    $role  = (int)($u['role_id'] ?? $u['id_role'] ?? -1);

    // Ambil daftar yang diizinkan: prioritaskan argumen di Routes, 
    // jika kosong pakai default dari Config/Custom.php
    $allowed = $arguments ? array_map('intval', (array)$arguments) : config(\Config\Custom::class)->adminRoles;

    if (! in_array($role, $allowed, true)) {
        return redirect()->to(site_url('admin'))->with('error','Akses admin ditolak. (role: ' . $role . ')');
    }

    // --- Tambahan Restriksi Khusus Reviewer (Role 4) ---
    if ($role === 4) {
        $uri = $request->getPath();
        
        $allowedReviewerPaths = [
            'admin/dashboard',
            'admin/logout',
            'admin/soal',
            'admin/options',
            'admin/praktek/aspek',
        ];

        $isAllowed = false;
        foreach ($allowedReviewerPaths as $p) {
            if (strpos($uri, $p) === 0) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return redirect()->to(site_url('admin/dashboard'))->with('error', 'Reviewer hanya diperbolehkan mengakses menu Soal.');
        }
    }

    // --- Tambahan Restriksi Khusus Operator Ujian (Role 6) ---
    if ($role === 6) {
        $uri = $request->getPath();

        // 1) Endpoint yang secara eksplisit DILARANG (input/edit/hapus/telaah/import soal,
        //    serta modul lain di luar cakupan operator meski prefix-nya mirip)
        $deniedOperatorPatterns = [
            'admin/soal/teori/tambah',
            'admin/soal/teori/simpan',
            'admin/soal/teori/create',
            'admin/soal/teori/edit',
            'admin/soal/teori/update',
            'admin/soal/teori/delete',
            'admin/soal/teori/upload',
            'admin/soal/teori/import',
            'admin/soal/teori/review',
            'admin/soal/teori/revisi',
            'admin/soal/teori/reg-generate',
            'admin/soal/praktek/create',
            'admin/soal/praktek/update',
            'admin/soal/praktek/delete',
            'admin/soal/praktek/upload',
            'admin/soal/praktek/import',
            'admin/soal/praktek/review',
            'admin/soal/praktek/reg-generate',
            'admin/soal/praktek/add',
            'admin/soal/praktek/simpan',
            'admin/soal/praktek/edit',
            'admin/praktek/aspek',   // kelola aspek penilaian OSCE, bukan bagian dari lihat soal
            'admin/osce-soal',       // modul CRUD soal praktek OSCE (bukan cakupan "lihat soal" via bank soal)
            'admin/master',          // master data & manajemen pengguna lain
        ];
        foreach ($deniedOperatorPatterns as $p) {
            if (strpos($uri, $p) === 0) {
                return redirect()->to(site_url('admin/dashboard'))
                    ->with('error', 'Operator Ujian hanya dapat melihat soal dan mengatur sesi ujian.');
            }
        }

        // 2) Prefix yang diizinkan untuk Operator
        $allowedOperatorPrefixes = [
            'admin/dashboard',
            'admin/logout',
            'admin/ujian',       // kelola sesi ujian teori & praktek: create/update/detail/kode/peserta/pilih-soal
            'admin/soal/teori',  // hanya GET (lihat) — aksi tulis sudah diblokir denylist di atas
            'admin/soal/praktek',// hanya GET (lihat) — aksi tulis sudah diblokir denylist di atas
            'admin/soal/format',
            'admin/options',     // endpoint dropdown pendukung form (departemen, dsb.)
        ];

        $isAllowed = false;
        foreach ($allowedOperatorPrefixes as $p) {
            if (strpos($uri, $p) === 0) { $isAllowed = true; break; }
        }
        if (!$isAllowed) {
            return redirect()->to(site_url('admin/dashboard'))
                ->with('error', 'Operator Ujian tidak memiliki akses ke halaman ini.');
        }
    }

    if (! \Modules\Auth\Libraries\Auth::validateFingerprint($request)) {
        \Modules\Auth\Libraries\Auth::logout();
        return redirect()->to(site_url('admin'))->with('error','Sesi berakhir, silakan login ulang.');
    }
}



    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
