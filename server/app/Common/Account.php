<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Support\Str;
use Minimal\Facades\Cache;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 账户公共类
 */
class Account
{
    /**
     * 用户编号
     */
    public static function uid(int $length = null) : string
    {
        $length = $length ?? Config::get('app.account.inviter.length', 5);
        $count = 1;
        while (true) {
            $array = str_split(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'), $length);
            $values = Cache::mGet(array_map(fn($s) => 'account:uid:' . $s, $array));
            foreach ($values as $key => $value) {
                if (false === $value) {
                    return $array[$key];
                }
            }
            $count++;
            if (!($count % 5)) {
                $length++;
            }
        }
    }

    /**
     * 密码加密
     */
    public static function encrypt(string $chars, string $secret = null) : string
    {
        $secret = $secret ?? Config::get('app.secret', 'QQ123567231');
        return md5(sha1($secret) . crc32($chars));
    }

    /**
     * 授权验证
     */
    public static function verify($req) : mixed
    {
        // 获取信息
        $account = $req->session->get('account');
        if (empty($account)) {
            throw new Exception('很抱歉、请登录后再操作！', 403);
        }

        // 返回结果
        return $account['uid'];
    }

    /**
     * 登录账户
     */
    public static function signin($req, $account, $expire = null) : array
    {
        // 过期时间
        $expire = $expire ?? $req->session->getConfig('expire');
        // 保存数据
        $req->session->set('account', $account, $expire);
        // 个人资料
        $account = self::profile($account);
        // 保存令牌
        $account['token'] = $req->session->id();

        // 返回结果
        return $account;
    }

    /**
     * 个人资料
     */
    public static function profile(array $account) : array
    {
        // 可用字段
        $data = array_intersect_key($account, [
            'type'      =>  1,
            'level'     =>  1,
            'status'    =>  1,

            'nickname'  =>  '',
            'avatar'    =>  '',
            'gender'    =>  '',
            'birthday'  =>  '',
        ]);

        // 个人钱包
        $data['wallet'] = array_intersect_key(Wallet::get($account['uid']), [
            'money'         =>  0,
            'money2'        =>  0,
            'score'         =>  0,
            'score2'        =>  0,
            'commission'    =>  0,
            'commission2'   =>  0,
            'spend'         =>  0,
            'spend2'        =>  0,
        ]);


        // 实名认证
        $data['authentic'] = Authentic::status($account['uid'], Config::get('app.account.authentic', Authentic::IDCARD));

        // 返回结果
        return $data;
    }



    /**
     * 添加账号
     */
    public static function add(array $data) : string
    {
        // 用户编号
        if (!isset($data['uid'])) {
            $data['uid'] = self::uid();
        }

        // 密码加密
        if (isset($data['password'])) {
            $data['password'] = self::encrypt($data['password']);
        }
        if (isset($data['safeword'])) {
            $data['safeword'] = self::encrypt($data['safeword']);
        }

        // 默认参数
        $data = array_merge([
            'type'      =>  1,
            'status'    =>  1,
            'level'     =>  1,
            'created_at'=>  date('Y-m-d H:i:s'),
        ], $data);

        // 保存数据
        if (!Db::table('account')->insert($data)) {
            throw new Exception('很抱歉、账户注册失败请重试！');
        }

        // 保存编号
        $id = Db::lastInsertId();
        Cache::set('account:uid:' . $data['uid'], $id);

        // 返回结果
        return $data['uid'];
    }

    /**
     * 修改资料
     */
    public static function upd(string $uid, array $data) : bool
    {
        // 密码加密
        if (isset($data['password'])) {
            $data['password'] = self::encrypt($data['password']);
        }
        if (isset($data['safeword'])) {
            $data['safeword'] = self::encrypt($data['safeword']);
        }

        // 修改时间
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('account')->where('uid', $uid)->update($data) > 0;
    }

    /**
     * 查询 - 根据用户编号
     */
    public static function get(string $uid) : array
    {
        return Db::table('account')->where('uid', $uid)->where('deleted_at')->first();
    }

    /**
     * 查询 - 根据用户名
     */
    public static function getByUsername(string $username) : array
    {
        return Db::table('account')->where('username', $username)->where('deleted_at')->first();
    }

    /**
     * 查询 - 根据手机号码
     */
    public static function getByPhone(int|string $country, int|string $phone) : array
    {
        return Db::table('account')->where('country', (string) $country)->where('phone', (string) $phone)->where('deleted_at')->first();
    }

    /**
     * 查询 - 根据邮箱地址
     */
    public static function getByEmail(string $email) : array
    {
        return Db::table('account')->where('email', $email)->where('deleted_at')->first();
    }
}