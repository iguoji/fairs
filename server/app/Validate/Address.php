<?php
declare(strict_types=1);

namespace App\Validate;

use Minimal\Foundation\Validate;
use App\Common\Region as RegionCommon;
use App\Common\Address as AddressCommon;

class Address
{
    /**
     * 添加收货地址
     */
    public static function save(array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('is_default', '是否设为默认地址')->in(0, 1)->default(0);

        $validate->string('country', '国家')->require()->length(1, 24)->digit()->call(function($value){
            return RegionCommon::has(country: $value);
        });
        $validate->string('province', '省份')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $value);
        })->requireWith('country');
        $validate->string('city', '市')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $value);
        })->requireWith('country', 'province');
        $validate->string('county', '区县')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $value);
        })->requireWith('country', 'province', 'city');
        $validate->string('town', '乡镇')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $values['county'] ?? '', town: $value);
        })->requireWith('country', 'province', 'city', 'county');

        $validate->string('address', '详细地址')->require();

        $validate->string('name', '联系人姓名')->require()->length(2, 30)->chsAlpha();
        $validate->int('phone', '电话号码')->require()->length(5, 30)->digit()->requireWith('country');

        return $validate->check();
    }

    /**
     * 编辑收货地址
     */
    public static function edit(string $uid, array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('id', '收货地址编号')->require()->digit()->call(function($value) use($uid){
            return AddressCommon::has((int) $value, $uid);
        }, message: '很抱歉、收货地址编号不存在！');
        $validate->int('is_default', '是否设为默认地址')->in(0, 1)->default(0);

        $validate->string('country', '国家')->require()->length(1, 24)->digit()->call(function($value){
            return RegionCommon::has(country: $value);
        });
        $validate->string('province', '省份')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $value);
        })->requireWith('country');
        $validate->string('city', '市')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $value);
        })->requireWith('country', 'province');
        $validate->string('county', '区县')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $value);
        })->requireWith('country', 'province', 'city');
        $validate->string('town', '乡镇')->require()->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $values['county'] ?? '', town: $value);
        })->requireWith('country', 'province', 'city', 'county');

        $validate->string('address', '详细地址')->require();

        $validate->string('name', '联系人姓名')->require()->length(2, 30)->chsAlpha();
        $validate->int('phone', '电话号码')->require()->length(5, 30)->digit()->requireWith('country');

        return $validate->check();
    }

    /**
     * 删除收货地址
     */
    public static function remove(string $uid, array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('id', '收货地址编号')->require()->digit()->call(function($value) use($uid){
            return AddressCommon::has((int) $value, $uid);
        }, message: '很抱歉、收货地址编号不存在！');

        return $validate->check();
    }

    /**
     * 设置为默认地址
     */
    public static function default(string $uid, array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('id', '收货地址编号')->require()->digit()->call(function($value) use($uid){
            return AddressCommon::has((int) $value, $uid);
        }, message: '很抱歉、收货地址编号不存在！');

        $validate->int('is_default', '是否设为默认地址')->in(0, 1)->default(1);

        return $validate->check();
    }
}