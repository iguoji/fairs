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
        return Db::query('
            SELECT
            `aa`.`id`, `aa`.`is_default`, `aa`.`name`, `aa`.`phone`,
            `r5`.`country`,     `r5`.`country_name`,
            `r5`.`province`,    `r5`.`province_name`,
            `r5`.`city`,        `r5`.`city_name`,
            `r5`.`county`,      `r5`.`county_name`,
            `r5`.`town`,        `r5`.`town_name`,
            `aa`.`address`
            FROM `account_address` AS `aa`
            INNER JOIN `region` AS `r5` ON `r5`.`town` = `aa`.`town` AND `r5`.`type` = 5
        ')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 读取收货地址
     */
    public static function read(int $id) : array
    {
        return Db::table('account_address')->where('id', $id)->first();
    }

    /**
     * 添加收货地址
     */
    public static function save(array $data) : bool
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Db::table('account_address')->insert($data) > 0;
    }

    /**
     * 修改收货地址
     */
    public static function edit(int $id, array $data) : bool
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
     * 删除收货地址
     */
    public static function remove(int $id) : bool
    {
        return Db::table('account_address')->where('id', $id)->delete() > 0;
    }

    /**
     * 是否存在
     */
    public static function exists(int $id, string $uid = null) : bool
    {
        $query = Db::table('account_address')->where('id', $id);
        if (!is_null($uid)) {
            $query->where('uid', $uid);
        }
        return !empty($query->first());
    }
}