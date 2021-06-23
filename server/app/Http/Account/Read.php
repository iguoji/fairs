<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
use App\Common\AccountBank;
use Minimal\Http\Validate;

/**
 * 查看/读取账户资料
 */
class Read
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 用户编号
        $validate->string('uid', '用户编号')->require()->alphaNum();

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
            // 管理员查看/读取
            return $this->admin($req, $res);
        } else {
            // 用户自行读取
            return $this->account($req, $res);
        }
    }

    /**
     * 管理员
     */
    public function admin($req, $res) : mixed
    {
        // 验证参数
        $params = [];
        // 国家信息
        $countrys = [];
        // 账户列表
        $account = [];

        // 授权验证
        $admin = Admin::verify($req);

        // 验证参数
        $params = $this->validate($req->all());
        // 查询账号
        $account = Account::get($params['uid']);
        // 联系地址
        $region = Region::find($account);
        $account['address'] = $region['address'] ?? '';
        // 上级信息
        $account['parent'] = [];
        if (!empty($account['inviter'])) {
            $account['parent'] = Account::get($account['inviter']);
        }
        // 绑卡数量
        $account['bank_count'] = AccountBank::count($account['uid']);

        // 返回结果
        return $res->html('admin/account/read', [
            'params'    =>  $params,
            'account'   =>  $account,
            'countrys'  =>  $countrys,
        ]);
    }

    /**
     * 用户
     */
    public function account($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 查询账号
        $account = Account::get($uid);

        // 返回数据
        return Account::profile($account);
    }
}