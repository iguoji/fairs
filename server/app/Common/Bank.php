<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 银行公共类
 */
class Bank
{
    /**
     * 是否存在
     */
    public static function has(int $id) : bool
    {
        return !empty(Db::table('bank')->where('id', $id)->first());
    }

    /**
     * 读取数据
     */
    public static function get(int $id) : array
    {
        return Db::table('bank')->where('id', $id)->first();
    }
}