<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Account;

/**
 * 账户资料
 */
class Profile
{
    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 查询账号
        $account = Account::get($uid);

        // 返回数据
        return Account::profile($account);
    }
}