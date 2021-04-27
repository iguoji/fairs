<?php
declare(strict_types=1);

namespace App\Listener;

use Exception;
use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Contracts\Listener;

/**
 * 地区事件
 */
class Region implements Listener
{
    /**
     * 监听事件
     */
    public function events() : array
    {
        return [
            'Region:OnDivide',
        ];
    }

    /**
     * 事件处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 行政划分
        if ($event == 'Region:OnDivide') {
            return $this->divide();
        }

        // 返回结果
        return true;
    }

    /**
     * 行政划分
     * https://github.com/modood/Administrative-divisions-of-China
     * https://raw.githubusercontent.com/modood/Administrative-divisions-of-China/master/dist/pcas-code.json
     */
    public function divide() : bool
    {
        // 省级数据
        $json = file_get_contents(dirname(dirname(__DIR__)) . '/config/region.json');
        $provinces = json_decode($json, true);

        // 循环省级
        foreach ($provinces as $province) {
            // 保存省级
            $provinceItem = $this->divideSave(2, $province['code'], $province['name']);

            if (!empty($province['children'])) {
                // 循环市级
                foreach ($province['children'] as $city) {
                    // 保存市级
                    $cityItem = $this->divideSave(3, $city['code'], $city['name'], $provinceItem);

                    if (!empty($city['children'])) {
                        // 循环县级
                        foreach ($city['children'] as $county) {
                            // 保存县级
                            $countyItem = $this->divideSave(4, $county['code'], $county['name'], $cityItem);

                            if (!empty($county['children'])) {
                                // 循环镇级
                                foreach ($county['children'] as $town) {
                                    // 保存镇级
                                    $townItem = $this->divideSave(5, $town['code'], $town['name'], $countyItem);
                                }
                            }
                        }
                    }
                }
            }
        }

        // 返回结果
        return true;
    }

    /**
     * 行政划分 - 数据保存
     */
    public function divideSave(int $type, string $code, string $name, array $parent = [], array $extra = []) : array
    {
        $data = array_merge([
            'type'              =>  null,
            'zone'              =>  null,
            'zip'               =>  null,
            'country'           =>  '86',
            'country_name'      =>  '中国',
            'province'          =>  null,
            'province_name'     =>  null,
            'city'              =>  null,
            'city_name'         =>  null,
            'county'            =>  null,
            'county_name'       =>  null,
            'town'              =>  null,
            'town_name'         =>  null,
            'village'           =>  null,
            'village_type'      =>  null,
            'village_name'      =>  null,
            'address'           =>  null,
        ], $parent, $extra);
        $data['type'] = $type;
        $data['created_at'] = date('Y-m-d H:i:s');

        if ($type == 2) {
            $data['province'] = $code;
            $data['province_name'] = $name;
        } else if ($type == 3) {
            $data['city'] = $code;
            $data['city_name'] = $name;
        } else if ($type == 4) {
            $data['county'] = $code;
            $data['county_name'] = $name;
        } else if ($type == 5) {
            $data['town'] = $code;
            $data['town_name'] = $name;
        } else if ($type == 6) {
            $data['village'] = $code;
            $data['village_type'] = $vtype;
            $data['village_name'] = $name;
        }
        $data['address'] = ($data['address'] ?? '') . $name;

        Db::table('region')->insert($data);

        return $data;
    }

}