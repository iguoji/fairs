<?php
declare(strict_types=1);

namespace App\Http\Rbac\Relation;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 编辑权限
 */
class Edit
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 角色名称
        $validate->int('role', '角色编号')->require()->call(function($value){
            return Rbac::hasRole($value);
        });
        $validate->int('nodes', '节点编号')->require()->call(function($value){
            foreach ($value as $k => $v) {
                if (!Rbac::hasNode($v)) {
                    return false;
                }
            }
            return true;
        });

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 权限验证
        $admin = Admin::verify($req);

        // 参数验证
        $data = self::validate($req->all());
        try {
            // 开启事务
            Db::beginTransaction();

            // 先删除此前所有权限
            Rbac::delRolePower($data['role']);
            // 添加新的权限
            Rbac::addRolePowers($data['role'], $data['nodes']);

            // 提交事务
            Db::commit();
        } catch (\Throwable $th) {
            // 回滚事务
            Db::rollback();
            // 抛出异常
            throw $th;
        }

        // 返回结果
        return $data;
    }
}