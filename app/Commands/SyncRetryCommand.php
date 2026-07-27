<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncRetryCommand extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'sync:retry';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'sync:retry [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $syncApiKey = env('SYNC_API_KEY');
        $managementUrl = env('MANAGEMENT_API_URL');
        $client = \Config\Services::curlrequest();

        CLI::write('Starting Sync Retry Task...', 'green');

        // Retry Ujian Teori (ujian_attempt)
        $teoriCodes = $db->table('ujian_attempt')
            ->select('kode')
            ->where('synced_at IS NULL', null, false)
            ->whereIn('status', ['done', 'finished']) // Only retry completed attempts
            ->groupBy('kode')
            ->get()->getResultArray();

        foreach ($teoriCodes as $row) {
            $kode = $row['kode'];
            CLI::write("Pushing Teori: {$kode}", 'yellow');
            
            $attempts = $db->table('ujian_attempt')
                ->where('kode', $kode)
                ->whereIn('status', ['done', 'finished'])
                ->where('synced_at IS NULL', null, false)
                ->get()->getResultArray();
            
            if (empty($attempts)) continue;

            try {
                $url = rtrim($managementUrl, '/') . '/api/sync/import-results';
                $response = $client->request('POST', $url, [
                    'headers' => ['X-API-KEY' => $syncApiKey, 'Content-Type' => 'application/json'],
                    'json' => ['attempts' => $attempts],
                    'verify' => env('CI_ENVIRONMENT') === 'production' ? true : false,
                    'http_errors' => false
                ]);

                if ($response->getStatusCode() < 400) {
                    $syncedIds = array_column($attempts, 'id');
                    $db->table('ujian_attempt')->whereIn('id', $syncedIds)->update(['synced_at' => date('Y-m-d H:i:s')]);
                    CLI::write("Success pushing {$kode}", 'green');
                } else {
                    CLI::write("Failed pushing {$kode}: HTTP " . $response->getStatusCode(), 'red');
                    log_message('error', "SyncRetry Teori {$kode} failed: HTTP " . $response->getStatusCode());
                }
            } catch (\Exception $e) {
                CLI::write("Error pushing {$kode}: " . $e->getMessage(), 'red');
                log_message('error', "SyncRetry Teori {$kode} error: " . $e->getMessage());
            }
        }

        // Retry OSCE (jawaban_osce)
        $osceIds = $db->table('jawaban_osce')
            ->select('osce_id')
            ->where('synced_at IS NULL', null, false)
            ->groupBy('osce_id')
            ->get()->getResultArray();

        foreach ($osceIds as $row) {
            $osce_id = $row['osce_id'];
            
            $results = $db->table('jawaban_osce')
                ->where('osce_id', $osce_id)
                ->where('synced_at IS NULL', null, false)
                ->get()->getResultArray();
                
            if (empty($results)) continue;

            foreach ($results as &$res) {
                $aspek = $db->table('jawaban_osce_aspek')
                    ->where('jawaban_osce_id', $res['id'])
                    ->get()->getResultArray();
                $res['aspek'] = $aspek;
            }

            CLI::write("Pushing OSCE ID: {$osce_id}", 'yellow');

            try {
                $url = rtrim($managementUrl, '/') . '/api/sync/import-osce-results';
                $response = $client->request('POST', $url, [
                    'headers' => ['X-API-KEY' => $syncApiKey, 'Content-Type' => 'application/json'],
                    'json' => ['results' => $results],
                    'verify' => env('CI_ENVIRONMENT') === 'production' ? true : false,
                    'http_errors' => false
                ]);

                if ($response->getStatusCode() < 400) {
                    $syncedIds = array_column($results, 'id');
                    $db->table('jawaban_osce')->whereIn('id', $syncedIds)->update(['synced_at' => date('Y-m-d H:i:s')]);
                    CLI::write("Success pushing OSCE ID {$osce_id}", 'green');
                } else {
                    CLI::write("Failed pushing OSCE ID {$osce_id}: HTTP " . $response->getStatusCode(), 'red');
                    log_message('error', "SyncRetry OSCE {$osce_id} failed: HTTP " . $response->getStatusCode());
                }
            } catch (\Exception $e) {
                CLI::write("Error pushing OSCE ID {$osce_id}: " . $e->getMessage(), 'red');
                log_message('error', "SyncRetry OSCE {$osce_id} error: " . $e->getMessage());
            }
        }

        CLI::write('Sync Retry Task Completed.', 'green');
    }
}
