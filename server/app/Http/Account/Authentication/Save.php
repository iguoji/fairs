<?php
declare(strict_types=1);

namespace App\Http\Account\Authentication;

use Throwable;
use App\Common\Region;
use App\Common\Account;
use App\Common\Authentication;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Facades\Config;
use Minimal\Foundation\Exception;

/**
 * 用户申请实名认证
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

        $type = Config::get('app.account.authentication', Authentication::IDCARD);
        $fields = Authentication::FIELDS[$type] ?? [];

        $validate->string('name', $fields['name'] ?? '真实姓名')->require(array_key_exists('name', $fields))->length(2, 30);
        $validate->string('idcard', $fields['idcard'] ?? '身份证号码')->require(array_key_exists('idcard', $fields))->length(6, 30);
        $validate->string('bankcard', $fields['bankcard'] ?? '银行卡号')->require(array_key_exists('bankcard', $fields))->length(6, 30);

        $validate->string('country', $fields['country'] ?? '国家')->require(array_key_exists('country', $fields))->default('86')->length(1, 24)->digit()->call(function($value){
            return Region::has($value);
        });
        $validate->string('province', $fields['province'] ?? '省份')->require(array_key_exists('province', $fields))->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 2);
        })->requireWith('country');

        $validate->string('phone', $fields['phone'] ?? '手机号码')->require(array_key_exists('phone', $fields))->length(5, 30)->digit()->requireWith('country');

        $validate->string('front', $fields['front'] ?? '正面照片')->require(array_key_exists('front', $fields))->length(2, 150);
        $validate->string('back', $fields['back'] ?? '反面照片')->require(array_key_exists('back', $fields))->length(2, 150);
        $validate->string('face', $fields['face'] ?? '人脸照片')->require(array_key_exists('face', $fields))->length(2, 150);
        $validate->string('hold', $fields['hold'] ?? '手持照片')->require(array_key_exists('hold', $fields))->length(2, 150);
        $validate->string('video', $fields['video'] ?? '视频')->require(array_key_exists('video', $fields))->length(2, 150);

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

        try {
            // 开启事务
            Db::beginTransaction();

            // 获取类型
            $type = Config::get('app.account.authentication', Authentication::IDCARD);

            // 认证状态
            $status = Authentication::status($uid, $type);
            if ($status === Authentication::WAIT) {
                throw new Exception('很抱歉、您的资料已在审核中请勿重复提交！');
            } else if ($status == Authentication::SUCCESS) {
                throw new Exception('很抱歉、您已认证通过请勿重复提交！');
            }

            // 参数检查
            $data = $this->validate($req->all());

            // 提交认证
            $data['uid'] = $uid;
            if (!Authentication::add($data, $type)) {
                throw new Exception('很抱歉、认证资料保存失败请重试！');
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