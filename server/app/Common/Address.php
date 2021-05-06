<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 收货地址公共类
 */
class Address
{
    /**
     * 我的收货地址
     */
    public static function my(string $uid) : array
    {
        $result = Db::table('account_address', 'aa')
            ->leftJoin('region', 'r1', function($query){
                $query->where('r1.country', 'aa.country')->where('r1.type', 1);
            })
            ->leftJoin('region', 'r2', function($query){
                $query->where('r2.province', 'aa.province')->where('r2.type', 2);
            })
            ->leftJoin('region', 'r3', function($query){
                $query->where('r3.city', 'aa.city')->where('r3.type', 3);
            })
            ->leftJoin('region', 'r4', function($query){
                $query->where('r4.county', 'aa.county')->where('r4.type', 4);
            })
            ->leftJoin('region', 'r5', function($query){
                $query->where('r5.town', 'aa.town')->where('r5.type', 5);
            })
            ->where('aa.uid', $uid)
            ->where('aa.deleted_at')
            ->orderByDesc('aa.is_default')
            ->orderByDesc('aa.updated_at')
            ->all(
                'aa.id', 'aa.name', 'aa.phone', 'aa.is_default',
                'r1.country', 'r1.country_name',
                'r2.province', 'r2.province_name',
                'r3.city', 'r3.city_name',
                'r4.county', 'r4.county_name',
                'r5.town', 'r5.town_name',
                'r5.zip',
                'aa.address',
            );

        var_dump(Db::lastSql());
        return $result;
    }

    /**
     * 读取收货地址
     */
    public static function get(int $id) : array
    {
        return Db::table('account_address')->where('id', $id)->where('deleted_at')->first();
    }

    /**
     * 添加收货地址
     */
    public static function add(array $data) : bool
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Db::table('account_address')->insert($data) > 0;
    }

    /**
     * 修改收货地址
     */
    public static function upd(int $id, array $data) : bool
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return Db::table('account_address')->where('id', $id)->update($data) > 0;
    }

    /**
     * 取消现有默认地址
     */
    public static function cancelCurrentDefault(string $uid) : void
    {
        Db::table('account_address')->where('uid', $uid)->where('is_default', 1)->update([
            'is_default'    =>  0,
            'updated_at'    =>  date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 是否存在
     */
    public static function has(int $id, string $uid = null) : bool
    {
        $query = Db::table('account_address')->where('id', $id)->where('deleted_at');
        if (!is_null($uid)) {
            $query->where('uid', $uid);
        }
        return !empty($query->first());
    }
}