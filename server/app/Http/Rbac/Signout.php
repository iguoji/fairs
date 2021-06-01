<?php
declare(strict_types=1);

namespace App\Http\Rbac;

use App\Common\Admin;
use Minimal\Foundation\Exception;

/**
 * 管理员退出
 */
class Signout
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {}

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 异常错误
        $exception = [];
        try {
            // 权限验证
            $adminId = Admin::verify($req);
            // 退出登录
            Admin::signout($req, $res);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->redirect('signin.html', [
            'exception' =>  $exception,
        ]);
    }
}