<?php
declare(strict_types=1);

namespace App\Http\Admin;

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
        return $res->html();
    }
}