<?php
declare(strict_types=1);

namespace App\Http\Rbac\Admin;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 管理员日志
 */
class Logs
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
        $admins = [];
        $logs = [];
        $total = 0;
        $size = 20;
        // 异常错误
        $exception = [];
        // 角色列表
        $rules = [];
        try {
            // 权限验证
            $admin = Admin::verify($req);
            // 管理员列表
            $admins = Admin::all();
            // 日志列表
            list($logs, $total) = Admin::logs((int) $req->input('pageNo', 1), $size);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [0, $th->getFile(), $th->getLine()] ];
        }

        // 返回结果
        return $res->html('admin/rbac/admin/logs', [
            'admins'    =>  $admins,
            'total'     =>  $total,
            'size'      =>  $size,
            'logs'      =>  $logs,
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}