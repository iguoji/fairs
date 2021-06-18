<?php
declare(strict_types=1);

namespace App\Http\Account\Relation;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
use App\Common\Authentication;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 推广关系列表
 */
class Index
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->string('keyword', '账号关键字');
        $validate->string('type', '认证类型');
        $validate->int('status', '状态');
        $validate->string('name', '真实姓名');
        $validate->string('idcard', '证件号码');
        $validate->string('bankcard', '银行卡号');
        $validate->string('phone', '手机号码');
        $validate->string('created_start_at', '申请起始时间')->date();
        $validate->string('created_end_at', '申请截止时间')->date();
        $validate->int('pageNo', '当前页码')->default(0);
        $validate->int('pageSize', '每页数量')->default(20);

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 异常错误
        $exception = [];
        // 用户参数
        $params = [];
        // 认证列表
        $authentications = [];
        // 认证类型
        $types = ['' => '全部'] + Authentication::TYPES;
        // 认证状态
        $statuses = [
            ''      =>  '全部',
            '1'     =>  '通过',
            '0'     =>  '拒绝',
            '2'     =>  '待审核',
        ];
        // 数据总数
        $total = 0;

        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 验证参数
            $params = $this->validate($req->all());

            // 认证列表
            list($authentications, $total) = Account::all($params);
        } catch (\Throwable $th) {
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        return $res->html('admin/account/relation/index', [
            'params'            =>  $params,
            'authentications'   =>  $authentications,
            'types'             =>  $types,
            'statuses'          =>  $statuses,
            'total'             =>  $total,
            'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
        ]);
    }
}