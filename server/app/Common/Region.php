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
    public static function exists(string $country = null, string $province = null, string $city = null, string $county = null, string $town = null, string $village = null) : bool
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
}