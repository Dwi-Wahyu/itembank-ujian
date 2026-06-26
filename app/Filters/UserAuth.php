<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Auth\Libraries\Auth as AuthLib;

class UserAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! AuthLib::check()) {
            if (! AuthLib::tryAutoLogin($request)) {
                return redirect()->to(base_url('login'));
            }
        }
        if (! AuthLib::validateFingerprint($request)) {
            AuthLib::logout();
            return redirect()->to(base_url('login'))->with('error', 'Sesi berakhir, silakan login ulang.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
