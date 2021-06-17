<?php
declare(strict_types=1);

namespace App\Http\Account;

use Throwable;
use App\Common\Sms;
use App\Common\Mail;
use App\Common\Account;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 账户登录
 */
class Signin
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 登录方式
        $action = $validate->string('action', '登录方式')
            ->default('username')
            ->in('username', 'mobile', 'email')
            ->unset()->value();

        // 按情况验证参数字段
        switch ($action) {
            // 手机 + 短信验证码/密码
            case 'mobile':
                $validate->string('country', '国家区号')->require()->length(1, 24)->digit();
                $validate->int('phone', '手机号码')
                    ->require()->length(5, 30)->digit()
                    ->call(function($value, $values){
                        return !empty(Account::getByPhone($values['country'] ?? '', $value));
                    }, message: '很抱歉、该手机号码不存在！');

                $validate->string('password', '密码')->length(6, 32)->requireWithout('verify_code');

                $length = Config::get('sms.length', 4);
                $validate->string('verify_code', '短信验证码')
                    ->digit()->length($length, $length)
                    ->requireWithout('password')
                    ->call(function($value, $values){
                        return Sms::check($values['country'] ?? '', $values['phone'] ?? '', $value);
                    })
                    ->unset();
                break;
            // 邮箱 + 邮箱验证码/密码
            case 'email':
                $validate->string('email', '邮箱地址')
                    ->require()->length(6, 64)->email()
                    ->call(function($value){
                        return !empty(Account::get($value, 'email'));
                    }, message: '很抱歉、邮箱地址不存在！');

                $validate->string('password', '密码')->length(6, 32)->requireWithout('verify_code');

                $length = Config::get('mail.length', 4);
                $validate->string('verify_code', '邮箱验证码')
                    ->digit()->length($length, $length)
                    ->requireWithout('password')
                    ->call(function($value, $values){
                        return Mail::check($values['email'] ?? '', $value);
                    })
                    ->unset();
                break;
            // 账号 + 密码
            default:
                $validate->string('username', '账号')->require()->length(6, 64)->alphaDash();
                $validate->string('password', '密码')->require()->length(6, 32);
                break;
        }

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 参数检查
        $data = self::validate($req->all());

        try {
            // 开启事务
            Db::beginTransaction();

            // 按情况登录
            if (isset($data['country']) && isset($data['phone'])) {
                // 手机
                $account = Account::getByPhone($data['country'], $data['phone']);
                if (empty($account)) {
                    throw new Exception('很抱歉、该手机号码不存在！');
                }
            } else if (isset($data['email'])) {
                // 邮箱
                $account = Account::get($data['email'], 'email');
                if (empty($account)) {
                    throw new Exception('很抱歉、该邮箱地址不存在！');
                }
            } else {
                // 账号
                $account = Account::get($data['username'], 'username');
                if (empty($account)) {
                    throw new Exception('很抱歉、该账号不存在！');
                }
            }

            // 验证密码
            if (isset($data['password']) && $account['password'] !== Account::encrypt($data['password'])) {
                throw new Exception('很抱歉、登录密码不正确！');
            }

            // 执行登录
            $account = Account::signin($req, $account, Config::get('app.account.signin.expire'));

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
}