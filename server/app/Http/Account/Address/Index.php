<?php
declare(strict_types=1);

namespace App\Http\Account\Address;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
use App\Common\AccountAddress;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 收货地址列表
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
        $validate->string('name', '联系人姓名');
        $validate->string('phone', '联系号码');
        $validate->string('country', '国家')->digit();
        $validate->string('province', '省份')->digit();
        $validate->string('city', '城市')->digit();
        $validate->string('county', '区县')->digit();
        $validate->string('address', '详细地址');
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
            // 用户自己的收货地址
            return $this->account($req, $res);
        }
    }

    /**
     * 管理员
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
        // 是否为默认
        $defaults = [
            ''      =>  '全部',
            '1'     =>  '是',
            '0'     =>  '不是',
        ];

        // 验证参数
        $params = $this->validate($req->all());
        // 数据列表
        list($list, $total) = AccountAddress::all($params);

        // 返回结果
        return $res->html('admin/account/address/index.html', [
            'params'    =>  $params,
            'list'      =>  $list,
            'total'     =>  $total,
            'defaults'     =>  $defaults,
        ]);
    }

    /**
     * 用户
     */
    public function account($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 返回数据
        return AccountAddress::my($uid);
    }
}