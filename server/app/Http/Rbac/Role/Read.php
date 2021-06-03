<?php
declare(strict_types=1);

namespace App\Http\Rbac\Role;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 读取角色
 */
class Read
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 角色编号
        $validate->int('id', '角色编号')->require()->call(function($value){
            return Rbac::hasRole($value);
        });

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 最终结果
        $result = [];

        // 权限验证
        $admin = Admin::verify($req);
        // 参数验证
        $data = self::verify($req->all());
        // 查询数据
        $result = Rbac::getRole($data['id']);

        // 返回结果
        return $result;
    }
}