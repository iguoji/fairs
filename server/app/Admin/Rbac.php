<?php
declare(strict_types=1);

namespace App\Admin;

use Minimal\Facades\View;

/**
 * 权限类
 */
class Rbac
{
    /**
     * 登录后台
     */
    public function signin($req, $res)
    {
        return View::fetch('admin/rbac/signin', [
            'name'      =>  'iguoji',
            'other'     =>  'iJing',
            'list'      =>  ['one', 'two', 'three'],
        ]);
    }
}