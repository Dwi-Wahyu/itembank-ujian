<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class IpUjianFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $clientIp = $request->getIPAddress();

        // Always allow loopback/localhost access for development/local testing
        if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
            return;
        }

        $allowedPrefix = env('ALLOWED_IP_PREFIX', '192.168.10.');

        // Tolak jika IP klien tidak berawalan prefix lab
        if (strpos($clientIp, $allowedPrefix) !== 0) {
            return Services::response()
                ->setStatusCode(403)
                ->setBody('Akses Ditolak: Perangkat Anda berada di luar jaringan lab ujian.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Kosongkan saja
    }
}