<?php
declare(strict_types=1);

namespace App\Http\AccountBank;

use App\Common\Account;
use App\Common\AccountBank;

/**
 * 我的银行卡
 */
class My
{
    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 返回数据
        return AccountBank::my($uid);
    }
}