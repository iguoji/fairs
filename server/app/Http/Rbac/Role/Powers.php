<?php
declare(strict_types=1);

namespace App\Http\Rbac\Role;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 查询角色拥有的权限
 */
class Powers
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

        // 先查询角色拥有的权限
        $roleNodes = Rbac::getRolePowers($data['id']);
        $roleNodeIds = array_column($roleNodes, 'id');
        // 再查询所有节点
        $nodes = Rbac::getNodes(0, false);
        foreach ($nodes as $key => $value) {
            $value['checked'] = in_array($value['id'], $roleNodeIds);
            $nodes[$key] = $value;
        }

        // 返回结果
        return $nodes;
    }
}