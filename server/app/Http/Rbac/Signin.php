<?php
declare(strict_types=1);

namespace App\Http\Rbac;

/**
 * 管理员登录
 */
class Signin
{
    public function handle($req, $res) : mixed
    {
        return $res->html('admin/rbac/signin', [
            'name'      =>  'iguoji',
            'other'     =>  'iJing',
            'list'      =>  ['one', 'two', 'three'],
        ]);
    }
}