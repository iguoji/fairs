<?php
declare(strict_types=1);

namespace App\Common;

use App\Common\Account;
use App\Common\AccountBank;
use App\Common\AccountAddress;
use App\Common\AccountPromotion;
use App\Common\Authentication;
use Minimal\Facades\Db;
use Minimal\Facades\App;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Facades\Config;
use Minimal\Facades\Request;
use Minimal\Support\Str;
use Minimal\Foundation\Exception;

/**
 * 系统类
 */
class System
{
    /**
     * 协程环境
     */
    public function coroutine(callable $callback) : mixed
    {
        return \Swoole\Coroutine\run(function() use($callback){
            // 获取缓存
            $config = Config::get('cache', []);
            $config['pool'] = 1;
            App::set('cache', new \Minimal\Cache\Manager($config, 1));

            // 获取数据库
            $config = Config::get('db', []);
            $config['pool'] = 1;
            App::set('database', new \Minimal\Database\Manager($config, 1));

            // 回调函数
            $callback();
        });
    }

    /**
     * 清空数据
     */
    public function clean() : mixed
    {
        return $this->coroutine(function(){
            // 清空缓存
            Cache::clear();
            // 清空数据库
            Db::table('account')->truncate();
            Db::table('account_address')->truncate();
            Db::table('account_authenticate')->truncate();
            Db::table('account_bank')->truncate();
            Db::table('account_link')->truncate();
            Db::table('account_promotion')->truncate();
            Db::table('wallet')->truncate();
            Db::table('wallet_record')->truncate();
        });
    }

    /**
     * 模拟数据
     * php minimal \\App\\Common\\System mock cU7E 10000
     */
    public function mock(string $inviter, string|int $count) : mixed
    {
        return $this->coroutine(function() use($inviter, $count){
            // 起始编号
            $index = Cache::inc('account:mock');
            // 统一密码
            $password = '123456';
            // 添加数量
            $count = (int) $count;
            // 已添加的用户编号
            $uids = [$inviter];
            Log::debug('开始创建模拟账号，起始编号：' . $index . '，数量：' . $count . '，原始上级：' . $inviter);

            try {
                // 开启事务
                Db::beginTransaction();

                // 当前时间
                $now = date('YmdHis');
                $date = date('Y-m-d H:i:s');
                $time = time();

                // 江浙沪地区数据
                $regions = Db::table('region')->where('province', 'in', ['11', '31', '44'])->where('type', 4)->all('province', 'city', 'county');
                $regionCount = count($regions);

                // 循环处理
                for ($i = 0; $i < $count; $i++, $index++) {
                    // 随机地区
                    $region = $regions[mt_rand(0, $regionCount - 1)];
                    // 注册账号
                    $uid = Account::new([
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
                        'province'      =>  $region['province'],
                        'city'          =>  $region['city'],
                        'county'        =>  $region['county'],
                        'inviter'       =>  $uids[0],
                        'created_at'    =>  $date,
                    ]);
                    // 实名认证
                    $this->mockAccountAuthentication($uid);
                    // 银行卡
                    $this->mockAccountBank($uid);
                    // 收货地址
                    $this->mockAccountAddress($uid, $regions);
                    // 保存编号
                    $uids[] = $uid;
                    // 更换上级
                    if (($i > 0 && $i % 20 == 0) || empty($uids[0])) {
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
        });
    }

    /**
     * 模拟账户数据
     * php minimal \\App\\Common\\System mockAccount 上级编号 a 6 3
     */
    public function mockAccount(string $inviter, string $prefix = 'a', string|int $length = 6, string|int $count = 3) : mixed
    {
        return $this->coroutine(function() use($inviter, $prefix, $length, $count){
            // 起始索引
            $index = (int) str_pad('1', $length, '0');
            // 添加数量
            $count = (int) $count;
            // 账号长度
            $length = (int) $length;
            // 统一密码
            $password = '123456';
            Log::debug('开始创建模拟账号，起始编号：' . $index . '，数量：' . $count . '，原始上级：' . $inviter);

            try {
                // 开启事务
                Db::beginTransaction();

                // 江浙沪地区数据
                $regions = Db::table('region')->where('province', 'in', ['11', '31', '44'])->where('type', 4)->all('province', 'city', 'county');
                $regionCount = count($regions);

                // 当前层次
                $depth = 1;
                // 递归添加
                $fn = function(string $inviter, string $prefix, int $depth) use($count, $regions, $regionCount, $length, $password, &$fn){
                    // 循环处理
                    for ($i = 1; $i <= $count; $i++) {
                        // 随机地区
                        $region = $regions[mt_rand(0, $regionCount - 1)];
                        // 注册账号
                        $uid = Account::new([
                            'type'          =>  1,                                  // 账户类型
                            'status'        =>  mt_rand(0, 10) <= 1 ? 0 : 1,        // 账户状态
                            'level'         =>  1,                                  // 账户等级
                            'username'      =>  str_pad($prefix . $i, $length, '0'),
                            'password'      =>  $password,
                            'safeword'      =>  $password,
                            'nickname'      =>  Str::random(mt_rand(2, 6), 4),
                            'gender'        =>  mt_rand(1, 2),
                            'birthday'      =>  date('Y-m-d', mt_rand(0, time())),
                            'province'      =>  $region['province'],
                            'city'          =>  $region['city'],
                            'county'        =>  $region['county'],
                            'inviter'       =>  $inviter,
                            'created_at'    =>  date('Y-m-d H:i:s'),
                        ]);
                        // 继续注册下级
                        if ($depth < AccountPromotion::MAX) {
                            $fn($uid, $prefix . $i, $depth + 1);
                        }
                    }
                };
                // 执行添加
                $fn($inviter, $prefix, $depth);

                // 提交事务
                Db::commit();
            } catch (\Throwable $th) {
                // 事务回滚
                Db::rollback();
                // 保存异常
                Log::debug($th->getMessage(), [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ]);
            }

            // 返回结果
            Log::debug('添加成功');
        });
    }

    /**
     * 模拟实名认证
     */
    public function mockAccountAuthentication(string $uid) : mixed
    {
        // 身份证认证
        $status = mt_rand(0, 2);
        $bool = Authentication::add([
            'type'          =>  1,
            'status'        =>  $status,
            'uid'           =>  $uid,
            'name'          =>  Str::random(mt_rand(2, 4), 4),
            'idcard'        =>  Str::random(18, 1),
            'front'         =>  '/front.png',
            'back'          =>  '/back.png',
            'reason'        =>  $status === 0 ? '很抱歉、资料不齐全！' : null,
        ]);
        if (!$bool) {
            throw new Exception('模拟实名认证 - 身份证认证失败！');
        }
        // 如果失败，可以尝试再次申请
        if (mt_rand(0, 10) < 2) {
            $this->mockAccountAuthentication($uid);
        }
        // 返回结果
        return true;
    }

    /**
     * 模拟银行卡
     */
    public function mockAccountBank(string $uid) : mixed
    {
        // 支付宝
        $bool = AccountBank::add([
            'is_default'    =>  1,
            'uid'           =>  $uid,
            'bank'          =>  1,
            'name'          =>  Str::random(mt_rand(2, 4), 4),
            'card'          =>  Str::random(12, 1),
        ]);
        if (!$bool) {
            throw new Exception('很抱歉、模拟银行卡 - 支付宝失败！');
        }
        // 返回结果
        return true;
    }

    /**
     * 模拟收货地址
     */
    public function mockAccountAddress(string $uid, array $regions) : mixed
    {
        // 随机数量
        $count = mt_rand(1, 5);
        // 地区数量
        $regionCount = count($regions);
        // 循环添加
        for ($i = 0;$i < $count; $i++) {
            // 随机地区
            $region = $regions[mt_rand(0, $regionCount - 1)];
            // 添加地址
            $bool = AccountAddress::add([
                'is_default'        =>  ($i === 0 ? 1 : 0),
                'uid'               =>  $uid,
                'name'              =>  Str::random(mt_rand(2, 4), 4),
                'phone'             =>  '168' . Str::random(8, 1),
                'country'           =>  '86',
                'province'          =>  $region['province'],
                'city'              =>  $region['city'],
                'county'            =>  $region['county'],
                'address'           =>  Str::random(mt_rand(10, 20), 4),
            ]);
            if (!$bool) {
                throw new Exception('很抱歉、模拟收货地址失败！');
            }
        }
        // 返回结果
        return true;
    }
}