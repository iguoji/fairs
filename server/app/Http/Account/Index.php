<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 账户列表
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
        $validate->int('type', '类型');
        $validate->int('status', '状态');
        $validate->int('level', '等级');
        $validate->string('uid', '编号');
        $validate->string('keyword', '账号关键字');
        $validate->string('username', '用户名');
        $validate->string('phone', '手机号码');
        $validate->string('email', '邮箱地址');

        $validate->string('nickname', '昵称');
        $validate->string('gender', '性别')->in('0', '1', '2');
        $validate->string('birthday', '出生年月')->date('Y-m-d');

        $validate->string('country', '国家')->digit();
        $validate->string('province', '省份')->digit();
        $validate->string('city', '城市')->digit();
        $validate->string('county', '区县')->digit();

        $validate->string('inviter', '上级邀请码');
        $validate->string('created_start_at', '注册起始时间')->date();
        $validate->string('created_end_at', '注册截止时间')->date();

        $validate->int('pageNo', '当前页码')->default(1);
        $validate->int('pageSize', '每页数量')->default(20);

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 用户参数
        $params = [];
        // 账户列表
        $accounts = [];
        // 国家列表
        $countrys = [];
        // 会员级别
        $levels = [
            ''      =>  '全部',
        ];
        // 账户状态
        $statuses = [
            ''      =>  '全部',
            '1'     =>  '正常',
            '0'     =>  '冻结',
        ];
        // 实名认证
        $authentications = [
            ''      =>  '全部',
            '1'     =>  '是',
            '0'     =>  '否',
        ];
        // 是否绑卡
        $isBindCards = [
            ''      =>  '全部',
            '1'     =>  '是',
            '0'     =>  '否',
        ];
        // 性别
        $genders = [
            ''      =>  '全部',
            '0'     =>  '未知',
            '1'     =>  '男',
            '2'     =>  '女',
        ];
        // 账户总数
        $total = 0;

        // 权限验证
        $admin = Admin::verify($req);

        // 验证参数
        $params = $this->validate($req->all());
        // 账户列表
        list($accounts, $total) = Account::all($params);

        // 返回结果
        return $res->html('admin/account/index', [
            'params'            =>  $params,
            'list'              =>  $accounts,
            'total'             =>  $total,
            'levels'            =>  $levels,
            'statuses'          =>  $statuses,
            'authentications'   =>  $authentications,
            'isBindCards'       =>  $isBindCards,
            'genders'           =>  $genders,
        ]);
    }
}