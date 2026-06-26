<?php
namespace Modules\Auth\Libraries;

use Modules\Auth\Models\UserModel;

class Auth
{
    public const KEY = 'auth';

    /** Simpan session + optional remember me */
    public static function login(array $user, bool $remember, $request = null): void
    {
        $claims = [
            'uid'       => (int) $user['id'],
            'name'      => $user['name'] ?? '',
            'uname'     => $user['username'] ?? '',
            'email'     => $user['email'] ?? '',
            'role_id'   => $user['role_id'] ?? null,
            'dept'      => $user['departemen'] ?? '',
            'avatar'    => $user['thumb_avatar'] ?? '',
            'logged_at' => time(),
            'ua'        => sha1($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'ip'        => self::ipSegment($request?->getIPAddress() ?? ($_SERVER['REMOTE_ADDR'] ?? '')),
        ];

        session()->regenerate(true);          // destroy old session
        session()->set([self::KEY => $claims]);

        if ($remember) {
            self::setRemember((int)$user['id']);
        } else {
            self::clearRemember();
        }
    }

    public static function user(): ?array  { return session(self::KEY) ?: null; }
    public static function id(): ?int      { return session(self::KEY.'.uid') ?? null; }
    public static function check(): bool   { return (bool) session(self::KEY); }

    public static function logout(): void
    {
        self::clearRemember();
        session()->remove(self::KEY);
        session()->destroy();
    }

    /** Validasi fingerprint (UA + segmen IP) */
    public static function validateFingerprint($request): bool
    {
        $auth = self::user();
        if (!$auth) return false;
        $ua = sha1($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip = self::ipSegment($request?->getIPAddress() ?? '');
        return hash_equals($auth['ua'], $ua) && ($auth['ip'] === $ip);
    }

    /** Auto login dari cookie remember me */
    public static function tryAutoLogin($request): bool
    {
        helper('cookie');
        $uid = (int) (get_cookie('remember_uid') ?? 0);
        $tok = (string) (get_cookie('remember_token') ?? '');
        if (!$uid || $tok === '') return false;

        $appKey    = env('encryption.key') ?? 'fallback-app-key';
        $calcHash  = hash_hmac('sha256', $tok, $appKey);

        $model = new UserModel();
        $user  = $model->select('id,name,username,email,role_id,departemen,thumb_avatar,remember_token,blok')
                       ->find($uid);

        if (!$user || (string)$user['blok'] === '1') return false;
        if (!hash_equals((string)$user['remember_token'], $calcHash)) return false;

        self::login($user, false, $request); // re-issue session tanpa set remember baru
        return true;
    }

    /* ===== helpers ===== */

    protected static function ipSegment(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $p = explode('.', $ip);
            return sprintf('%s.%s.%s', $p[0] ?? '0', $p[1] ?? '0', $p[2] ?? '0'); // /24
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $p = explode(':', $ip);
            return implode(':', array_slice($p, 0, 3));
        }
        return 'unknown';
    }

    protected static function setRemember(int $uid): void
    {
        helper('cookie');
        $public   = bin2hex(random_bytes(32)); // disimpan di cookie
        $appKey   = env('encryption.key') ?? 'fallback-app-key';
        $hash     = hash_hmac('sha256', $public, $appKey); // disimpan di DB

        $model = new UserModel();
        $model->update($uid, [
            'remember_token' => $hash,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $secure = (stripos((string)base_url(), 'https://') === 0);
        // cookie 30 hari, HttpOnly + SameSite Lax
        set_cookie('remember_uid',   (string)$uid, 60*60*24*30, '', '/', '', $secure, true, 'Lax');
        set_cookie('remember_token', $public,      60*60*24*30, '', '/', '', $secure, true, 'Lax');
    }

    protected static function clearRemember(): void
    {
        helper('cookie');
        delete_cookie('remember_uid');
        delete_cookie('remember_token');
    }
    
}
