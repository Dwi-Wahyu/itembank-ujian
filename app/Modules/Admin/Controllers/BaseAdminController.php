<?php 
// Modules/Admin/Controllers/BaseAdminController.php
namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

class BaseAdminController extends BaseController
{
    protected array $viewData = [
        'title'      => 'Admin',
        'menuActive' => 'dashboard',
        'user'       => ['name' => 'Admin'],
    ];
}

?>