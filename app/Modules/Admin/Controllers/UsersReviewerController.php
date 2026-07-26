<?php
// Modules/Admin/Controllers/UsersReviewerController.php
namespace Modules\Admin\Controllers;
class UsersReviewerController extends UsersBaseController {
    protected int $ROLE_ID = 4;
    protected string $TITLE = 'Pengguna – Reviewer';
    protected string $routeBase = 'admin/master/pengguna-reviewer';
    protected string $menuActive = 'pengguna-reviewer';
}

?>