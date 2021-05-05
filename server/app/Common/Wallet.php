<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Foundation\Exception;

/**
 * 钱包公共类
 */
class Wallet
{
    /**
     * 创建钱包
     */
    public static function new(string $uid) : bool
    {
        return Db::table('wallet')->insert([
            'uid'       =>  $uid,
            'created_at'=>  date('Y-m-d H:i:s'),
        ]) > 0;
    }

    /**
     * 存在钱包
     */
    public static function has(string $uid) : bool
    {
        return !empty(Db::table('wallet')->where('uid', $uid)->first());
    }

    /**
     * 获取钱包
     */
    public static function get(string $uid) : array
    {
        return Db::table('wallet')->where('uid', $uid)->first();
    }

    /**
     * 获取货币数量
     */
    public static function value(string $uid, string $coin) : float
    {
        return Db::table('wallet')->where('uid', $uid)->value($coin);
    }

    /**
     * 货币自增
     */
    public static function inc(int $type, string $oid, string $uid, string $coin, float $number) : float
    {
        $config = Config::get('wallet.' . $coin);
        if (empty($config)) {
            throw new Exception('很抱歉、系统尚未配置[' . $coin . ']货币！');
        }
        if ($number != 0) {
            $bool = Db::table('wallet')->where('uid', $uid)->inc($coin, $number);
            if (!$bool) {
                throw new Exception('很抱歉、' . $config['name'] . '增加失败！');
            }
        }
        return (float) self::value($uid, $coin);
    }

    /**
     * 货币自减
     */
    public static function dec(int $type, string $oid, string $uid, string $coin, float $number) : float
    {
        $config = Config::get('wallet.' . $coin);
        if (empty($config)) {
            throw new Exception('很抱歉、系统尚未配置[' . $coin . ']货币！');
        }
        if ($number != 0) {
            $bool = Db::table('wallet')->where('uid', $uid)->dec($coin, $number);
            if (!$bool) {
                throw new Exception('很抱歉、' . $config['name'] . '减少失败！');
            }
        }
        return (float) self::value($uid, $coin);
    }

    /**
     * 流水记录
     */
    public static function record(int $type, string $oid, string $uid, string $coin, float $before, float $number, float $after = null) : bool
    {
        return Db::table('wallet_record')->insert([
            'type'      =>  $type,
            'oid'       =>  $oid,
            'uid'       =>  $uid,
            'coin'      =>  $coin,
            'before'    =>  $before,
            'number'    =>  $number,
            'after'     =>  $after ?? $before + $number,
        ]) > 0;
    }
}