<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Facades\Cache;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 商品品牌类
 */
class Brand
{
    /**
     * 数据列表
     */
    public static function all(array $params = []) : array
    {
        // 查询对象
        $query = Db::table('brand', 'b');

        // 条件：按状态查询
        if (isset($params['status'])) {
            $query->where('b.status', $params['status']);
        }
        // 条件：不含已删除
        $query->where('b.deleted_at');

        // 数据总数
        $total = (clone $query)->count('b.id');
        // 查询数据
        $data = $query->orderByDesc('b.sort')->all();
        // 整理数据
        foreach ($data as $key => $value) {
            # code...
        }
        // 返回结果
        return [$data, $total];
    }

    /**
     * 添加数据
     */
    public static function add(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        // 返回结果
        return Db::table('brand')->insert($data) > 0;
    }

    /**
     * 修改数据
     */
    public static function upd(int $pk, array $data) : bool
    {
        // 修改时间
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('brand')->where('id', $pk)->update($data) > 0;
    }

    /**
     * 查询数据
     */
    public static function get(int $pk, string $field = 'id') : array
    {
        return Db::table('brand')->where($field, $pk)->where('deleted_at')->first();
    }

    /**
     * 是否存在指定数据
     */
    public static function has(int $pk, string $field = 'id') : bool
    {
        return !empty(static::get($pk, $field));
    }

    /**
     * 删除数据
     */
    public static function del(int $pk) : bool
    {
        return static::upd($pk, [
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]);
    }
}