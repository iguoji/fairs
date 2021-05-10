<?php
declare(strict_types=1);

namespace App\Admin;

use Minimal\Facades\Db;
use Minimal\Facades\View;

class Index
{
    /**
     * 后台首页
     */
    public function index($req, $res)
    {
        return View::fetch('admin/index/index');
    }
}