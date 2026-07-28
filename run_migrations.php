<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(__DIR__);
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \Config\Database::connect();

try {
    $db->query("ALTER TABLE ujian_attempt ADD COLUMN synced_at DATETIME NULL");
    echo "Ujian: synced_at added to ujian_attempt\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->query("ALTER TABLE jawaban_osce ADD COLUMN synced_at DATETIME NULL");
    echo "Ujian: synced_at added to jawaban_osce\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->query("ALTER TABLE jawaban_osce ADD COLUMN keterangan TEXT NULL");
    echo "Ujian: keterangan added to jawaban_osce\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
