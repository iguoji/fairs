<?php
declare(strict_types=1);

namespace App\Http\Address;

use Throwable;
use App\Common\Account;
use App\Common\Address;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 设置为默认收货地址
 */
class UseDefault
{
    /**
     * 参数验证
     */
    public function validate(string $uid, array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        $validate->int('id', '收货地址编号')->require()->digit()->call(function($value) use($uid){
            return Address::has((int) $value, $uid);
        }, message: '很抱歉、收货地址编号不存在！');

        $validate->int('is_default', '是否设为默认地址')->in(0, 1)->default(1);

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
            Address::cancelCurrentDefault($uid);

            // 执行修改
            $id = $data['id'];
            unset($data['id']);
            if (!Address::upd($id, $data)) {
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