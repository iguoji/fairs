<?php
declare(strict_types=1);

namespace App\Open;

use Throwable;
use Minimal\Facades\Db;
use Minimal\Foundation\Exception;
use App\Common\Bank as BankCommon;
use App\Common\AccountBank as AccountBankCommon;
use App\Validate\AccountBank as AccountBankValidate;

/**
 * 银行卡类
 */
class AccountBank
{
    /**
     * 我的银行卡
     */
    public function my($req, $res)
    {
        return AccountBankCommon::my($req->uid);
    }

    /**
     * 新增银行卡
     */
    public function save($req, $res)
    {
        // 参数检查
        $data = AccountBankValidate::save($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 银行数据
            $bank = BankCommon::read($data['bank']);
            // 已帮卡数量
            $myBindCount = AccountBankCommon::singleCount($data['bank'], $req->uid);
            if ($bank['single_max_count'] > 0 && $myBindCount >= $bank['single_max_count']) {
                throw new Exception('很抱歉、您已达到' . $bank['name'] . '的可绑定数量上限！');
            }

            // 取消现有默认
            if (!empty($data['is_default'])) {
                AccountBankCommon::cancelCurrentDefault($req->uid);
            }

            // 执行保存
            $data['uid'] = $req->uid;
            if (!AccountBankCommon::save($data)) {
                throw new Exception('很抱歉、操作失败请重试！');
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

    /**
     * 编辑银行卡
     */
    public function edit($req, $res)
    {
        // 参数检查
        $data = AccountBankValidate::edit($req->uid, $req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 取消现有默认
            if (!empty($data['is_default'])) {
                AccountBankCommon::cancelCurrentDefault($req->uid);
            }

            // 执行修改
            $id = $data['id'];
            unset($data['id']);
            if (!AccountBankCommon::edit($id, $data)) {
                throw new Exception('很抱歉、操作失败请重试！');
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

    /**
     * 删除银行卡
     */
    public function remove($req, $res)
    {
        // 参数检查
        $data = AccountBankValidate::remove($req->uid, $req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 执行删除
            if (!AccountBankCommon::remove($data['id'])) {
                throw new Exception('很抱歉、操作失败请重试！');
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

    /**
     * 设置为默认银行卡
     */
    public function default($req, $res)
    {
        // 参数检查
        $data = AccountBankValidate::default($req->uid, $req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 取消现有默认
            AccountBankCommon::cancelCurrentDefault($req->uid);

            // 执行修改
            $id = $data['id'];
            unset($data['id']);
            if (!empty($data['is_default']) && !AccountBankCommon::edit($id, $data)) {
                throw new Exception('很抱歉、操作失败请重试！', 0, [Db::lastSql(), $data]);
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