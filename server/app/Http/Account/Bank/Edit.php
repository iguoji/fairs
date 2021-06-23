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
 * 编辑银行卡
 */
class Edit
{
    /**
     * 参数验证
     */
    public function validate(string $uid, array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        $validate->int('id', '银行卡编号')->require()->digit()->call(function($value) use($uid){
            return AccountBank::has((int) $value, $uid);
        }, message: '很抱歉、银行卡编号不存在！');
        $validate->int('is_default', '是否设为默认银行卡')->in(0, 1)->default(0);

        $validate->string('name', '姓名')->length(2, 30)->chsAlpha();
        $validate->int('card', '卡号')->length(5, 50)->digit();
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
        $data = $this->validate($uid, $req->all());

        try {
            // 开启事务
            Db::beginTransaction();

            // 取消现有默认
            if (!empty($data['is_default'])) {
                AccountBank::cancelCurrentDefault($uid);
            }
            // 执行修改
            $id = $data['id'];
            unset($data['id']);
            if (!AccountBank::upd($id, $data)) {
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