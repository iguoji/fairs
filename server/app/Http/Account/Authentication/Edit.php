<?php
declare(strict_types=1);

namespace App\Http\Account\Authentication;

use App\Common\Admin;
use App\Common\Region;
use App\Common\Account;
use App\Common\Authentication;
use Minimal\Facades\Db;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 编辑账户关系
 */
class Edit
{
    /**
     * 管理员参数验证
     */
    public static function adminValidate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->int('id', '认证编号')->require()->call(function($value){
            if (!is_array($value)) {
                $value = [$value];
            }
            for ($i = 0;$i < count($value);$i++) {
                if (!Authentication::has((int) $value[$i])) {
                    return false;
                }
            }
            return true;
        });
        $validate->int('status', '状态')->in(0, 1, 2);
        $validate->string('reason', '原因');

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
            // 管理员编辑
            return $this->admin($req, $res);
        } else {
            // 用户重新提交认证
            return $this->account($req, $res);
        }
    }

    /**
     * 管理员编辑
     */
    public function admin($req, $res): mixed
    {
        // 权限验证
        $admin = Admin::verify($req);

        try {
            // 参数检查
            $params = $this->adminValidate($req->all());

            // 开启事务
            Db::beginTransaction();

            // 修改资料
            $idList = $params['id'];
            if (!is_array($idList)) {
                $idList = [$idList];
            }
            unset($params['id']);
            for ($i = 0;$i < count($idList); $i++) {
                $bool = Authentication::upd($idList[$i], $params);
                if (!$bool) {
                    throw new Exception('很抱歉、修改失败请重试！');
                }
            }

            // 如果是修改的认证状态，那么必须要更新账户那边的认证编号
            if (isset($params['status'])) {
                // 获取这一批认证资料所属的用户
                $uidList = Authentication::getUidList($idList);
                // 本次调整的状态
                $status = $params['status'];
                // 循环处理
                foreach ($uidList as $id => $uid) {
                    // 本次请求目的是取消认证
                    if ($status == 0) {
                        // 先查找一个最新的已通过的认证编号
                        $authenticate = Authentication::getAccountLast($uid);
                        // 更新编号
                        $id = $authenticate['id'] ?? 0;
                    }
                    // 直接更新认证编号
                    $bool = Account::upd($uid, ['authenticate' => $id]);
                    if (empty($bool)) {
                        throw new Exception('很抱歉、更新用户认证编号失败！');
                    }
                }
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
        return $res->redirect('/account/authentications.html');
    }
}