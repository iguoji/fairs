<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\App;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 地区公共类
 */
class Region
{
    /**
     * 初始化数据
     * php minimal \\App\\Common\\Region init
     */
    public static function init() : bool
    {
        return \Swoole\Coroutine\run(function(){
            // 获取数据库
            $config = Config::get('db', []);
            $config['pool'] = 1;
            App::set('database', new \Minimal\Database\Manager($config, 1));

            // 当前时间
            $date = date('Y-m-d H:i:s');

            // 国家数据
            // https://reg.jd.com/p/countryItems
            // $json = ajax('https://reg.jd.com/p/countryItems');
            // if (empty($json)) {
            //     throw new Exception('很抱歉、国家数据请求失败！');
            // }
            // $json = mb_convert_encoding($json, 'utf-8', 'gb2312');
            // $countrys = json_decode($json, true);


            try {
                // 中国省级：
                // https://cdn.jsdelivr.net/gh/modood/Administrative-divisions-of-China@master/dist/provinces.json
                $json = ajax('https://cdn.jsdelivr.net/gh/modood/Administrative-divisions-of-China@master/dist/provinces.json');
                if (empty($json)) {
                    throw new Exception('很抱歉、中国省级数据请求失败！');
                }
                $provinces = json_decode($json, true);
                // 中国市级：
                // https://cdn.jsdelivr.net/gh/modood/Administrative-divisions-of-China@master/dist/cities.json
                $json = ajax('https://cdn.jsdelivr.net/gh/modood/Administrative-divisions-of-China@master/dist/cities.json');
                if (empty($json)) {
                    throw new Exception('很抱歉、中国市级数据请求失败！');
                }
                $citys = json_decode($json, true);
                // 中国区级：
                // https://cdn.jsdelivr.net/gh/modood/Administrative-divisions-of-China@master/dist/areas.json
                $json = ajax('https://cdn.jsdelivr.net/gh/modood/Administrative-divisions-of-China@master/dist/areas.json');
                if (empty($json)) {
                    throw new Exception('很抱歉、中国市级数据请求失败！');
                }
                $countys = json_decode($json, true);

                // 先清空表
                Db::table('region')->truncate();

                // 循环中国省级
                $provinceCount = count($provinces);
                foreach ($provinces as $key1 => $province) {
                    // 添加省级
                    $provinceAddress = $province['name'];
                    $provinceFilter = ['省', '市', '自治区', '回族', '维吾尔', '壮族'];
                    foreach ($provinceFilter as $fk1 => $pf) {
                        if (str_ends_with($provinceAddress, $pf)) {
                            $provinceAddress = substr($provinceAddress, 0, strlen($provinceAddress) - strlen($pf));
                        }
                    }
                    $bool = static::add([
                        'type'          =>  2,
                        'sort'          =>  ($provinceCount - $key1) * 10,
                        'province'      =>  $province['code'],
                        'province_name' =>  $province['name'],
                        'address'       =>  $provinceAddress,
                        'created_at'    =>  $date,
                    ]);
                    if (!$bool) {
                        throw new Exception('很抱歉、省级[' . $province['name'] . ']添加失败！');
                    }
                    // 循环中国市级
                    $cityCount = count($citys);
                    foreach ($citys as $key2 => $city) {
                        // 添加市级
                        if ($city['provinceCode'] == $province['code']) {
                            $cityAddress = $city['name'];
                            $cityFilter = ['市辖区'];
                            foreach ($cityFilter as $fk2 => $cf) {
                                if (str_ends_with($cityAddress, $cf)) {
                                    $cityAddress = substr($cityAddress, 0, strlen($cityAddress) - strlen($cf));
                                }
                            }
                            $bool = static::add([
                                'type'          =>  3,
                                'sort'          =>  ($cityCount - $key2) * 10,
                                'province'      =>  $province['code'],
                                'province_name' =>  $province['name'],
                                'city'          =>  $city['code'],
                                'city_name'     =>  $city['name'],
                                'address'       =>  $provinceAddress . $city['name'],
                                'created_at'    =>  $date,
                            ]);
                            if (!$bool) {
                                throw new Exception('很抱歉、市级[' . $province['name'] . $city['name'] . ']添加失败！');
                            }
                            // 循环中国区级
                            $countyCount = count($countys);
                            foreach ($countys as $key3 => $county) {
                                // 添加市级
                                if ($county['cityCode'] == $city['code'] && $county['provinceCode'] == $province['code']) {
                                    $bool = static::add([
                                        'type'          =>  4,
                                        'sort'          =>  ($countyCount - $key3) * 10,
                                        'province'      =>  $province['code'],
                                        'province_name' =>  $province['name'],
                                        'city'          =>  $city['code'],
                                        'city_name'     =>  $city['name'],
                                        'county'        =>  $county['code'],
                                        'county_name'   =>  $county['name'],
                                        'address'       =>  $provinceAddress . $cityAddress . $county['name'],
                                        'created_at'    =>  $date,
                                    ]);
                                    if (!$bool) {
                                        throw new Exception('很抱歉、区级[' . $province['name'] . $city['name'] . $county['name'] . ']添加失败！');
                                    }
                                }
                            }
                        }
                    }
                }

            } catch (\Throwable $th) {
                Log::debug($th->getMessage());
            }

        }) > 0;
    }

    /**
     * 根据地区类型获取字段
     */
    public static function getFieldByType(int $type) : array
    {
        $field = '';
        $fields = [];
        $parent = '';
        switch ($type) {
            case 1:
                $field = 'country';
                $fields = [['country' => 'id'], ['country_name' => 'name'], 'address'];
                break;
            case 2:
                $field = 'province';
                $fields = [['province' => 'id'], ['province_name' => 'name'], 'address'];
                $parent = 'country';
                break;
            case 3:
                $field = 'city';
                $fields = [['city' => 'id'], ['city_name' => 'name'], 'address'];
                $parent = 'province';
                break;
            case 4:
                $field = 'county';
                $fields = [['county' => 'id'], ['county_name' => 'name'], 'address'];
                $parent = 'city';
                break;
            case 5:
                $field = 'town';
                $fields = [['town' => 'id'], ['town_name' => 'name'], 'address'];
                $parent = 'county';
                break;
            case 6:
                $field = 'village';
                $fields = [['village' => 'id'], ['village_name' => 'name'], 'address'];
                $parent = 'town';
                break;
            default:
                break;
        }

        // 返回结果
        return [$field, $fields, $parent];
    }

    /**
     * 获取全部地区数据
     */
    public static function all(array $params = []) : array
    {
        // 查询对象
        $query = Db::table('region');

        // 显示字段
        $fields = [['province' => 'id'], ['province_name' => 'name'], 'address'];

        // 条件：按类型查询
        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }
        // 条件：按省份查询
        if (isset($params['province'])) {
            $fields = [['city' => 'id'], ['city_name' => 'name'], 'address'];
            $query->where('province', $params['province']);
        }
        // 条件：按城市查询
        if (isset($params['city'])) {
            $fields = [['county' => 'id'], ['county_name' => 'name'], 'address'];
            $query->where('city', $params['city']);
        }

        // 返回结果
        return $query->orderBy('type')->orderByDesc('sort')->all(...$fields);
    }

    /**
     * 获取指定地区数据
     */
    public static function get(string $id, int $type = 1) : array
    {
        list($field, $fields) = static::getFieldByType($type);

        return Db::table('region')->where('type', $type)->where($field, $id)->first(...$fields);
    }

    /**
     * 指定地区是否存在
     */
    public static function has(string $id, int $type = 1) : bool
    {
        if ($type == 1) {
            return in_array($id, ['86']);
        }
        return !empty(static::get($id, $type));
    }

    /**
     * 添加数据
     */
    public static function add(array $data) : bool
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        return Db::table('region')->insert($data) > 0;
    }

    /**
     * 获取所有国家信息
     */
    public static function countrys() : array
    {
        return [
            [
                'id'    =>  86,
                'name'  =>  '中国',
            ],
        ];
    }

    /**
     * 获取所有省份信息
     */
    public static function provinces(string $country = '86') : array
    {
        if ($country != '86') {
            // 其他国家
            return [];
        } else {
            // 中国
            return static::all([
                'type'  =>  2,
            ]);
        }
    }

    /**
     * 获取所有城市信息
     */
    public static function citys(string $province, string $country = '86') : array
    {
        if ($country != '86') {
            // 其他国家
            return [];
        } else {
            // 中国
            return static::all([
                'type'      =>  3,
                'province'  =>  $province,
            ]);
        }
    }

    /**
     * 获取所有区县信息
     */
    public static function countys(string $city, string $province, string $country = '86') : array
    {
        if ($country != '86') {
            // 其他国家
            return [];
        } else {
            // 中国
            return static::all([
                'type'      =>  4,
                'province'  =>  $province,
                'city'      =>  $city,
            ]);
        }
    }
}