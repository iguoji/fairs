<?php
declare(strict_types=1);

namespace App\Http\Rbac\Admin;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 删除管理员
 */
class Remove
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 管理员编号
        $validate->int('id', '管理员编号')->require()->call(function($value){
            return Admin::has($value);
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

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 参数验证
            $data = self::validate($req->all());
            // 执行操作
            Admin::del($data['id']);
            // 请退当前已登录的该管理员
            Admin::getout($req, $data['id']);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->redirect('/rbac/admin.html', [
            'exception'     =>  $exception
        ]);
    }
}