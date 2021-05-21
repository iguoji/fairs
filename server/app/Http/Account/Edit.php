<?php
declare(strict_types=1);

namespace App\Http\Account;

use Throwable;
use App\Common\Region;
use App\Common\Account;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 资料编辑
 */
class Edit
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        $validate->string('nickname', '昵称')->length(2, 20)->chsDash();
        $validate->string('avatar', '头像')->length(2, 150);
        $validate->int('gender', '性别')->in(1, 2)->digit();
        $validate->string('birthday', '出生年月')->date('Y-m-d');

        $validate->string('country', '国家')->length(1, 24)->digit()->call(function($value){
            return Region::has(country: $value);
        })->unset();
        $validate->string('province', '省份')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $value);
        })->requireWith('country');
        $validate->string('city', '市')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $value);
        })->requireWith('country', 'province');
        $validate->string('county', '区县')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $value);
        })->requireWith('country', 'province', 'city');
        $validate->string('town', '乡镇')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has(country: $values['country'] ?? '', province: $values['province'] ?? '', city: $values['city'] ?? '', county: $values['county'] ?? '', town: $value);
        })->requireWith('country', 'province', 'city', 'county');

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

            // 没有任何修改
            if (empty($data)) {
                throw new Exception('很抱歉、没有任何修改！');
            }

            // 修改资料
            $bool = Account::upd($uid, $data);
            if (!$bool) {
                throw new Exception('很抱歉、修改失败请重试！');
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