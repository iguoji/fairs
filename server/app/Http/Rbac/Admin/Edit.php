<?php
declare(strict_types=1);

namespace App\Http\Rbac\Admin;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 编辑管理员
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

        // 细节参数
        $validate->int('id', '管理员编号')->require()->call(function($value){
            return Admin::has($value);
        });
        $validate->string('username', '登录账号')->length(5, 32)->call(function($value, $values){
            $admin = Admin::get($value, 'username');
            return empty($admin) || $admin['id'] == $values['id'];
        }, message: '很抱歉、该账号已存在！');
        $validate->string('password', '登录密码')->length(6, 32);
        $validate->int('role', '所属角色')->call(function($value){
            return Rbac::hasRole($value);
        });
        $validate->int('status', '账号状态')->default(1);
        $validate->string('nickname', '个性昵称');

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

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 参数验证
            $data = self::validate($req->all());
            // 编辑管理员
            Admin::upd($data['id'], $data);
            // 请退当前已登录的该管理员
            Admin::getout($req, $data['id']);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->redirect('/rbac/admin.html', [
            'exception' =>  $exception,
        ]);
    }
}