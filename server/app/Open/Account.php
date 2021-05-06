<?php
declare(strict_types=1);

namespace App\Open;

use Throwable;
use Minimal\Facades\Db;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;
use App\Common\Sms as SmsCommon;
use App\Common\Mail as MailCommon;
use App\Common\Wallet as WalletCommon;
use App\Common\Account as AccountCommon;
use App\Common\Authentic as AuthenticCommon;
use App\Validate\Account as AccountValidate;

/**
 * 账户类
 */
class Account
{
    /**
     * 账号注册
     */
    public function signup($req, $res)
    {
        // 参数检查
        $data = AccountValidate::signup($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 补充账号
            if (isset($data['country']) && isset($data['phone']) && !isset($data['username'])) {
                // 手机注册
                $data['username'] = $data['country'] . '_' . $data['phone'];
            } else if (isset($data['email']) && !isset($data['username'])) {
                // 邮箱注册
                $data['username'] = str_replace(['@', '.'], ['_', '_'], $data['email']);
            }

            // 注册账号
            $uid = AccountCommon::add($data);

            // 注册钱包
            $bool = WalletCommon::new($uid);
            if (!$bool) {
                throw new Exception('很抱歉、钱包创建失败请重试！');
            }

            // 立即登录
            $account = AccountCommon::signin(AccountCommon::get($uid));

            // 提交事务
            Db::commit();

            // 返回结果
            return $account;
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 账号登录
     */
    public function signin($req, $res)
    {
        // 参数检查
        $data = AccountValidate::signin($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 按情况登录
            if (isset($data['country']) && isset($data['phone'])) {
                // 手机
                $account = AccountCommon::getByPhone($data['country'], $data['phone']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该手机号码不存在！');
                }
            } else if (isset($data['email'])) {
                // 邮箱
                $account = AccountCommon::getByEmail($data['email']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该邮箱地址不存在！');
                }
            } else {
                // 账号
                $account = AccountCommon::getByUsername($data['username']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该账号不存在！');
                }
            }

            // 验证密码
            if (isset($data['password']) && $account['password'] !== AccountCommon::encrypt($data['password'])) {
                throw new Exception('很抱歉、登录密码不正确！');
            }

            // 执行登录
            $account = AccountCommon::signin($account);

            // 提交事务
            Db::commit();

            // 返回结果
            return $account;
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 个人资料
     */
    public function profile($req, $res)
    {
        // 查询账号
        $account = AccountCommon::get($req->uid);

        // 返回数据
        return AccountCommon::profile($account);
    }

    /**
     * 编辑资料
     */
    public function edit($req, $res)
    {
        // 参数检查
        $data = AccountValidate::edit($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 没有任何修改
            if (empty($data)) {
                throw new Exception('很抱歉、没有任何修改！');
            }

            // 修改资料
            $bool = AccountCommon::upd($req->uid, $data);
            if (!$bool) {
                throw new Exception('很抱歉、修改失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 实名认证
     */
    public function authentic($req, $res)
    {
        try {
            // 开启事务
            Db::beginTransaction();

            // 获取类型
            $type = Config::get('app.account.authentic', AuthenticCommon::IDCARD);

            // 认证状态
            $status = AuthenticCommon::status($req->uid, $type);
            if ($status === AuthenticCommon::WAIT) {
                throw new Exception('很抱歉、您的资料已在审核中请勿重复提交！');
            } else if ($status == AuthenticCommon::SUCCESS) {
                throw new Exception('很抱歉、您已认证通过请勿重复提交！');
            }

            // 参数检查
            $data = AccountValidate::authentic($req->post ?? []);

            // 提交认证
            $data['uid'] = $req->uid;
            if (!AuthenticCommon::add($data, $type)) {
                throw new Exception('很抱歉、认证资料保存失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 绑定安全密码
     */
    public function safeword($req, $res)
    {
        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
            $account = AccountCommon::get($req->uid);
            if (!empty($account['safeword'])) {
                throw new Exception('很抱歉、您已绑定过安全密码了！');
            }

            // 参数检查
            $data = AccountValidate::safeword($req->post ?? []);

            // 按情况处理
            if ($data['action'] == 'mobile') {
                // 未绑定手机
                if (empty($account['phone']) || empty($account['country'])) {
                    throw new Exception('很抱歉、您尚未绑定手机号码！');
                }
                // 验证码核实
                if (!SmsCommon::check($account['country'], $account['phone'], $data['verify_code'])) {
                    throw new Exception('很抱歉、短信验证码不正确！');
                }
            } else if ($data['action'] == 'email') {
                // 未绑定邮箱
                if (empty($account['email'])) {
                    throw new Exception('很抱歉、您尚未绑定邮箱地址！');
                }
                // 验证码核实
                if (!MailCommon::check($account['email'], $data['verify_code'])) {
                    throw new Exception('很抱歉、邮箱验证码不正确！');
                }
            } else {
                if ($account['password'] !== AccountCommon::encrypt($data['password'])) {
                    throw new Exception('很抱歉、登录密码不正确！');
                }
            }

            // 修改资料
            $bool = AccountCommon::upd($account['uid'], [
                'safeword'   =>  $data['safeword']
            ]);
            if (!$bool) {
                throw new Exception('很抱歉、绑定失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 绑定手机号码
     */
    public function bindPhone($req, $res)
    {
        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
            $account = AccountCommon::get($req->uid);
            if (!empty($account['phone'])) {
                throw new Exception('很抱歉、您已绑定过手机号码了！');
            }

            // 参数检查
            $data = AccountValidate::bindPhone($req->post ?? []);

            // 按情况处理
            if ($data['action'] == 'email') {
                // 未绑定邮箱
                if (empty($account['email'])) {
                    throw new Exception('很抱歉、您尚未绑定邮箱地址！');
                }
                // 验证码核实
                if (!MailCommon::check($account['email'], $data['email_code'])) {
                    throw new Exception('很抱歉、邮箱验证码不正确！');
                }
                unset($data['email_code']);
            } else {
                if (isset($data['password']) && $account['password'] !== AccountCommon::encrypt($data['password'])) {
                    throw new Exception('很抱歉、登录密码不正确！');
                }
                if (isset($data['safeword']) && $account['safeword'] !== AccountCommon::encrypt($data['safeword'])) {
                    throw new Exception('很抱歉、安全密码不正确！');
                }
            }

            // 修改资料
            $bool = AccountCommon::upd($account['uid'], [
                'phone'     =>  $data['phone'],
                'country'   =>  $data['country'],
            ]);
            if (!$bool) {
                throw new Exception('很抱歉、绑定失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 绑定邮箱地址
     */
    public function bindEmail($req, $res)
    {
        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
            $account = AccountCommon::get($req->uid);
            if (!empty($account['email'])) {
                throw new Exception('很抱歉、您已绑定过邮箱了！');
            }

            // 参数检查
            $data = AccountValidate::bindEmail($req->post ?? []);

            // 按情况处理
            if ($data['action'] == 'mobile') {
                // 未绑定手机
                if (empty($account['phone']) || empty($account['country'])) {
                    throw new Exception('很抱歉、您尚未绑定手机号码！');
                }
                // 验证码核实
                if (!SmsCommon::check($account['country'], $account['phone'], $data['mobile_code'])) {
                    throw new Exception('很抱歉、短信验证码不正确！');
                }
                unset($data['mobile_code']);
            } else {
                if (isset($data['password']) && $account['password'] !== AccountCommon::encrypt($data['password'])) {
                    throw new Exception('很抱歉、登录密码不正确！');
                }
                if (isset($data['safeword']) && $account['safeword'] !== AccountCommon::encrypt($data['safeword'])) {
                    throw new Exception('很抱歉、安全密码不正确！');
                }
            }

            // 修改资料
            $bool = AccountCommon::upd($account['uid'], [
                'email'     =>  $data['email'],
            ]);
            if (!$bool) {
                throw new Exception('很抱歉、绑定失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 重置密码
     */
    public function resetPwd($req, $res)
    {
        // 参数检查
        $data = AccountValidate::resetPwd($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
            $account = AccountCommon::get($req->uid);

            // 按情况处理
            if ($data['action'] == 'mobile') {
                // 未绑定手机
                if (empty($account['phone']) || empty($account['country'])) {
                    throw new Exception('很抱歉、您尚未绑定手机号码！');
                }
                // 验证码核实
                if (!SmsCommon::check($account['country'], $account['phone'], $data['verify_code'])) {
                    throw new Exception('很抱歉、短信验证码不正确！');
                }
            } else if ($data['action'] == 'email') {
                // 未绑定邮箱
                if (empty($account['email'])) {
                    throw new Exception('很抱歉、您尚未绑定邮箱地址！');
                }
                // 验证码核实
                if (!MailCommon::check($account['email'], $data['verify_code'])) {
                    throw new Exception('很抱歉、邮箱验证码不正确！');
                }
            } else {
                if (!empty($account[$data['type']]) && $account[$data['type']] !== AccountCommon::encrypt($data['oldword'])) {
                    throw new Exception('很抱歉、旧的密码不正确！');
                }
            }

            // 修改资料
            $bool = AccountCommon::upd($account['uid'], [
                $data['type']   =>  $data['newword']
            ]);
            if (!$bool) {
                throw new Exception('很抱歉、修改失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }

    /**
     * 忘记密码
     */
    public function forgot($req, $res)
    {
        // 参数检查
        $data = AccountValidate::forgot($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
            if (isset($data['country']) && isset($data['phone'])) {
                // 手机
                $account = AccountCommon::findByPhone($data['country'], $data['phone']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该手机号码不存在！');
                }
            } else if (isset($data['email'])) {
                // 邮箱
                $account = AccountCommon::findByEmail($data['email']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该邮箱地址不存在！');
                }
            }

            // 验证旧密码
            if (isset($data['oldword']) && $account[$data['type']] !== AccountCommon::encrypt($data['oldword'])) {
                throw new Exception('很抱歉、旧的密码不正确！', 0, [$account, $data]);
            }

            // 修改资料
            $bool = AccountCommon::upd($account['uid'], [
                $data['type']   =>  $data['newword']
            ]);
            if (!$bool) {
                throw new Exception('很抱歉、修改失败请重试！');
            }

            // 提交事务
            Db::commit();

            // 返回结果
            return [];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }
}