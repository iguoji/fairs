<?php
declare(strict_types=1);

namespace App\Http\Account;

use App\Common\Admin;
use App\Common\Account;
use Minimal\Http\Validate;
use Minimal\Facades\Db;
use Minimal\Foundation\Exception;

/**
 * 删除账户
 */
class Remove
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 账户编号
        $validate->string('uid', '账户编号')->require()->call(function($value){
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

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 参数验证
            $params = self::validate($req->all());

            // 开启事务
            Db::beginTransaction();

            // 编号处理
            $uids = $params['uid'];
            if (!is_array($uids)) {
                $uids = [$uids];
            }
            unset($params['uid']);
            for ($i = 0;$i < count($uids); $i++) {
                // 执行操作
                Account::del($uids[$i]);
            }

            // 提交事务
            Db::commit();
        } catch (\Throwable $th) {
            // 事务回滚
            Db::rollback();
            // 抛出异常
            throw $th;
        }

        // 返回结果
        return $res->redirect('/accounts.html');
    }
}