<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\{RequestInterface, ResponseInterface};

class TeoriAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('teori_logged_in')) {
            return redirect()->to(base_url('teori/login'));
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
