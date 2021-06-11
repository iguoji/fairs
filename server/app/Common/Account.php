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
     * 账户列表
     */
    public static function all(array $params = []) : array
    {
        // 查询对象
        $query = Db::table('account', 'a');

        // 条件：按账号查询
        if (isset($params['username'])) {
            $query->where('a.username', $params['username']);
        }
        // 条件：按国家查询
        if (isset($params['country'])) {
            $query->where('a.country', $params['country']);
        }
        // 条件：按手机查询
        if (isset($params['phone'])) {
            $query->where('a.phone', $params['phone']);
        }
        // 条件：按邮箱查询
        if (isset($params['email'])) {
            $query->where('a.email', $params['email']);
        }
        // 条件：按状态查询
        if (isset($params['status'])) {
            $query->where('a.status', $params['status']);
        }
        // 条件：按起始时间查询
        if (isset($params['created_start_at'])) {
            $query->where('a.created_at', '>=', $params['created_start_at']);
        }
        // 条件：按截止时间查询
        if (isset($params['created_end_at'])) {
            $query->where('a.created_at', '<=', $params['created_end_at']);
        }
        // 条件：已删除
        $query->where('a.deleted_at');

        // 数据总数
        $total = (clone $query)->count('id');
        // 查询数据
        $data = (clone $query)
            ->leftJoin('account', 'parent', 'parent.uid', 'a.inviter')
            ->leftJoin('region', 'r1', function($query){
                $query->where('r1.country', 'a.country')->where('r1.type', 1)->where('r1.country', '!=', null)->where('a.country', '!=', null);
            })
            ->leftJoin('region', 'r2', function($query){
                $query->where('r2.province', 'a.province')->where('r2.type', 2)->where('r2.province', '!=', null)->where('a.province', '!=', null);
            })
            ->leftJoin('region', 'r3', function($query){
                $query->where('r3.city', 'a.city')->where('r3.type', 3)->where('r3.city', '!=', null)->where('a.city', '!=', null);
            })
            ->leftJoin('region', 'r4', function($query){
                $query->where('r4.county', 'a.county')->where('r4.type', 4)->where('r4.county', '!=', null)->where('a.county', '!=', null);
            })
            ->leftJoin('region', 'r5', function($query){
                $query->where('r5.town', 'a.town')->where('r5.type', 5)->where('r5.town', '!=', null)->where('a.town', '!=', null);
            })
            ->page($params['pageNo'] ?? 1, $params['size'] ?? 20)
            ->orderByDesc('a.id')
            ->all(
                'a.*',

                ['parent.username' => 'parent_username'],
                ['parent.country' => 'parent_country'],
                ['parent.phone' => 'parent_phone'],
                ['parent.email' => 'parent_email'],
                ['parent.username' => 'parent_username'],
                ['parent.nickname' => 'parent_nickname'],

                ['r1.address' => 'r1_address'],
                ['r2.address' => 'r2_address'],
                ['r3.address' => 'r3_address'],
                ['r4.address' => 'r4_address'],
                ['r5.address' => 'r5_address'],
            );
        // 循环数据
        foreach ($data as $key => $value) {
            // 地址
            $value['address'] = $value['r5_address'] ?? $value['r4_address'] ?? $value['r3_address'] ?? $value['r2_address'] ?? $value['r1_address'];
            unset($value['r5_address'], $value['r4_address'], $value['r3_address'], $value['r2_address'], $value['r1_address']);
            // 上级
            $value['parent'] = [
                'uid'       =>  $value['inviter'],
                'username'  =>  $value['parent_username'],
                'country'   =>  $value['parent_country'],
                'phone'     =>  $value['parent_phone'],
                'email'     =>  $value['parent_email'],
                'username'  =>  $value['parent_username'],
                'nickname'  =>  $value['parent_nickname'],
            ];
            unset($value['parent_username'], $value['parent_country'], $value['parent_phone'], $value['parent_email'], $value['parent_username'], $value['parent_nickname']);

            $data[$key] = $value;
        }
        // 返回结果
        return [$data, $total];
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
    public static function get(string $uid, string $field = 'uid') : array
    {
        return Db::table('account')->where($field, $uid)->where('deleted_at')->first();
    }

    /**
     * 是否存在账户
     */
    public static function has(string $uid, string $field = 'uid') : bool
    {
        return !empty(static::get($uid, $field));
    }

    /**
     * 查询 - 根据用户名
     */
    public static function getByUsername(string $username) : array
    {
        return $this->get($username, 'username');
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
        return static::get($email, 'email');
    }

    /**
     * 删除账户
     */
    public static function del(string $uid) : bool
    {
        return static::upd($uid, [
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]);
    }
}