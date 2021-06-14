<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 地区公共类
 */
class Region
{
    /**
     * 指定地区是否存在
     */
    public static function has(string $country = null, string $province = null, string $city = null, string $county = null, string $town = null, string $village = null) : bool
    {
        if (!func_num_args()) {
            return false;
        }

        $query = Db::table('region');

        if (!is_null($country)) {
            $query->where('country', $country);
        }
        if (!is_null($province)) {
            $query->where('province', $province);
        }
        if (!is_null($city)) {
            $query->where('city', $city);
        }
        if (!is_null($county)) {
            $query->where('county', $county);
        }
        if (!is_null($town)) {
            $query->where('town', $town);
        }
        if (!is_null($village)) {
            $query->where('village', $village);
        }

        return !empty($query->first());
    }

    /**
     * 所有国家信息
     */
    public static function countrys() : array
    {
        return Db::table('region')->where('type', 1)->orderBy('id')->all();
    }

    /**
     * 获取数据
     */
    public static function data(int $type = 1, string|int $parent = null) : array
    {
        $fields = ['*'];
        $parentField = null;
        switch ($type) {
            case 1:
                $fields = [['country' => 'id'], ['country_name' => 'name']];
                break;
            case 2:
                $parentField = 'country';
                $fields = [['province' => 'id'], ['province_name' => 'name']];
                break;
            case 3:
                $parentField = 'province';
                $fields = [['city' => 'id'], ['city_name' => 'name']];
                break;
            case 4:
                $parentField = 'city';
                $fields = [['county' => 'id'], ['county_name' => 'name']];
                break;
            case 5:
                $parentField = 'county';
                $fields = [['town' => 'id'], ['town_name' => 'name']];
                break;
            case 6:
                $parentField = 'town';
                $fields = [['village' => 'id'], ['village_name' => 'name']];
                break;
            default:
                break;
        }

        // 查询对象
        $query = Db::table('region')->where('type', $type);
        // 按上级查询
        if (!is_null($parentField)) {
            $query->where($parentField, $parent);
        }

        // 返回结果
        return $query->orderBy('id')->all(...$fields);
    }
}