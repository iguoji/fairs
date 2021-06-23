<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 账户收货地址公共类
 */
class AccountAddress
{
    /**
     * 所有数据
     */
    public static function all(array $params) : array
    {
        // 查询对象
        $query = Db::table('account_address', 'aa')
            ->join('account', 'a', 'a.uid', 'aa.uid');

        // 条件：是否为默认
        if (isset($params['is_default'])) {
            $query->where('aa.is_default', $params['is_default']);
        }
        // 条件：按账户查询
        if (isset($params['uid'])) {
            $query->where('aa.uid', $params['uid']);
        }
        // 条件：按账户关键字查询
        if (isset($params['keyword'])) {
            $query->where(function($query1) use($params){
                $query1->orWhere('a.username', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.phone', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.email', 'like', '%' . $params['keyword'] . '%');
            });
        }
        // 条件：按联系人查询
        if (isset($params['name'])) {
            $query->where('aa.name', 'like', '%' . $params['name'] . '%');
        }
        // 条件：按联系号码查询
        if (isset($params['phone'])) {
            $query->where('aa.phone', 'like', '%' . $params['phone'] . '%');
        }
        // 条件：按国家查询
        if (isset($params['country'])) {
            $query->where('aa.country', $params['country']);
        }
        // 条件：按省查询
        if (isset($params['province'])) {
            $query->where('aa.province', $params['province']);
        }
        // 条件：按市查询
        if (isset($params['city'])) {
            $query->where('aa.city', $params['city']);
        }
        // 条件：按区查询
        if (isset($params['county'])) {
            $query->where('aa.county', $params['county']);
        }
        // 条件：按添加起始时间查询
        if (isset($params['created_start_at'])) {
            $query->where('aa.created_at', '>=', $params['created_start_at']);
        }
        // 条件：按添加截止时间查询
        if (isset($params['created_end_at'])) {
            $query->where('aa.created_at', '<=', $params['created_end_at']);
        }
        // 条件：不含已删除
        $query->where('aa.deleted_at');

        // 数据总数
        $total = (clone $query)->count('aa.id');
        // 查询数据
        $data = $query->page($params['pageNo'] ?? 1, $params['size'] ?? 20)
            ->orderByDesc('aa.id')
            ->all(
                'aa.id', 'aa.is_default', 'aa.name', 'aa.country', 'aa.phone', 'aa.province', 'aa.city', 'aa.county', 'aa.address', 'aa.created_at',
                'a.uid', 'a.username', ['a.country' => 'account_country'], ['a.phone' => 'account_phone'], 'a.email', 'a.avatar', 'a.nickname',
            );
        // 整理数据
        foreach ($data as $key => $value) {
            // 地址
            $region = Region::find($value);
            $value['province_name'] = $region['province_name'] ?? '';
            $value['city_name'] = $region['city_name'] ?? '';
            $value['county_name'] = $region['county_name'] ?? '';
            // 保存
            $data[$key] = $value;
        }

        // 返回结果
        return [$data, $total];
    }

    /**
     * 我的收货地址
     */
    public static function my(string $uid) : array
    {
        // 查询对象
        $query = Db::table('account_address', 'aa');

        // 条件：按账户查询
        $query->where('aa.uid', $uid);
        // 条件：不含已删除
        $query->where('aa.deleted_at');

        // 查询数据
        $data = $query->orderByDesc('aa.is_default')
            ->orderByDesc('aa.id')
            ->all(
                'aa.id', 'aa.is_default', 'aa.name', 'aa.country', 'aa.phone', 'aa.province', 'aa.city', 'aa.county', 'aa.address', 'aa.created_at',
            );
        // 整理数据
        foreach ($data as $key => $value) {
            // 地址
            $region = Region::find($value);
            $value['province_name'] = $region['province_name'] ?? '';
            $value['city_name'] = $region['city_name'] ?? '';
            $value['county_name'] = $region['county_name'] ?? '';
            // 保存
            $data[$key] = $value;
        }

        // 返回结果
        return $data;
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