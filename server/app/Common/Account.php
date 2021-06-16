<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Facades\App;
use Minimal\Facades\Log;
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
        // 编号长度
        $length = $length ?? Config::get('app.account.inviter.length', 5);
        // 循环次数
        $count = 1;
        // 所用字符
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $charsLenth = strlen($chars);
        // 循环生成
        while (true) {
            // 生成多个编号
            $array = [];
            for ($i = 0;$i < 10; $i++) {
                $str = '';
                for ($j = 0;$j < $length; $j++) {
                    $str .= $chars[mt_rand(0, $charsLenth - 1)];
                }
                $array[] = $str;
            }
            // 判断是否存在
            $values = Cache::hMGet('account:uids', $array);
            foreach ($values as $key => $value) {
                if (false === $value) {
                    return (string) $key;
                }
            }
            // 超过五次还没找到，长度增加
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
     * 模拟数据
     */
    public static function mock(string $inviter = '', string|int $count = 10000) : bool
    {
        return \Swoole\Coroutine\run(function() use($inviter, $count){
            // 获取缓存
            $config = Config::get('cache', []);
            $config['pool'] = 1;
            App::set('cache', new \Minimal\Cache\Manager($config, 1));

            // 获取数据库
            $config = Config::get('db', []);
            $config['pool'] = 1;
            App::set('database', new \Minimal\Database\Manager($config, 1));

            // 起始编号
            $index = Cache::inc('account:mock');
            // 添加数量
            $count = (int) $count;
            // 统一密码
            $password = '123456';
            // 已添加的用户编号
            $uids = [$inviter];

            try {
                // 开启事务
                Db::beginTransaction();

                // 当前时间
                $now = date('YmdHis');
                $date = date('Y-m-d H:i:s');
                $time = time();

                // 江浙沪地区数据
                $regions = Db::table('region')->where('province', 'in', [11, 31, 44])->where('type', 5)->all(
                    'country', 'province', 'city', 'county', 'town'
                );
                $regionCount = count($regions);

                // 循环处理
                for ($i = 0; $i < $count; $i++, $index++) {
                    // 随机地区
                    $region = $regions[mt_rand(0, $regionCount - 1)];
                    // 注册账号
                    $uid = Account::add([
                        'type'          =>  1,                                  // 账户类型
                        'status'        =>  mt_rand(0, 10) <= 1 ? 0 : 1,        // 账户状态
                        'level'         =>  1,                                  // 账户等级
                        'username'      =>  'test_' . $now . '_' . $index,
                        'password'      =>  $password,
                        'safeword'      =>  $password,
                        'phone'         =>  16800000000 + $index,
                        'email'         =>  $index . '_' . $now . '@test.dev',
                        'nickname'      =>  Str::random(mt_rand(2, 6), 4),
                        'gender'        =>  mt_rand(1, 2),
                        'birthday'      =>  date('Y-m-d', mt_rand(0, $time)),
                        'country'       =>  $region['country'],
                        'province'      =>  $region['province'],
                        'city'          =>  $region['city'],
                        'county'        =>  $region['county'],
                        'town'          =>  $region['town'],
                        'inviter'       =>  $uids[0],
                        'created_at'    =>  $date,
                    ]);
                    // 注册钱包
                    $bool = Wallet::new($uid);
                    if (!$bool) {
                        throw new Exception('很抱歉、钱包创建失败请重试！');
                    }
                    // 保存编号
                    $uids[] = $uid;
                    // 更换上级
                    if (($i > 0 && $i % 50 == 0) || empty($uids[0])) {
                        array_shift($uids);
                    }
                }

                // 提交事务
                Db::commit();
            } catch (\Throwable $th) {
                // 事务回滚
                Db::rollback();
                // 保存异常
                Log::debug($th->getMessage(), [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ]);
            }

            // 返回结果
            Cache::set('account:mock', $index);
            Log::debug('成功添加：' . count($uids));

        }) > 0;
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
        // 条件：按昵称查询
        if (isset($params['nickname'])) {
            $query->where('a.nickname', $params['nickname']);
        }
        // 条件：按状态查询
        if (isset($params['status'])) {
            $query->where('a.status', $params['status']);
        }
        // 条件：按上级邀请码查询
        if (isset($params['inviter'])) {
            $query->where('a.inviter', $params['inviter']);
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
            );
        // 循环数据
        foreach ($data as $key => $value) {
            // 地址
            $region = [];
            if (!empty($value['town'])) {
                $region = Region::get($value['town'], 5);
            } else if (!empty($value['county'])) {
                $region = Region::get($value['county'], 4);
            } else if (!empty($value['city'])) {
                $region = Region::get($value['city'], 3);
            } else if (!empty($value['province'])) {
                $region = Region::get($value['province'], 2);
            } else if (!empty($value['country'])) {
                $region = Region::get($value['country'], 1);
            }
            $value['address'] = $region['address'] ?? '';
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
        Cache::hSet('account:uids', $data['uid'], $id);

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
     * 查询 - 根据手机号码
     */
    public static function getByPhone(int|string $country, int|string $phone) : array
    {
        return Db::table('account')->where('country', (string) $country)->where('phone', (string) $phone)->where('deleted_at')->first();
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