<?php
declare(strict_types=1);

namespace App\Http\Rbac\Node;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 节点列表
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
        $nodes = [];
        // 异常错误
        $exception = [];
        // 节点列表
        $rules = [];

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 节点列表
            $nodes = Rbac::getNodes(0, false);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->html('admin/rbac/node/index', [
            'nodes'     =>  $nodes,
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}