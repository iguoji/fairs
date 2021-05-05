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
     * 我的银行卡
     */
    public static function my(string $uid) : array
    {
        return Db::table('account_bank', 'ab')
            ->join('bank', 'b', 'b.id', 'ab.bank')
            ->orderByDesc('ab.is_default')
            ->orderByDesc('ab.updated_at')
            ->all(
                'ab.id', 'ab.is_default', 'ab.name', 'ab.card',
                ['b.name', 'bank'], 'b.type', 'ab.address'
            );
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
        return $query->count('id');
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
        return $query->count('id');
    }

    /**
     * 读取银行卡
     */
    public static function read(int $id) : array
    {
        return Db::table('account_bank')->where('id', $id)->first();
    }

    /**
     * 添加银行卡
     */
    public static function save(array $data) : bool
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return Db::table('account_bank')->insert($data) > 0;
    }

    /**
     * 修改银行卡
     */
    public static function edit(int $id, array $data) : bool
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
     * 删除银行卡
     */
    public static function remove(int $id) : bool
    {
        return Db::table('account_bank')->where('id', $id)->delete() > 0;
    }

    /**
     * 是否存在
     */
    public static function exists(int $id, string $uid = null) : bool
    {
        $query = Db::table('account_bank')->where('id', $id);
        if (!is_null($uid)) {
            $query->where('uid', $uid);
        }
        return !empty($query->first());
    }
}