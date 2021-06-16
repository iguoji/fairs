<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Account;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 删除账户
 */
class Remove
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 账户编号
        $validate->string('uid', '账户编号')->require()->call(function($value){
            return Account::has($value);
        });

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

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 参数验证
            $data = self::validate($req->all());
            // 执行操作
            Account::del($data['uid']);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->redirect('/account.html', [
            'exception'     =>  $exception
        ]);
    }
}