<?php
declare(strict_types=1);

namespace App\Http\Rbac;

use App\Common\Admin;
use App\Common\Account;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 管理员登录
 */
class Signin
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        $validate->string('username', '账号')
            ->require()->length(5, 64)
            ->alphaDash()
            ->call(function($value){
                return Admin::has($value);
            });
        $validate->string('password', '密码')->require()->length(6, 32);
        $validate->bool('remember', '记住设备')->default(false);
        $validate->string('from', '来路页面')->default('/index.html');

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
            // 进行登录
            if ($req->isPost()) {
                // 参数验证
                $data = self::verify($req->all());
                // 获取账号
                $admin = Admin::get($data['username']);
                // 对比密码
                if ($admin['password'] != Account::encrypt($data['password'])) {
                    throw new Exception('很抱歉、密码不正确！');
                }
                // 保存会话
                Admin::signin($req, $res, $admin, $data['remember'] ? 60 * 60 * 24 * 7 : null);
                // 前往来路页面
                return $res->redirect($data['from']);
            }
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->html('admin/rbac/signin', [
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}