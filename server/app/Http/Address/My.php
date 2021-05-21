<?php
declare(strict_types=1);

namespace App\Http\Address;

use App\Common\Account;
use App\Common\Address;

/**
 * 我的收货地址
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
        return Address::my($uid);
    }
}