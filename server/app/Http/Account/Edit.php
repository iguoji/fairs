<?php
declare(strict_types=1);

namespace App\Http\Account;

use Throwable;
use App\Common\Admin;
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
     * 参数验证 - 管理员
     */
    public function adminValidate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 编号
        $validate->string('uid', '账号编号')->require()->alphaNum()->call(function($value){
            if (!is_array($value)) {
                $value = [$value];
            }
            for ($i = 0;$i < count($value); $i++) {
                if (!Account::has($value[$i])) {
                    return false;
                }
            }
            return true;
        });

        // 手机号码
        $validate->string('phone', '手机号码')
            ->length(5, 30)->digit()
            ->call(function($value, $values){
                $account = Account::getByPhone($value);
                return empty($account) || $account['uid'] == $values['uid'];
            }, message: '很抱歉、该手机号码已被注册！');
        // 邮箱
        $validate->string('email', '邮箱地址')
            ->length(6, 64)->email()
            ->call(function($value, $values){
                $account = Account::get($value, 'email');
                return empty($account) || $account['uid'] == $values['uid'];
            }, message: '很抱歉、邮箱地址已被注册！');
        // 账号
        $validate->string('username', '账号')
            ->length(6, 32)->alphaNum()
            ->call(function($value){
                return 1 === preg_match('/^[A-Za-z]{1}$/', $value[0]);
            }, message: '很抱歉、账号的第一位必须是字母！')
            ->call(function($value, $values){
                $account = Account::get($value, 'username');
                return empty($account) || $account['uid'] == $values['uid'];
            }, message: '很抱歉、该账号已被注册！');
        // 上级邀请码
        $validate->string('inviter', '邀请码')
            ->alphaNum()->length(3, 32)
            ->call(function($value, $values){
                return $value != $values['uid'] && !empty(Account::get($value));
            }, message: '很抱歉、指定的邀请码不存在！')
            ->call(function($value, $values){
                return !in_array($values['uid'], Account::getParents($value));
            }, message: '很抱歉、无法将上级设置为该账号！');
        // 账户状态
        $validate->int('status', '账户状态');

        // 密码
        $validate->string('password', '登录密码')->length(6, 32);
        $validate->string('safeword', '安全密码')->length(6, 32);

        // 社交属性
        $validate->string('nickname', '昵称')->length(2, 20)->chsDash();
        $validate->string('avatar', '头像')->length(2, 150);
        $validate->int('gender', '性别')->in(0, 1, 2)->digit();
        $validate->string('birthday', '出生年月')->date('Y-m-d');

        // 省市区
        $validate->string('province', '省份')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 2);
        });
        $validate->string('city', '城市')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 3);
        })->requireWith('province');
        $validate->string('county', '区县')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 4);
        })->requireWith('province', 'city');

        // 返回结果
        return $validate->check();
    }

    /**
     * 参数验证 - 用户
     */
    public function accountValidate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 基本资料
        $validate->string('nickname', '昵称')->length(2, 20)->chsDash();
        $validate->string('avatar', '头像')->length(2, 150);
        $validate->int('gender', '性别')->in(1, 2)->digit();
        $validate->string('birthday', '出生年月')->date('Y-m-d');

        // 省市区
        $validate->string('province', '省份')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 2);
        });
        $validate->string('city', '城市')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 3);
        })->requireWith('province');
        $validate->string('county', '区县')->length(1, 30)->digit()->call(function($value, $values){
            return Region::has($value, 4);
        })->requireWith('province', 'city');

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 等待：修改上级的时候，需要做好应对死循环关联的判断
        // 获取身份
        $identity = $req->session->identity();
        // 判断身份
        if ($identity == 'admin') {
            // 管理员操作
            return $this->admin($req, $res);
        } else {
            // 用户操作
            return $this->account($req, $res);
        }
    }

    /**
     * 管理员操作
     */
    public function admin($req, $res) : mixed
    {
        // 异常错误
        $exception = [];

        // 授权验证
        $admin = Admin::verify($req);

        try {
            // 参数检查
            $params = $this->adminValidate($req->all());

            // 开启事务
            Db::beginTransaction();

            // 修改资料
            $uids = $params['uid'];
            if (!is_array($uids)) {
                $uids = [$uids];
            }
            unset($params['uid']);
            for ($i = 0;$i < count($uids); $i++) {
                $bool = Account::upd($uids[$i], $params);
                if (!$bool) {
                    throw new Exception('很抱歉、修改失败请重试！');
                }
            }

            // 提交事务
            Db::commit();
        } catch (\Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 保存异常
            $exception = [$th->getCode(), $th->getMessage(), method_exists($th, 'getData') ? $th->getData() : [] ];
        }

        // 返回结果
        if ($req->isAjax()) {
            return $res->json([], $exception[0] ?? 200, $exception[1] ?? '恭喜您、操作成功！');
        } else {
            return $res->redirect('/accounts.html', [
                'exception' =>  $exception,
            ]);
        }
    }

    /**
     * 用户操作
     */
    public function account($req, $res) : mixed
    {
        // 授权验证
        $uid = Account::verify($req);

        // 参数检查
        $data = $this->accountValidate($req->all());

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