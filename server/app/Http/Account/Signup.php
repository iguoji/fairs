<?php
declare(strict_types=1);

namespace App\Http\Account;

use Throwable;
use App\Common\Sms;
use App\Common\Mail;
use App\Common\Wallet;
use App\Common\Account;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 账户注册
 */
class Signup
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 注册方式
        $action = $validate->string('action', '注册方式')->default('username')
            ->in('username', 'mobile', 'email')
            ->unset()->value();

        // 按情况验证参数字段
        switch ($action) {
            // 手机 + 短信验证码
            case 'mobile':
                $validate->int('country', '国家区号')->require()->length(1, 24)->digit();
                $validate->int('phone', '手机号码')
                    ->require()->length(5, 30)->digit()
                    ->call(function($value, $values){
                        return empty(Account::getByPhone($values['country'] ?? '', $value));
                    }, message: '很抱歉、该手机号码已被注册！');

                $length = Config::get('sms.length', 4);
                $validate->string('verify_code', '短信验证码')
                    ->require()->digit()->length($length, $length)
                    ->call(function($value, $values){
                        return Sms::check($values['country'] ?? '', $values['phone'] ?? '', $value);
                    })
                    ->unset();
                break;
            // 邮箱 + 邮箱验证码
            case 'email':
                $validate->string('email', '邮箱地址')
                    ->require()->length(6, 64)->email()
                    ->call(function($value){
                        return empty(Account::getByEmail($value));
                    }, message: '很抱歉、邮箱地址已被注册！');

                $length = Config::get('mail.length', 4);
                $validate->string('verify_code', '短信验证码')
                    ->require()->digit()->length($length, $length)
                    ->call(function($value, $values){
                        return Mail::check($values['email'] ?? '', $value);
                    })
                    ->unset();
                break;
            // 账号 + 密码
            default:
                $validate->string('username', '账号')
                    ->require()->length(6, 32)->alphaNum()
                    ->call(function($value){
                        return 1 === preg_match('/^[A-Za-z]{1}$/', $value[0]);
                    }, message: '很抱歉、账号的第一位必须是字母！')
                    ->call(function($value){
                        return empty(Account::getByUsername($value));
                    }, message: '很抱歉、该账号已被注册！');
                break;
        }

        // 通用验证
        $validate->string('password', '密码')->require()->length(6, 32);
        $validate->string('inviter', '邀请码')
            ->require(Config::get('app.account.inviter.enable', true))
            ->alphaNum()->length(3, 32)
            ->call(function($value){
                return !empty(Account::get($value));
            }, message: '很抱歉、指定的邀请码不存在！');

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

            // 补充账号
            if (isset($data['country']) && isset($data['phone']) && !isset($data['username'])) {
                // 手机注册
                $data['username'] = $data['country'] . '_' . $data['phone'];
            } else if (isset($data['email']) && !isset($data['username'])) {
                // 邮箱注册
                $data['username'] = str_replace(['@', '.'], ['_', '_'], $data['email']);
            }

            // 注册账号
            $uid = Account::add($data);

            // 注册钱包
            $bool = Wallet::new($uid);
            if (!$bool) {
                throw new Exception('很抱歉、钱包创建失败请重试！');
            }

            // 立即登录
            $account = Account::signin(Account::get($uid));

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