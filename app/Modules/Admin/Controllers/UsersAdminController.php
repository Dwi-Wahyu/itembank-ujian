<?php
// Modules/Admin/Controllers/UsersAdminController.php
namespace Modules\Admin\Controllers;
class UsersAdminController extends UsersBaseController {
    protected int $ROLE_ID = 0;
    protected string $TITLE = 'Pengguna – Administrator';
    protected string $routeBase = 'admin/master/pengguna-admin';
    protected string $menuActive = 'pengguna-administrator';
}

?>