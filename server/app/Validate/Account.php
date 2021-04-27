<?php
declare(strict_types=1);

namespace App\Validate;

use Minimal\Facades\Config;
use Minimal\Foundation\Validate;
use App\Common\Sms as SmsCommon;
use App\Common\Mail as MailCommon;
use App\Common\Account as AccountCommon;

/**
 * 账户验证类
 */
class Account
{
    /**
     * 登录
     * 1. 账号 + 密码
     * 2. 手机 + 密码
     * 3. 邮箱 + 密码
     * 4. 手机 + 短信验证码
     * 5. 邮箱 + 邮箱验证码
     */
    public static function signin(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 登录方式
        $action = $validate->string('action')->default('default')->unset()->value();

        // 按情况验证参数字段
        if ($action == 'mobile') {
            // 手机 + 短信验证码
            self::signinByMobile($validate);
        } else if ($action == 'email') {
            // 邮箱 + 邮箱验证码
            self::signinByEmail($validate);
        } else {
            // 账号 + 密码
            // 手机 + 密码
            // 邮箱 + 密码
            self::signinByUsername($validate);
        }

        // 返回结果
        return $validate->check();
    }

    /**
     * 注册
     * 1. 账号 + 密码
     * 2. 手机 + 短信验证码
     * 3. 邮箱 + 邮箱验证码
     */
    public static function signup(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 注册方式
        $action = $validate->string('action')->default('default')->unset()->value();

        // 按情况验证参数字段
        if ($action == 'mobile') {
            // 手机 + 短信验证码
            self::signupByMobile($validate);
        } else if ($action == 'email') {
            // 邮箱 + 邮箱验证码
            self::signupByEmail($validate);
        } else {
            // 账号 + 密码
            self::signupByUsername($validate);
        }

        // 通用验证
        $validate->string('password', '密码')->require()->length(6, 32);
        $validate->string('inviter', '邀请码')
            ->require(Config::get('app.account.inviter.enable', true))
            ->alphaNum()->length(3, 32)
            ->call(function($value){
                return !empty(AccountCommon::findByUid($value));
            }, message: '很抱歉、指定的邀请码不存在！');

        // 返回结果
        return $validate->check();
    }

    /**
     * 根据用户名注册
     */
    public static function signupByUsername(Validate $validate) : void
    {
        $validate->string('username', '账号')
            ->require()->length(6, 32)->alphaNum()
            ->call(function($value){
                return 1 === preg_match('/^[A-Za-z]{1}$/', $value[0]);
            }, message: '很抱歉、账号的第一位必须是字母！')
            ->call(function($value){
                return empty(AccountCommon::findByUsername($value));
            }, message: '很抱歉、该账号已被注册！');
    }

    /**
     * 根据手机号码注册
     */
    public static function signupByMobile(Validate $validate) : void
    {
        $validate->int('zone', '国家区号')->require()->length(1, 24)->digit();
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return empty(AccountCommon::findByPhone($values['zone'] ?? '', $value));
            }, message: '很抱歉、该手机号码已被注册！');

        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return SmsCommon::check($values['zone'] ?? '', $values['phone'] ?? '', $value);
            })
            ->unset();
    }

    /**
     * 根据邮箱地址注册
     */
    public static function signupByEmail(Validate $validate) : void
    {
        $validate->string('email', '邮箱地址')
            ->require()->length(6, 64)->email()
            ->call(function($value){
                return empty(AccountCommon::findByEmail($value));
            }, message: '很抱歉、邮箱地址已被注册！');

        $length = Config::get('mail.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return MailCommon::check($values['email'] ?? '', $value);
            })
            ->unset();
    }

    /**
     * 根据用户名登录
     */
    public static function signinByUsername(Validate $validate) : void
    {
        // 根据用户名来判断情况
        $username = $validate->string('username', '账号')->require()->value();
        if (ctype_digit($username)) {
            // 手机登录
            $validate->int('zone', '国家区号')->default('86')->length(1, 24)->digit();
            $validate->int('phone', '手机号码')->default($username)->length(5, 30)->digit();
        } else if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            // 邮箱登录
            $validate->string('email', '邮箱地址')->default($username)->length(6, 64)->email();
        }

        // 密码
        $validate->string('password', '密码')->require()->length(6, 32);
    }

    /**
     * 根据手机验证码登录
     */
    public static function signinByMobile(Validate $validate) : void
    {
        $validate->int('zone', '国家区号')->require()->length(1, 24)->digit();
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return !empty(AccountCommon::findByPhone($values['zone'] ?? '', $value));
            }, message: '很抱歉、该手机号码不存在！');

        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return SmsCommon::check($values['zone'] ?? '', $values['phone'] ?? '', $value);
            })
            ->unset();
    }

    /**
     * 根据邮箱验证码登录
     */
    public static function signinByEmail(Validate $validate) : void
    {
        $validate->string('email', '邮箱地址')
            ->require()->length(6, 64)->email()
            ->call(function($value){
                return !empty(AccountCommon::findByEmail($value));
            }, message: '很抱歉、邮箱地址不存在！');

        $length = Config::get('mail.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return MailCommon::check($values['email'] ?? '', $value);
            })
            ->unset();
    }
}