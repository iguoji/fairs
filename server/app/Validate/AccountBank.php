<?php
declare(strict_types=1);

namespace App\Validate;

use Minimal\Foundation\Validate;
use App\Common\Bank as BankCommon;
use App\Common\AccountBank as AccountBankCommon;

class AccountBank
{
    /**
     * 添加银行卡
     */
    public static function save(array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('is_default', '是否设为默认银行卡')->in(0, 1)->default(0);
        $validate->int('bank', '银行')->require()->digit()->call(function($value){
            return BankCommon::exists((int) $value);
        }, message: '很抱歉、该银行不存在！');
        $validate->string('name', '姓名')->require()->length(2, 30)->chsAlpha();
        $validate->int('card', '卡号')->require()->length(5, 50)->digit();
        $validate->string('address', '详细地址');

        return $validate->check();
    }

    /**
     * 编辑银行卡
     */
    public static function edit(string $uid, array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('id', '银行卡编号')->require()->digit()->call(function($value) use($uid){
            return AccountBankCommon::exists((int) $value, $uid);
        }, message: '很抱歉、银行卡编号不存在！');
        $validate->int('is_default', '是否设为默认银行卡')->in(0, 1)->default(0);

        $validate->string('name', '姓名')->length(2, 30)->chsAlpha();
        $validate->int('card', '卡号')->length(5, 50)->digit();
        $validate->string('address', '详细地址');

        return $validate->check();
    }

    /**
     * 删除银行卡
     */
    public static function remove(string $uid, array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('id', '银行卡编号')->require()->digit()->call(function($value) use($uid){
            return AccountBankCommon::exists((int) $value, $uid);
        }, message: '很抱歉、银行卡编号不存在！');

        return $validate->check();
    }

    /**
     * 设置为默认银行卡
     */
    public static function default(string $uid, array $params) : array
    {
        $validate = new Validate($params);

        $validate->int('id', '银行卡编号')->require()->digit()->call(function($value) use($uid){
            return AccountBankCommon::exists((int) $value, $uid);
        }, message: '很抱歉、银行卡编号不存在！');

        $validate->int('is_default', '是否设为默认银行卡')->in(0, 1)->default(1);

        return $validate->check();
    }
}