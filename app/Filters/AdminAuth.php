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

    if (! \Modules\Auth\Libraries\Auth::validateFingerprint($request)) {
        \Modules\Auth\Libraries\Auth::logout();
        return redirect()->to(site_url('admin'))->with('error','Sesi berakhir, silakan login ulang.');
    }
}



    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
