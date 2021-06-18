<?php
declare(strict_types=1);

namespace App\Http\Address;

use Throwable;
use App\Common\Region;
use App\Common\Account;
use App\Common\Address;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 编辑收货地址
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

        $validate->int('id', '收货地址编号')->require()->digit()->call(function($value) use($uid){
            return Address::has((int) $value, $uid);
        }, message: '很抱歉、收货地址编号不存在！');
        $validate->int('is_default', '是否设为默认地址')->in(0, 1)->default(0);

        $validate->string('country', '国家')->require()->length(1, 24)->digit()->call(function($value){
            return Region::has(country: $value);
        });
        $validate->string('province', '省份')->require()->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $value);
        })->requireWith('country');
        $validate->string('city', '市')->require()->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $value);
        })->requireWith('country', 'province');
        $validate->string('county', '区县')->require()->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $value);
        })->requireWith('country', 'province', 'city');
        $validate->string('town', '乡镇')->require()->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $values['county'] ?? '', town: $value);
        })->requireWith('country', 'province', 'city', 'county');

        $validate->string('address', '详细地址')->require();

        $validate->string('name', '联系人姓名')->require()->length(2, 30)->chsAlpha();
        $validate->string('phone', '电话号码')->require()->length(5, 30)->digit()->requireWith('country');

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
                Address::cancelCurrentDefault($uid);
            }

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