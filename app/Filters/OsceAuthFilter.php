<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class OsceAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $osce = session('osce');
        if (!$osce) {
            return redirect()->to(site_url('Osce/login'));
        }
        // (opsional) validasi fingerprint
        $ua = sha1($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (($osce['ua'] ?? '') !== $ua) {
            session()->remove('osce');
            return redirect()->to(site_url('Osce/login'));
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
