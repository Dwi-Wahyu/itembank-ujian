<?php
// Modules/Admin/Controllers/UsersDosenController.php
namespace Modules\Admin\Controllers;
class UsersDosenController extends UsersBaseController {
    protected int $ROLE_ID = 1;
    protected string $TITLE = 'Pengguna – Dosen';
    protected string $routeBase = 'admin/master/pengguna-dosen';
    protected string $menuActive = 'pengguna-dosen';
}

?>