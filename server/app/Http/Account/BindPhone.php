<?php
declare(strict_types=1);

namespace App\Http\Account;

use Throwable;
use App\Common\Sms;
use App\Common\Mail;
use App\Common\Region;
use App\Common\Account;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 绑定手机号码
 */
class BindPhone
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
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
                return Region::has(country: $value);
            });
        $validate->int('phone', '手机号码')
            ->require()->length(5, 30)->digit()
            ->call(function($value, $values){
                return empty(Account::getByPhone($values['country'] ?? '', $value));
            }, message: '很抱歉、该手机号码已被绑定！');

        // 手机验证码
        $length = Config::get('sms.length', 4);
        $validate->string('verify_code', '短信验证码')
            ->require()->digit()->length($length, $length)
            ->call(function($value, $values){
                return Sms::check($values['country'] ?? '', $values['phone'] ?? '', $value);
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
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
            $account = Account::get($uid);
            if (!empty($account['phone'])) {
                throw new Exception('很抱歉、您已绑定过手机号码了！');
            }

            // 参数检查
            $data = $this->validate($req->all());

            // 按情况处理
            if ($data['action'] == 'email') {
                // 未绑定邮箱
                if (empty($account['email'])) {
                    throw new Exception('很抱歉、您尚未绑定邮箱地址！');
                }
                // 验证码核实
                if (!Mail::check($account['email'], $data['email_code'])) {
                    throw new Exception('很抱歉、邮箱验证码不正确！');
                }
                unset($data['email_code']);
            } else {
                if (isset($data['password']) && $account['password'] !== Account::encrypt($data['password'])) {
                    throw new Exception('很抱歉、登录密码不正确！');
                }
                if (isset($data['safeword']) && $account['safeword'] !== Account::encrypt($data['safeword'])) {
                    throw new Exception('很抱歉、安全密码不正确！');
                }
            }

            // 修改资料
            $bool = Account::upd($account['uid'], [
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
}