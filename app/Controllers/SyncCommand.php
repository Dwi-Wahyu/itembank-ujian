<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Services;

class SyncCommand extends Controller
{
    // Run this via URL on the local server before the exam starts: 
    // http://192.168.x.x/sync-command/pullExam/UJN-2026-05-01
    public function pullExam($kode_ujian)
    {
        $managementUrl = env('MANAGEMENT_API_URL') . '/api/sync/export-teori/' . $kode_ujian;
        $apiKey = env('SYNC_API_KEY');

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->request('GET', $managementUrl, [
                'headers' => [
                    'X-API-KEY' => $apiKey,
                    'Accept'    => 'application/json'
                ],
                'verify' => false // Set true in production if SSL is valid
            ]);

            $body = json_decode($response->getBody(), true);

            if ($body['status'] !== 'success') {
                return "Error from Master Server: " . json_encode($body);
            }

            $data = $body['data'];
            $db = \Config\Database::connect();
            
            $db->transStart();

            // 1. Clean existing local data for this exam to prevent duplicates
            $db->table('buat_teori')->where('kode_ujian', $kode_ujian)->delete();
            $db->table('admin_cbt')->where('kode_ujian', $kode_ujian)->delete();
            
            // 2. Insert new data
            if(!empty($data['exam'])) $db->table('buat_teori')->insert($data['exam']);
            if(!empty($data['participants'])) $db->table('admin_cbt')->insertBatch($data['participants']);
            
            // 3. Insert/Update Questions (Use REPLACE or ignore duplicates based on your DB strictness)
            if(!empty($data['questions'])) {
                // Using ignore to prevent crashing if the question already exists locally
                $db->table('ujian_teori')->ignore(true)->insertBatch($data['questions']);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return "Database Transaction Failed during Import.";
            }

            return "Exam {$kode_ujian} successfully synchronized to local server. Ready for offline CBT.";

        } catch (\Exception $e) {
            return "Connection Failed: " . $e->getMessage();
        }
    }

    // Run this when the exam finishes to send results back to VPS
    public function pushResults($kode_ujian)
    {
        $db = \Config\Database::connect();
        
        // Fetch finished attempts from local DB
        $attempts = $db->table('ujian_attempt')
            ->where('kode_ujian', $kode_ujian)
            ->where('status_ujian', 'selesai')
            ->get()->getResultArray();

        if (empty($attempts)) {
            return "No completed attempts to sync.";
        }

        $managementUrl = env('MANAGEMENT_API_URL') . '/api/sync/import-results';
        $client = Services::curlrequest();

        try {
            $response = $client->request('POST', $managementUrl, [
                'headers' => [
                    'X-API-KEY' => env('SYNC_API_KEY'),
                    'Content-Type' => 'application/json'
                ],
                'json' => ['attempts' => $attempts]
            ]);

            $result = json_decode($response->getBody(), true);
            return "Push Status: " . $result['message'];

        } catch (\Exception $e) {
            return "Failed to push results: " . $e->getMessage();
        }
    }
}