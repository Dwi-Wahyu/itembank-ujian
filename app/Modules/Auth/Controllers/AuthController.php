<?php
namespace Modules\Auth\Controllers;

use App\Controllers\BaseController;
use Modules\Auth\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Auth\Libraries\Auth as AuthLib;
use Config\Custom;
class AuthController extends BaseController
{
    public function index()
    {
        return view('\Modules\Auth\Views\login');
    }

    public function login()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_METHOD_NOT_ALLOWED)
                ->setJSON(['status'=>'error','message'=>'Metode tidak diizinkan','csrf_token'=>csrf_hash()]);
        }

        // Validasi input
        $rules = [
            'username'  => 'required|min_length[3]|max_length[100]',
            'exam_code' => 'required|min_length[6]|max_length[72]', // 72 untuk bcrypt
        ];
        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON([
                    'status'     => 'error',
                    'message'    => implode(' ', $this->validator->getErrors()),
                    'csrf_token' => csrf_hash(),
                ]);
        }

        $identifier = trim((string)$this->request->getPost('username'));
        $password   = (string)$this->request->getPost('exam_code');
        $remember   = (bool)$this->request->getPost('remember');

        // Rate limit: 5 request / 60 detik per IP+identifier
        $throttler = service('throttler');
        $key       = 'login:' . ($this->request->getIPAddress() ?? '0.0.0.0') . ':' . strtolower($identifier);
        if (! $throttler->check($key, 5, 60)) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS)
                ->setJSON([
                    'status'     => 'error',
                    'message'    => 'Terlalu banyak percobaan. Coba lagi beberapa saat.',
                    'csrf_token' => csrf_hash(),
                ]);
        }

        $users = new UserModel();

        // Ambil field yang diperlukan saja
        $user = $users->select('id,name,username,email,password,role_id,blok,departemen,thumb_avatar,remember_token,updated_at')
            ->groupStart()
                ->where('username', $identifier)
                ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        // --- Verifikasi kredensial (pesan generik agar tidak bocor) ---
        $invalidMsg = 'Kredensial tidak valid.';

        if (! $user) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['status'=>'error','message'=>$invalidMsg,'csrf_token'=>csrf_hash()]);
        }

        // Optional: kalau ingin benar-benar tidak bocor, jangan beritahu status blokir
        if ((string)$user['blok'] === '1') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON(['status'=>'error','message'=>'Akun diblokir. Hubungi admin.','csrf_token'=>csrf_hash()]);
        }

        if (! password_verify($password, $user['password'])) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['status'=>'error','message'=>$invalidMsg,'csrf_token'=>csrf_hash()]);
        }

        // Rehash jika algoritma default berubah (security hygiene)
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $users->update($user['id'], [
                'password'   => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Set session (regenerate TRUE = hapus sesi lama)
        $sessionData = [
            'isLoggedIn' => true,
            'user_id'    => (int)$user['id'],
            'name'       => $user['name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role_id'    => $user['role_id'],
            'departemen' => $user['departemen'],
            'avatar'     => $user['thumb_avatar'],
        ];
        session()->regenerate(true);
        session()->set($sessionData);

        // Remember me: simpan HASH token di DB, token asli di cookie
        helper('cookie');
        if ($remember) {
            $publicToken = bin2hex(random_bytes(32)); // simpan di cookie
            // hash dengan key aplikasi supaya kalau DB bocor token tak bisa dipakai
            $appKey = env('encryption.key') ?? 'fallback-app-key';
            $tokenHash = hash_hmac('sha256', $publicToken, $appKey);

            // simpan ke DB (gunakan kolom remember_token untuk HASH)
            $users->update($user['id'], [
                'remember_token' => $tokenHash,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            $isSecure = (stripos((string)base_url(), 'https://') === 0);
            // cookie 30 hari
            set_cookie('remember_uid',   (string)$user['id'], 60*60*24*30, '', '/', '', $isSecure, true, 'Lax');
            set_cookie('remember_token', $publicToken,        60*60*24*30, '', '/', '', $isSecure, true, 'Lax');
        } else {
            delete_cookie('remember_uid');
            delete_cookie('remember_token');
        }

        return $this->response->setJSON([
            'status'     => 'ok',
            'message'    => 'Login berhasil',
            'redirect'   => base_url('dashboard'),
            'csrf_token' => csrf_hash(),
        ]);
    }
}
