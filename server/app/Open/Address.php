<?php
declare(strict_types=1);

namespace App\Open;

use Throwable;
use Minimal\Facades\Db;
use App\Common\Account as AccountCommon;
use App\Common\Address as AddressCommon;
use App\Validate\Address as AddressValidate;

/**
 * 收货地址类
 */
class Address
{
    /**
     * 我的地址
     */
    public function my($req, $res)
    {
        return AddressCommon::my($req->uid);
    }

    /**
     * 新增地址
     */
    public function save($req, $res)
    {
        // 参数检查
        $data = AddressValidate::save($req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 取消现有默认
            if (!empty($data['is_default'])) {
                AddressCommon::cancelCurrentDefault($req->uid);
            }

            // 执行保存
            $data['uid'] = $req->uid;
            if (!AddressCommon::save($data)) {
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
     * 编辑地址
     */
    public function edit($req, $res)
    {
        // 参数检查
        $data = AddressValidate::edit($req->uid, $req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 取消现有默认
            if (!empty($data['is_default'])) {
                AddressCommon::cancelCurrentDefault($req->uid);
            }

            // 执行修改
            $id = $data['id'];
            unset($data['id']);
            if (!AddressCommon::edit($id, $data)) {
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
     * 删除地址
     */
    public function remove($req, $res)
    {
        // 参数检查
        $data = AddressValidate::remove($req->uid, $req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 执行删除
            if (!AddressCommon::remove($data['id'])) {
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
     * 设置为默认地址
     */
    public function default($req, $res)
    {
        // 参数检查
        $data = AddressValidate::default($req->uid, $req->post ?? []);

        try {
            // 开启事务
            Db::beginTransaction();

            // 取消现有默认
            AddressCommon::cancelCurrentDefault($req->uid);

            // 执行修改
            $id = $data['id'];
            unset($data['id']);
            if (!AddressCommon::edit($id, $data)) {
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
}