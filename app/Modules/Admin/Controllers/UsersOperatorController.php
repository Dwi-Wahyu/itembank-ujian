<?php
// Modules/Admin/Controllers/UsersOperatorController.php
namespace Modules\Admin\Controllers;

class UsersOperatorController extends UsersBaseController {
    protected int $ROLE_ID = 6;
    protected string $TITLE = 'Pengguna – Operator Ujian';
    protected string $routeBase = 'admin/master/pengguna-operator';
    protected string $menuActive = 'pengguna-operator';
}
