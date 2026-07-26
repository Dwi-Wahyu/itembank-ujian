<?php
// Modules/Admin/Controllers/UsersManajemenController.php
namespace Modules\Admin\Controllers;
class UsersManajemenController extends UsersBaseController {
    protected int $ROLE_ID = 2;
    protected string $TITLE = 'Pengguna – Manajemen';
    protected string $routeBase = 'admin/master/pengguna-manajemen';
    protected string $menuActive = 'pengguna-manajemen';
}

?>