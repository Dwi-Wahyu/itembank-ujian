<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Auth\Models\UserModel;
use Modules\Auth\Libraries\Auth as AuthLib;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Custom;

/**
 * Login khusus ADMIN.
 * Default admin roles = [1]. Sesuaikan sesuai skema kamu.
 */
class AuthController extends BaseController
{
      private array $ADMIN_ROLES;
 public function __construct()
 {
     $this->ADMIN_ROLES = config(Custom::class)->adminRoles ?? [0,1,2,3,4];
 }

    public function index()
    {
        // View admin login
        return view('\Modules\Admin\Views\login');
    }

 public function login()
{
    if ($this->request->getMethod() !== 'POST') {
        return $this->response->setStatusCode(ResponseInterface::HTTP_METHOD_NOT_ALLOWED)
            ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
    }

    // Validasi input
    $rules = [
        'username'  => 'required|min_length[3]|max_length[100]',
        'password'  => 'required|min_length[6]|max_length[72]',
    ];
    if (! $this->validate($rules)) {
        return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
            ->setJSON(['status'=>'error','message'=>implode(' ', $this->validator->getErrors()), 'csrf_token'=>csrf_hash()]);
    }

    $identifier = trim((string)$this->request->getPost('username'));
    $password   = (string)$this->request->getPost('password');
    $remember   = (bool)$this->request->getPost('remember');

    // Rate limit
    $throttler = service('throttler');
    $raw = ($this->request->getIPAddress() ?? '0.0.0.0') . '|' . strtolower($identifier);
    $key = 'adminlogin_' . md5($raw);
    if (! $throttler->check($key, 5, 60)) {
        return $this->response->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS)
            ->setJSON(['status'=>'error','message'=>'Terlalu banyak percobaan. Coba lagi sebentar lagi.','csrf_token'=>csrf_hash()]);
    }

    $users = new UserModel();

    // Ambil SEMUA field yang kamu minta
    $user = $users->select('
            id,name,username,email,password,role_id,blok,departemen,
            thumb_avatar,remember_token,remember_expires_at,
            old,kordinator,created_at,updated_at
        ')
        ->groupStart()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
        ->groupEnd()
        ->first();

    $invalidMsg = 'Kredensial tidak valid.';

    if (! $user) {
        return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
            ->setJSON(['status'=>'error','message'=>$invalidMsg,'csrf_token'=>csrf_hash()]);
    }

    // Boleh masuk hanya role admin
    $role = (int) ($user['role_id'] ?? $user['id_role'] ?? -1);
    if (! in_array($role, $this->ADMIN_ROLES, true)) {
        return $this->response->setStatusCode(403)
            ->setJSON([
                'status'=>'error',
                'message'=>'Akses admin ditolak. (role: '.$role.')',
                'csrf_token'=>csrf_hash()
            ]);
    }

    if ((string)$user['blok'] === '1') {
        return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
            ->setJSON(['status'=>'error','message'=>'Akun diblokir.','csrf_token'=>csrf_hash()]);
    }

    if (! password_verify($password, $user['password'])) {
        return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
            ->setJSON(['status'=>'error','message'=>$invalidMsg,'csrf_token'=>csrf_hash()]);
    }

    // Rehash bila perlu
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $users->update($user['id'], [
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        // refresh data user (password hash baru)
        $user['password'] = $users->select('password')->find($user['id'])['password'];
        $user['updated_at'] = date('Y-m-d H:i:s');
    }

    // Handle Remember Me: refresh token & expiry kalau dicentang
    if ($remember) {
        $newToken  = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (60*60*24*30)); // 30 hari
        $users->update($user['id'], [
            'remember_token'       => $newToken,
            'remember_expires_at'  => $expiresAt,
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);
        $user['remember_token']      = $newToken;
        $user['remember_expires_at'] = $expiresAt;
    }

    // Login via library (cookie remember-me, dsb.)
    AuthLib::login($user, $remember, $this->request);

    // Siapkan payload lengkap sesuai field yang diminta
    $sessionUser = [
        'id'                  => (int)($user['id'] ?? 0),
        'name'                => $user['name'] ?? null,
        'username'            => $user['username'] ?? null,
        'email'               => $user['email'] ?? null,
        'password'            => $user['password'] ?? null, // hash dari DB (bukan plain)
        'role_id'             => (int)($user['role_id'] ?? 0),
        'blok'                => (string)($user['blok'] ?? '0'),
        'departemen'          => $user['departemen'] ?? null,
        'thumb_avatar'        => $user['thumb_avatar'] ?? null,
        'remember_token'      => $user['remember_token'] ?? null,
        'remember_expires_at' => $user['remember_expires_at'] ?? null,
        'old'                 => $user['old'] ?? null,
        'kordinator'          => $user['kordinator'] ?? null,
        'created_at'          => $user['created_at'] ?? null,
        'updated_at'          => $user['updated_at'] ?? null,
    ];

    // Simpan ke session dalam satu namespace (mis. 'admin_user')
    session()->set([
        'admin_user'     => $sessionUser,
        'admin_role'     => $role,
        'admin_logged_in'=> true,
    ]);

    return $this->response->setJSON([
        'status'     => 'ok',
        'message'    => 'Login admin berhasil',
        'redirect'   => base_url('admin/sync/auto'),
        'csrf_token' => csrf_hash(),
    ]);
}
public function logout()
{
    helper('cookie');

    // Invalidate remember token di DB (kalau user masih ada di sesi)
    $uid = (int) (session('user_id') ?? 0);
    if ($uid > 0) {
        $users = new UserModel();
        $users->update($uid, [
            'remember_token' => null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    // Hapus cookie remember-me
    delete_cookie('remember_uid');
    delete_cookie('remember_token');

    // Hancurkan sesi
    session()->destroy();

    // Respon: JSON untuk AJAX, redirect untuk normal request
    $isAjax = $this->request->isAJAX() || str_contains(
        strtolower($this->request->getHeaderLine('Accept')), 'application/json'
    );

    if ($isAjax) {
        return $this->response->setJSON([
            'status'     => 'ok',
            'message'    => 'Logout berhasil',
            'redirect'   => base_url('/admin'),
            'csrf_token' => csrf_hash(),
        ]);
    }

    return redirect()->to(base_url('/admin'))->with('message', 'Anda telah logout.');
}

}
