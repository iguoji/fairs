<?php
declare(strict_types=1);

namespace App\Open;

use Throwable;
use Minimal\Facades\Db;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;
use App\Common\Account as AccountCommon;
use App\Validate\Account as AccountValidate;

/**
 * 账户类
 */
class Account
{
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
            if (isset($data['zone']) && isset($data['phone'])) {
                // 手机
                $account = AccountCommon::findByPhone($data['zone'], $data['phone']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该手机号码不存在！');
                }
            } else if (isset($data['email'])) {
                // 邮箱
                $account = AccountCommon::findByEmail($data['email']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该邮箱地址不存在！');
                }
            } else {
                // 账号
                $account = AccountCommon::findByUsername($data['username']);
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
            if (isset($data['zone']) && isset($data['phone']) && !isset($data['username'])) {
                // 手机注册
                $data['username'] = $data['zone'] . '_' . $data['phone'];
            } else if (isset($data['email']) && !isset($data['username'])) {
                // 邮箱注册
                $data['username'] = str_replace(['@', '.'], ['_', '_'], $data['email']);
            }

            // 注册账号
            $uid = AccountCommon::signup($data);

            // 提交事务
            Db::commit();

            // 返回结果
            return [
                $uid
            ];
        } catch (Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }
    }
}