<?php
declare(strict_types=1);

namespace App\Http\Rbac\Role;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 编辑角色
 */
class Edit
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 角色名称
        $validate->int('id', '角色编号')->require()->call(function($value){
            return Rbac::hasRole($value);
        });
        $validate->string('name', '角色名称')->length(1, 50);
        $validate->int('sort', '排列顺序');
        $validate->int('status', '角色状态');
        $validate->int('parent', '所属上级')->call(function($value, $values){
            return $value != $values['id'] && ($value == 0 || Rbac::hasRole($value));
        });

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 异常错误
        $exception = [];
        try {
            // 权限验证
            $admin = Admin::verify($req);
            // 参数验证
            $data = self::verify($req->all());
            // 编辑角色
            Rbac::updRole($data['id'], $data);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->redirect('/rbac/role.html', [
            'exception' =>  $exception,
        ]);
    }
}