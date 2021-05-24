<?php
declare(strict_types=1);

namespace App\Http\Admin;

use App\Common\Admin;

/**
 * 后台首页
 */
class Index
{
    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 权限验证
        $aid = Admin::verify($req, $res);

        // 渲染页面
        return $res->html();
    }
}