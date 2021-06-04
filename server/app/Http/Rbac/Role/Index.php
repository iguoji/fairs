<?php
declare(strict_types=1);

namespace App\Http\Rbac\Role;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 角色列表
 */
class Index
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

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
        // 异常错误
        $exception = [];
        // 角色列表
        $rules = [];
        try {
            // 权限验证
            $admin = Admin::verify($req);
            // 角色列表
            $result = Rbac::getRoles(0, false);

            var_dump(\Minimal\Facades\Db::lastSql());
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->html('admin/rbac/role/index', [
            'roles'     =>  $result,
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}