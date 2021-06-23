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
     * 所有银行卡
     */
    public static function all(array $params = []) : array
    {
        // 查询对象
        $query = Db::table('bank');

        // 数据总数
        $total = (clone $query)->count('id');
        // 查询数据
        $data = $query->orderByDesc('sort')->all();

        // 返回结果
        return [$data, $total];
    }

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