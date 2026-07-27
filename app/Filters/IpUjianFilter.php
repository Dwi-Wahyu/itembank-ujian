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
        // Pengecekan IP dinonaktifkan sementara
        return;

        // Jika CI_ENVIRONMENT adalah development, matikan proteksi filter IP
        if (ENVIRONMENT === 'development') {
            return;
        }

        $clientIp = $request->getIPAddress();

        // Always allow loopback/localhost access for development/local testing
        if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
            return;
        }

        $allowedPrefixesRaw = env('ALLOWED_IP_PREFIX', '192.168.10.');
        $allowedPrefixes = array_filter(array_map('trim', explode(',', $allowedPrefixesRaw)));

        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if ($prefix !== '' && strpos($clientIp, $prefix) === 0) {
                $isAllowed = true;
                break;
            }
        }

        // Tolak jika IP klien tidak berawalan salah satu prefix lab
        if (!$isAllowed) {
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