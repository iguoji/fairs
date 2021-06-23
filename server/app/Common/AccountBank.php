<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 账户银行卡公共类
 */
class AccountBank
{
    /**
     * 全部银行卡
     */
    public static function all(array $params) : array
    {
        // 查询对象
        $query = Db::table('account_bank', 'ab')
            ->join('account', 'a', 'a.uid', 'ab.uid')
            ->join('bank', 'b', 'b.id', 'ab.bank');

        // 条件：是否为默认
        if (isset($params['is_default'])) {
            $query->where('ab.is_default', $params['is_default']);
        }
        // 条件：按账户查询
        if (isset($params['uid'])) {
            $query->where('ab.uid', $params['uid']);
        }
        // 条件：按账户关键字查询
        if (isset($params['keyword'])) {
            $query->where(function($query1) use($params){
                $query1->orWhere('a.username', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.phone', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.email', 'like', '%' . $params['keyword'] . '%');
            });
        }
        // 条件：按银行查询
        if (isset($params['bank'])) {
            $query->where('ab.bank', $params['bank']);
        }
        // 条件：按姓名查询
        if (isset($params['name'])) {
            $query->where('ab.name', 'like', '%' . $params['name'] . '%');
        }
        // 条件：按卡号查询
        if (isset($params['card'])) {
            $query->where('ab.card', 'like', '%' . $params['card'] . '%');
        }
        // 条件：按添加起始时间查询
        if (isset($params['created_start_at'])) {
            $query->where('ab.created_at', '>=', $params['created_start_at']);
        }
        // 条件：按添加截止时间查询
        if (isset($params['created_end_at'])) {
            $query->where('ab.created_at', '<=', $params['created_end_at']);
        }
        // 条件：不含已删除
        $query->where('ab.deleted_at');

        // 数据总数
        $total = (clone $query)->count('ab.id');
        // 查询数据
        $data = $query->page($params['pageNo'] ?? 1, $params['size'] ?? 20)
            ->orderByDesc('ab.id')
            ->all(
                'ab.id', 'ab.is_default', 'ab.name', 'ab.card', 'ab.address', 'ab.created_at',
                'ab.bank', ['b.name' => 'bank_name'], 'b.type',
                'a.uid', 'a.username', 'a.country', 'a.phone', 'a.email', 'a.avatar', 'a.nickname',
            );
        // 整理数据
        // 返回结果
        return [$data, $total];
    }

    /**
     * 我的银行卡
     */
    public static function my(string $uid) : array
    {
        // 查询对象
        $query = Db::table('account_bank', 'ab')
            ->join('bank', 'b', 'b.id', 'ab.bank');

        // 条件：是否为默认
        if (isset($params['is_default'])) {
            $query->where('ab.is_default', $params['is_default']);
        }
        // 条件：按银行查询
        if (isset($params['bank'])) {
            $query->where('ab.bank', $params['bank']);
        }
        // 条件：按姓名查询
        if (isset($params['name'])) {
            $query->where('ab.name', 'like', '%' . $params['name'] . '%');
        }
        // 条件：按卡号查询
        if (isset($params['card'])) {
            $query->where('ab.card', 'like', '%' . $params['card'] . '%');
        }
        // 条件：按添加起始时间查询
        if (isset($params['created_start_at'])) {
            $query->where('ab.created_at', '>=', $params['created_start_at']);
        }
        // 条件：按添加截止时间查询
        if (isset($params['created_end_at'])) {
            $query->where('ab.created_at', '<=', $params['created_end_at']);
        }
        // 条件：不含已删除
        $query->where('ab.deleted_at');

        // 数据总数
        $total = (clone $query)->count('ab.id');
        // 查询数据
        $data = $query->page($params['pageNo'] ?? 1, $params['size'] ?? 20)
            ->orderByDesc('ab.is_default')
            ->orderByDesc('ab.id')
            ->all(
                'ab.id', 'ab.is_default', 'ab.name', 'ab.card', 'ab.address',
                'ab.bank', ['b.name' => 'bank_name'], 'b.type',
            );
        // 整理数据
        // 返回结果
        return [$data, $total];
    }

    /**
     * 总绑卡数量
     */
    public static function count(string $uid = null) : int
    {
        $query = Db::table('account_bank');
        if (!is_null($uid)) {
            $query->where('uid', $uid);
        }
        return $query->where('deleted_at')->count('id');
    }

    /**
     * 单人指定银行绑卡数量
     */
    public static function singleCount(int $bank, string $uid = null) : int
    {
        $query = Db::table('account_bank')->where('bank', $bank);
        if (!is_null($uid)) {
            $query->where('uid', $uid);
        }
        return $query->where('deleted_at')->count('id');
    }

    /**
     * 读取银行卡
     */
    public static function get(int $id) : array
    {
        return Db::table('account_bank')->where('id', $id)->where('deleted_at')->first();
    }

    /**
     * 添加银行卡
     */
    public static function add(array $data) : bool
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Db::table('account_bank')->insert($data) > 0;
    }

    /**
     * 修改银行卡
     */
    public static function upd(int $id, array $data) : bool
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return Db::table('account_bank')->where('id', $id)->update($data) > 0;
    }

    /**
     * 取消现有默认
     */
    public static function cancelCurrentDefault(string $uid) : void
    {
        Db::table('account_bank')->where('uid', $uid)->where('is_default', 1)->update([
            'is_default'    =>  0,
            'updated_at'    =>  date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 是否存在
     */
    public static function has(int $id, string $uid = null) : bool
    {
        $query = Db::table('account_bank')->where('id', $id)->where('deleted_at');
        if (!is_null($uid)) {
            $query->where('uid', $uid);
        }
        return !empty($query->first());
    }
}