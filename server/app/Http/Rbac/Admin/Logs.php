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
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->int('admin', '管理员编号');
        $validate->string('path', '路径');
        $validate->string('method', '请求方式');
        $validate->string('ip', 'IP')->ip();
        $validate->string('created_start_at', '起始时间')->date();
        $validate->string('created_end_at', '截止时间')->date();
        $validate->int('pageNo', '当前页码')->default(1);

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 最终结果
        $params = [];
        $admins = [];
        $logs = [];
        $total = 0;
        $size = 20;
        // 异常错误
        $exception = [];
        // 角色列表
        $rules = [];

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 参数验证
            $params = $this->validate($req->all());
            // 管理员列表
            $admins = Admin::all();

            // 日志列表
            $params['pageSize'] = $size;
            list($logs, $total) = Admin::logs($params);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [0, $th->getFile(), $th->getLine()] ];
        }

        // 返回结果
        return $res->html('admin/rbac/admin/logs', [
            'params'    =>  $params,
            'admins'    =>  $admins,
            'total'     =>  $total,
            'size'      =>  $size,
            'logs'      =>  $logs,
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}