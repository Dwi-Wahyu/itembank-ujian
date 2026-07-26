<?php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;
use CodeIgniter\I18n\Time;
use CodeIgniter\HTTP\ResponseInterface;

class PasswordMaintenanceController extends BaseController
{
    /**
     * Reset semua password users -> admin1234 (hashed).
     * POST only. Pastikan route diproteksi adminauth:0.
     */
    public function resetAll()
    {
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
        }

        $db = Database::connect();
        $newPlain = 'admin1234';
        $hash     = password_hash($newPlain, PASSWORD_DEFAULT);
        $now      = Time::now(config('App')->appTimezone)->toDateTimeString();

        $db->transStart();

        // NOTE: tanpa WHERE -> semua baris di tabel users
        $ok = $db->table('users')
            ->set('password', $hash)
            ->set('remember_token', null)
            ->set('remember_expires_at', null)
            ->set('updated_at', $now)
            ->update();

        $affected = $db->affectedRows();

        $db->transComplete();

        if ($db->transStatus() === false || !$ok) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setJSON(['status'=>'error','message'=>'Gagal reset password massal']);
        }
// Modules/Admin/Controllers/PasswordMaintenanceController.php
return $this->response
    ->setHeader('X-CSRF-TOKEN', csrf_hash()) // <— penting utk AJAX berikutnya
    ->setJSON([
        'status'       => 'ok',
        'affected'     => $affected,
        'new_password' => $newPlain,
    ]);

    }
}
