<?php
declare(strict_types=1);

namespace App\Validate;

use Minimal\Facades\Config;
use Minimal\Foundation\Validate;
use App\Common\Sms as SmsCommon;
use App\Common\Mail as MailCommon;
use App\Common\Region as RegionCommon;
use App\Common\Account as AccountCommon;
use App\Common\Authentic as AuthenticCommon;

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
        $action = $validate->string('action', '登录方式')
            ->default('username')
            ->in('username', 'mobile', 'email')
            ->unset()->value();

        // 按情况验证参数字段
        if ($action == 'mobile') {
            // 手机 + 短信验证码/密码
            self::signinByMobile($validate);
        } else if ($action == 'email') {
            // 邮箱 + 邮箱验证码/密码
            self::signinByEmail($validate);
        } else {
            // 账号 + 密码
            self::signinByUsername($validate);
        }

        // 返回结果
        return $validate->check();
    }

    /**
     * 根据用户名登录
     */
    public static function signinByUsername(Validate $validate) : void
    {
        $validate->string('username', '账号')->require()->length(6, 64)->alphaDash();
        $validate->string('password', '密码')->require()->length(6, 32);
    }

    /**
     * 根据手机验证码登录
     */
    public static function signinByMobile(Validate $validate) : void
    {
        $validate->int('country', '国家区号')->require()->length(1, 24)->digit();
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return !empty(AccountCommon::findByPhone($values['country'] ?? '', $value));
            }, message: '很抱歉、该手机号码不存在！');

        $validate->string('password', '密码')->length(6, 32)->requireWithout('verify_code');

        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->digit()->length($length, $length)
            ->requireWithout('password')
            ->call(function($value, $values){
                return SmsCommon::check($values['country'] ?? '', $values['phone'] ?? '', $value);
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

        $validate->string('password', '密码')->length(6, 32)->requireWithout('verify_code');

        $length = Config::get('mail.length', 4);
        $validate->string('verify_code', '邮箱验证码')
            ->digit()->length($length, $length)
            ->requireWithout('password')
            ->call(function($value, $values){
                return MailCommon::check($values['email'] ?? '', $value);
            })
            ->unset();
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
        $action = $validate->string('action', '注册方式')
            ->default('username')
            ->in('username', 'mobile', 'email')
            ->unset()->value();

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
        $validate->int('country', '国家区号')->require()->length(1, 24)->digit();
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return empty(AccountCommon::findByPhone($values['country'] ?? '', $value));
            }, message: '很抱歉、该手机号码已被注册！');

        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return SmsCommon::check($values['country'] ?? '', $values['phone'] ?? '', $value);
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
     * 修改资料
     */
    public static function edit(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        $validate->string('nickname', '昵称')->length(2, 20)->chsDash();
        $validate->string('avatar', '头像')->length(2, 150);
        $validate->int('gender', '性别')->in(1, 2)->digit();
        $validate->string('birthday', '出生年月')->date('Y-m-d');

        $validate->string('country', '国家')->length(1, 24)->digit()->call(function($value){
            return RegionCommon::exists(country: $value);
        })->unset();
        $validate->string('province', '省份')->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::exists(country: $values['country'] ?? '', province: $value);
        })->requireWith('country');
        $validate->string('city', '市')->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::exists(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $value);
        })->requireWith('country', 'province');
        $validate->string('county', '区县')->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::exists(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $value);
        })->requireWith('country', 'province', 'city');
        $validate->string('town', '乡镇')->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::exists(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $values['county'] ?? '', town: $value);
        })->requireWith('country', 'province', 'city', 'county');

        // 返回结果
        return $validate->check();
    }





    /**
     * 忘记密码
     */
    public static function forgot(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 找回方式
        $action = $validate->string('action', '找回方式')
            ->default('mobile')
            ->in('mobile', 'email')
            ->unset()->value();

        // 修改什么密码
        $validate->string('type', '修改类型')->require()->in('password', 'safeword')->default('password');

        // 按情况处理
        if ($action == 'mobile') {
            // 手机 + 验证码/密码
            self::forgotByMobile($validate);
        } else if ($action == 'email') {
            // 邮箱 + 验证码/密码
            self::forgotByEmail($validate);
        }

        // 获取密码
        $validate->string('newword', '新的密码')->require()->length(6, 32)->confirm('renewword');
        $validate->string('renewword', '确认密码')->require()->length(6, 32)->confirm('newword')->unset();

        // 返回结果
        return $validate->check();
    }

    /**
     * 忘记密码，根据手机验证码/密码
     */
    public static function forgotByMobile(Validate $validate) : void
    {
        $validate->int('country', '国家区号')
            ->require()->length(1, 24)->digit()
            ->call(function($value){
                return RegionCommon::exists(country: $value);
            });
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return !empty(AccountCommon::findByPhone($values['country'] ?? '', $value));
            }, message: '很抱歉、该手机号码不存在！');

        $validate->string('oldword', '旧的密码')->length(6, 32)->requireWithout('verify_code');

        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->digit()->length($length, $length)
            ->requireWithout('oldword')
            ->call(function($value, $values){
                return SmsCommon::check($values['country'] ?? '', $values['phone'] ?? '', $value);
            })
            ->unset();
    }

    /**
     * 忘记密码，根据邮箱验证码/密码
     */
    public static function forgotByEmail(Validate $validate) : void
    {
        $validate->string('email', '邮箱地址')
            ->require()->length(6, 64)->email()
            ->call(function($value){
                return !empty(AccountCommon::findByEmail($value));
            }, message: '很抱歉、邮箱地址不存在！');

        $validate->string('oldword', '旧的密码')->length(6, 32)->requireWithout('verify_code');

        $length = Config::get('mail.length', 4);
        $validate->string('verify_code', '邮箱验证码')
            ->digit()->length($length, $length)
            ->requireWithout('oldword')
            ->call(function($value, $values){
                return MailCommon::check($values['email'] ?? '', $value);
            })
            ->unset();
    }





    /**
     * 重置密码
     */
    public static function resetPwd(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 重置方式
        $action = $validate->string('action', '重置方式')
            ->default('oldword')
            ->in('oldword', 'mobile', 'email')
            ->value();

        // 修改什么密码
        $validate->string('type', '密码类型')->require()->in('password', 'safeword')->default('password');

        // 按情况处理
        if ($action == 'mobile') {
            // 手机验证码
            $length = Config::get('sms.length', 4);
            $validate->string('verify_code', '短信验证码')->require()->digit()->length($length, $length);
        } else if ($action == 'email') {
            // 邮箱验证码
            $length = Config::get('mail.length', 4);
            $validate->string('verify_code', '邮箱验证码')->require()->digit()->length($length, $length);
        } else {
            // 旧密码
            $validate->string('oldword', '旧的密码')->require()->length(6, 32);
        }

        // 获取密码
        $validate->string('newword', '新的密码')->require()->length(6, 32)->confirm('renewword');
        $validate->string('renewword', '确认密码')->require()->length(6, 32)->confirm('newword')->unset();

        // 返回结果
        return $validate->check();
    }




    /**
     * 绑定安全密码
     */
    public static function safeword(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 绑定方式
        $action = $validate->string('action', '绑定方式')
            ->default('password')
            ->in('password', 'mobile', 'email')
            ->value();

        // 按情况处理
        if ($action == 'mobile') {
            // 手机验证码
            $length = Config::get('sms.length', 4);
            $validate->string('verify_code', '短信验证码')->require()->digit()->length($length, $length);
        } else if ($action == 'email') {
            // 邮箱验证码
            $length = Config::get('mail.length', 4);
            $validate->string('verify_code', '邮箱验证码')->require()->digit()->length($length, $length);
        } else {
            // 登录密码
            $validate->string('password', '登录密码')->require()->length(6, 32);
        }

        // 获取密码
        $validate->string('safeword', '安全密码')->require()->length(6, 32)->confirm('resafeword');
        $validate->string('resafeword', '确认密码')->require()->length(6, 32)->confirm('safeword')->unset();

        // 返回结果
        return $validate->check();
    }




    /**
     * 绑定手机
     */
    public static function bindPhone(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 绑定方式
        $action = $validate->string('action', '绑定方式')
            ->default('password')
            ->in('password', 'safeword', 'email')
            ->value();

        // 手机号码
        $validate->int('country', '国家区号')
            ->require()->length(1, 24)->digit()
            ->call(function($value){
                return RegionCommon::exists(country: $value);
            });
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return empty(AccountCommon::findByPhone($values['country'] ?? '', $value));
            }, message: '很抱歉、该手机号码已被绑定！');

        // 手机验证码
        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return SmsCommon::check($values['country'] ?? '', $values['phone'] ?? '', $value);
            })->unset();

        // 按情况处理
        if ($action == 'email') {
            // 邮箱验证码
            $length = Config::get('mail.length', 4);
            $validate->string('email_code', '邮箱验证码')->require()->digit()->length($length, $length);
        } else {
            // 登录密码
            $validate->string('password', '登录密码')->length(6, 32)->requireWithout('safeword');
            // 安全密码
            $validate->string('safeword', '安全密码')->length(6, 32)->requireWithout('password');
        }

        // 返回结果
        return $validate->check();
    }



    /**
     * 绑定邮箱
     */
    public static function bindEmail(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 绑定方式
        $action = $validate->string('action', '绑定方式')
            ->default('password')
            ->in('password', 'safeword', 'mobile')
            ->value();

        // 邮箱地址
        $validate->string('email', '邮箱地址')
            ->require()->length(6, 64)->email()
            ->call(function($value){
                return empty(AccountCommon::findByEmail($value));
            }, message: '很抱歉、该邮箱地址已被绑定！');
        $length = Config::get('mail.length', 4);
        $validate->string('verify_code', '邮箱验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return MailCommon::check($values['email'] ?? '', $value);
            })
            ->unset();

        // 按情况处理
        if ($action == 'mobile') {
            // 手机验证码
            $length = Config::get('sms.length', 4);
            $validate->string('mobile_code', '短信验证码')->require()->digit()->length($length, $length);
        } else {
            // 登录密码
            $validate->string('password', '登录密码')->length(6, 32)->requireWithout('safeword');
            // 安全密码
            $validate->string('safeword', '安全密码')->length(6, 32)->requireWithout('password');
        }

        // 返回结果
        return $validate->check();
    }




    /**
     * 实名认证
     */
    public static function authentic(array $params) : array
    {
        $validate = new Validate($params);

        $type = Config::get('app.account.authentic', AuthenticCommon::IDCARD);
        $fields = AuthenticCommon::FIELDS[$type] ?? [];

        $validate->string('name', $fields['name'] ?? '真实姓名')->require(array_key_exists('name', $fields))->length(2, 30)->chsAlpha();
        $validate->string('code', $fields['code'] ?? '身份证号码')->require(array_key_exists('code', $fields))->length(6, 30)->digit();

        $validate->string('country', $fields['country'] ?? '国家')->require(array_key_exists('country', $fields))->length(1, 24)->digit()->call(function($value){
            return RegionCommon::exists(country: $value);
        });
        $validate->string('province', $fields['province'] ?? '省份')->require(array_key_exists('province', $fields))->length(1, 30)->digit()->call(function($value, $values){
            return RegionCommon::exists(country: $values['country'] ?? '', province: $value);
        })->requireWith('country');

        $validate->int('phone', $fields['phone'] ?? '手机号码')->require(array_key_exists('phone', $fields))->length(5, 30)->digit()->requireWith('country');

        $validate->string('front', $fields['front'] ?? '正面照片')->require(array_key_exists('front', $fields))->length(2, 150);
        $validate->string('back', $fields['back'] ?? '反面照片')->require(array_key_exists('back', $fields))->length(2, 150);
        $validate->string('face', $fields['face'] ?? '人脸照片')->require(array_key_exists('face', $fields))->length(2, 150);
        $validate->string('hold', $fields['hold'] ?? '手持照片')->require(array_key_exists('hold', $fields))->length(2, 150);
        $validate->string('video', $fields['video'] ?? '视频')->require(array_key_exists('video', $fields))->length(2, 150);

        $data = $validate->check();

        return $data;
    }
}