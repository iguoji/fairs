<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
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

        // Ajax
        if ($req->isAjax()) {
            // 参数验证
            $params = $this->validate($req->all());
            // 查询账号
            $account = Account::get($params['uid']);

            // 返回结果
            return $account;
        } else {

            // 验证参数
            $params = [];
            // 国家信息
            $countrys = [];
            // 账户列表
            $account = [];
            // 异常信息
            $exception = [];

            try {
                // 验证参数
                $params = $this->validate($req->all());
                // 国家信息
                $countrys = Region::countrys();
                // 账户列表
                $account = Account::get($params['uid']);
            } catch (\Throwable $th) {
                // 保存异常
                $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
            }

            // 返回结果
            return $res->html('admin/account/read', [
                'params'    =>  $params,
                'account'   =>  $account,
                'countrys'  =>  $countrys,
                'exception' =>  json_encode($exception, JSON_UNESCAPED_UNICODE),
            ]);
        }
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