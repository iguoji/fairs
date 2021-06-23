<?php
declare(strict_types=1);

namespace App\Http\Account\Bank;

use App\Common\Bank;
use App\Common\Admin;
use App\Common\Account;
use App\Common\AccountBank;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 银行卡列表
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

        $validate->string('uid', '账户编号');
        $validate->string('keyword', '账号关键字');
        $validate->int('bank', '具体银行');
        $validate->int('is_default', '是否为默认');
        $validate->string('name', '姓名');
        $validate->string('card', '卡号');
        $validate->string('created_start_at', '添加起始时间')->date();
        $validate->string('created_end_at', '添加截止时间')->date();

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
        // 获取身份
        $identity = $req->session->identity();
        // 判断身份
        if ($identity == 'admin') {
            // 管理员查看
            return $this->admin($req, $res);
        } else {
            // 用户自己的银行卡
            return $this->account($req, $res);
        }
    }

    /**
     * 管理员查看列表
     */
    public function admin($req, $res) : mixed
    {
        // 授权验证
        $admin = Admin::verify($req);

        // 数据列表
        $list = [];
        // 数据总数
        $total = 0;
        // 用户参数
        $params = [];
        // 银行卡列表
        list($banks, $bankCount) = Bank::all();
        array_unshift($banks, ['name' => '全部', 'id' => '']);
        $banks = array_column($banks, 'name', 'id');
        // 是否为默认
        $defaults = [
            ''      =>  '全部',
            '1'     =>  '是',
            '0'     =>  '不是',
        ];

        // 验证参数
        $params = $this->validate($req->all());
        // 数据列表
        list($list, $total) = AccountBank::all($params);

        // 返回结果
        return $res->html('admin/account/bank/index.html', [
            'params'    =>  $params,
            'list'      =>  $list,
            'total'     =>  $total,
            'banks'     =>  $banks,
            'defaults'     =>  $defaults,
        ]);
    }

    /**
     * 用户自己的银行卡
     */
    public function account($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 返回数据
        return AccountBank::my($uid);
    }
}