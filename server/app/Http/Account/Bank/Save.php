<?php
declare(strict_types=1);

namespace App\Http\Account\Bank;

use Throwable;
use App\Common\Bank;
use App\Common\Account;
use App\Common\AccountBank;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 新增银行卡
 */
class Save
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        $validate->int('is_default', '是否设为默认银行卡')->in(0, 1)->default(0);
        $validate->int('bank', '银行')->require()->digit()->call(function($value){
            return Bank::has((int) $value);
        }, message: '很抱歉、该银行不存在！');
        $validate->string('name', '姓名')->require()->length(2, 30)->chsAlpha();
        $validate->int('card', '卡号')->require()->length(5, 50)->digit();
        $validate->string('address', '详细地址');

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

        // 参数检查
        $data = $this->validate($req->all());

        try {
            // 开启事务
            Db::beginTransaction();

            // 银行数据
            $bank = Bank::get($data['bank']);
            // 已帮卡数量
            $myBindCount = AccountBank::singleCount($data['bank'], $uid);
            if ($bank['single_max_count'] > 0 && $myBindCount >= $bank['single_max_count']) {
                throw new Exception('很抱歉、您已达到' . $bank['name'] . '的可绑定数量上限！');
            }
            // 取消现有默认
            if (!empty($data['is_default'])) {
                AccountBank::cancelCurrentDefault($uid);
            }
            // 执行保存
            $data['uid'] = $uid;
            if (!AccountBank::add($data)) {
                throw new Exception('很抱歉、操作失败请重试！');
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