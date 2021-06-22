<?php
declare(strict_types=1);

namespace App\Http\Account\Promotion;

use App\Common\Admin;
use App\Common\Account;
use App\Common\AccountPromotion;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 读取推广细节
 */
class Read
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->string('keyword', '账号关键字');
        $validate->string('inviter', '上级编号');
        $validate->int('pageNo', '当前页码')->default(0);
        $validate->int('pageSize', '每页数量')->default(20);

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 用户参数
        $params = [];
        // 账户列表
        $accounts = [];

        // 数据总数
        $total = 0;

        // 权限验证
        $admin = Admin::verify($req);

        // 验证参数
        $params = $this->validate($req->all());
        // 账户列表
        list($accounts, $total) = AccountPromotion::all($params);

        // 返回结果
        return $res->json([
            'params'        =>  $params,
            'list'          =>  $accounts,
            'total'         =>  $total,
        ]);
    }
}