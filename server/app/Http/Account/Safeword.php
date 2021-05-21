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
 * 安全密码
 */
class Safeword
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
            if (!empty($account['safeword'])) {
                throw new Exception('很抱歉、您已绑定过安全密码了！');
            }

            // 参数检查
            $data = $this->validate($req->all());

            // 按情况处理
            if ($data['action'] == 'mobile') {
                // 未绑定手机
                if (empty($account['phone']) || empty($account['country'])) {
                    throw new Exception('很抱歉、您尚未绑定手机号码！');
                }
                // 验证码核实
                if (!Sms::check($account['country'], $account['phone'], $data['verify_code'])) {
                    throw new Exception('很抱歉、短信验证码不正确！');
                }
            } else if ($data['action'] == 'email') {
                // 未绑定邮箱
                if (empty($account['email'])) {
                    throw new Exception('很抱歉、您尚未绑定邮箱地址！');
                }
                // 验证码核实
                if (!Mail::check($account['email'], $data['verify_code'])) {
                    throw new Exception('很抱歉、邮箱验证码不正确！');
                }
            } else {
                if ($account['password'] !== Account::encrypt($data['password'])) {
                    throw new Exception('很抱歉、登录密码不正确！');
                }
            }

            // 修改资料
            $bool = Account::upd($account['uid'], [
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
}