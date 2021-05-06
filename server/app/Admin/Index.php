<?php
declare(strict_types=1);

namespace App\Admin;

use Minimal\Facades\Db;
use Minimal\Facades\View;

class Index
{
    /**
     * 后台登录
     */
    public function signin($req, $res)
    {
        return View::fetch('Admin/Brand/save.php');
    }
}