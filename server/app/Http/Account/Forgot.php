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
 * 忘记密码
 */
class Forgot
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
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
            $validate->string('country', '国家区号')
                ->require()->length(1, 24)->digit()
                ->call(function($value){
                    return Region::has(country: $value);
                });
            $validate->int('phone', '手机号码')
                ->require()->length(5, 30)->digit()
                ->call(function($value, $values){
                    return !empty(Account::getByPhone($values['country'] ?? '', $value));
                }, message: '很抱歉、该手机号码不存在！');

            $validate->string('oldword', '旧的密码')->length(6, 32)->requireWithout('verify_code');

            $length = Config::get('sms.length', 4);
            $validate->string('verify_code', '短信验证码')
                ->digit()->length($length, $length)
                ->requireWithout('oldword')
                ->call(function($value, $values){
                    return Sms::check($values['country'] ?? '', $values['phone'] ?? '', $value);
                })
                ->unset();
        } else if ($action == 'email') {
            // 邮箱 + 验证码/密码
            $validate->string('email', '邮箱地址')
                ->require()->length(6, 64)->email()
                ->call(function($value){
                    return !empty(Account::get($value, 'email'));
                }, message: '很抱歉、邮箱地址不存在！');

            $validate->string('oldword', '旧的密码')->length(6, 32)->requireWithout('verify_code');

            $length = Config::get('mail.length', 4);
            $validate->string('verify_code', '邮箱验证码')
                ->digit()->length($length, $length)
                ->requireWithout('oldword')
                ->call(function($value, $values){
                    return Mail::check($values['email'] ?? '', $value);
                })
                ->unset();
        }

        // 获取密码
        $validate->string('newword', '新的密码')->require()->length(6, 32)->confirm('renewword');
        $validate->string('renewword', '确认密码')->require()->length(6, 32)->confirm('newword')->unset();

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 参数检查
        $data = $this->validate($req->all());

        try {
            // 开启事务
            Db::beginTransaction();

            // 查询账号
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
            }

            // 验证旧密码
            if (isset($data['oldword']) && $account[$data['type']] !== Account::encrypt($data['oldword'])) {
                throw new Exception('很抱歉、旧的密码不正确！', 0, [$account, $data]);
            }

            // 修改资料
            $bool = Account::upd($account['uid'], [
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