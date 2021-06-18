<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 实名认证类
 */
class Authentication
{
    /**
     * 认证类型
     */
    const IDCARD = 1;       // 姓名 + 身份证号码 + 正面照片 + 反面照片

    /**
     * 认证状态
     */
    const EMPTY = -1;
    const SUCCESS = 1;
    const FAIL = 0;
    const WAIT = 2;

    /**
     * 所需资料
     */
    const TYPES  = [
        self::IDCARD    =>  '身份证认证',
    ];
    const FIELDS = [
        self::IDCARD    =>  ['name' => '真实姓名', 'idcard' => '身份证号码', 'front' => '正面照片', 'back' => '反面照片'],
    ];

    /**
     * 所有认证
     */
    public static function all(array $params) : array
    {
        // 查询对象
        $query = Db::table('account_authenticate', 'aa')
            ->leftJoin('account', 'a', 'a.uid', 'aa.uid');

        // 条件：按类型查询
        if (isset($params['type'])) {
            $query->where('aa.type', $params['type']);
        }
        // 条件：按状态查询
        if (isset($params['status'])) {
            $query->where('aa.status', $params['status']);
        }
        // 条件：按账号关键字查询
        if (isset($params['keyword'])) {
            $query->where(function($query1) use($params){
                $query1->orWhere('a.username', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.phone', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.email', 'like', '%' . $params['keyword'] . '%');
            });
        }
        // 条件：按姓名查询
        if (isset($params['name'])) {
            $query->where('aa.name', 'like', '%' . $params['name'] . '%');
        }
        // 条件：按证件号码查询
        if (isset($params['idcard'])) {
            $query->where('aa.idcard', 'like', '%' . $params['idcard'] . '%');
        }
        // 条件：按银行卡号查询
        if (isset($params['bankcard'])) {
            $query->where('aa.bankcard', 'like', '%' . $params['bankcard'] . '%');
        }
        // 条件：按国家查询
        if (isset($params['country'])) {
            $query->where('aa.country', $params['country']);
        }
        // 条件：按手机号码查询
        if (isset($params['phone'])) {
            $query->where('aa.phone', 'like', '%' . $params['phone'] . '%');
        }
        // 条件：按申请起始时间查询
        if (isset($params['created_start_at'])) {
            $query->where('aa.created_at', '>=', $params['created_start_at']);
        }
        // 条件：按申请截止时间查询
        if (isset($params['created_end_at'])) {
            $query->where('aa.created_at', '<=', $params['created_end_at']);
        }
        // 条件：已删除
        $query->where('aa.deleted_at');
        $query->where('a.deleted_at');

        // 数据总数
        $total = (clone $query)->count('aa.id');
        // 查询数据
        $data = $query->orderByDesc('id')->all(
            'aa.*',
            'a.username', ['a.country', 'account_country'], 'a.phone', 'a.email', 'a.nickname', 'a.avatar',
        );
        // 循环数据
        foreach ($data as $key => $value) {
            // 类型
            $value['type_name'] = static::TYPES[$value['type']] ?? '未知';
            // 保存
            $data[$key] = $value;
        }

        // 返回结果
        return [$data, $total];
    }

    /**
     * 获取认证信息
     */
    public static function get(int $id) : array
    {
        return Db::table('account_authenticate')->where('id', $id)->first();
    }

    /**
     * 是否存在认证信息
     */
    public static function has(int $id) : bool
    {
        return !empty(static::get($id));
    }

    /**
     * 提交认证
     */
    public static function add(array $data, int $type = self::IDCARD) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        // 保存数据
        return Db::table('account_authenticate')->insert($data) > 0;
    }

    /**
     * 修改认证
     */
    public static function upd(int $id, array $data) : bool
    {
        // 修改时间
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('account_authenticate')->where('id', $id)->update($data) > 0;
    }

    /**
     * 指定账户是否已认证通过
     */
    public static function passed(string $uid, int $type = self::IDCARD) : bool
    {
        return !empty(Db::table('account_authenticate')->where('uid', $uid)->where('type', $type)->where('status', self::SUCCESS)->first());
    }

    /**
     * 获取指定账户的认证状态
     */
    public static function status(string $uid, int $type = self::IDCARD) : int|array
    {
        $auth = Db::table('account_authenticate')->where('uid', $uid)->where('type', $type)->orderByDesc('id')->first();
        if (empty($auth)) {
            return self::EMPTY;
        }
        return $auth['status'] == self::FAIL ? [self::FAIL, $auth['reason']] : $auth['status'];
    }

    /**
     * 获取指定账户的最新认证信息
     */
    public static function getAccountLast(string $uid, int $status = self::SUCCESS) : array
    {
        return Db::table('account_authenticate')->where('uid', $uid)->where('status', $status)->orderByDesc('updated_at')->first();
    }

    /**
     * 批量根据编号获取用户编号
     */
    public static function getUidList(array $idList) : array
    {
        $data = Db::table('account_authenticate')->where('id', 'in', $idList)->all('id', 'uid');
        return array_column($data, 'uid', 'id') ?? [];
    }
}