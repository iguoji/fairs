<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 账户列表
 */
class Index
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->string('username', '账号');
        $validate->int('country', '国家');
        $validate->int('phone', '手机号码');
        $validate->string('email', '邮箱');
        $validate->string('inviter', '上级邀请码');
        $validate->int('status', '状态');
        $validate->string('created_start_at', '注册起始时间')->date();
        $validate->string('created_end_at', '注册截止时间')->date();

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 异常错误
        $exception = [];
        // 用户参数
        $params = [];
        // 账户列表
        $accounts = [];
        // 国家列表
        $countrys = [];
        // 账户总数
        $total = 0;
        // 每页数量
        $size = 20;

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 验证参数
            $params = $this->verify($req->all());
            // 国家信息
            $countrys = Region::countrys();
            // 账户列表
            $params['pageSize'] = $size;
            list($accounts, $total) = Account::all($params);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->html('admin/account/index', [
            'params'    =>  $params,
            'accounts'  =>  $accounts,
            'countrys'  =>  $countrys,
            'total'     =>  $total,
            'size'      =>  $size,
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}