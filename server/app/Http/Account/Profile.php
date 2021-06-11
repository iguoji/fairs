<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Account;
use Minimal\Http\Validate;

/**
 * 账户资料
 */
class Profile
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
            // 管理员读取
            return $this->read($req, $res);
        } else {
            // 用户自行读取
            return $this->profile($req, $res);
        }
    }

    /**
     * 读取资料
     */
    public function read($req, $res) : mixed
    {
        // 授权验证
        $admin = Admin::verify($req);

        // 参数验证
        $params = $this->validate($req->all());

        // 查询账号
        $account = Account::get($params['uid']);

        // 返回结果
        return $account;
    }

    /**
     * 读取档案
     */
    public function profile($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 查询账号
        $account = Account::get($uid);

        // 返回数据
        return Account::profile($account);
    }
}