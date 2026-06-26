<?php

use CodeIgniter\Boot;
use Config\Paths;

// Pastikan skrip ini hanya dijalankan di lingkungan FrankenPHP
if (!function_exists('frankenphp_handle_request')) {
    require __DIR__ . '/public/index.php';
    return;
}

// Persiapan untuk Worker Mode
define('FCPATH', __DIR__ . '/public' . DIRECTORY_SEPARATOR);
require __DIR__ . '/app/Config/Paths.php';
$paths = new Paths();

// Pemuatan sistem (hanya sekali di awal)
require $paths->systemDirectory . '/Boot.php';

// Loop utama worker
$handler = function () use ($paths) {
    // Jalankan satu permintaan (ini menangani routing, controllers, dsb.)
    Boot::bootWeb($paths);
};

// Daftarkan handler ke FrankenPHP
for ($nbRequests = 0; frankenphp_handle_request($handler); ++$nbRequests) {
    // Anda bisa menambahkan logika di sini setiap X permintaan (opsional)
}
