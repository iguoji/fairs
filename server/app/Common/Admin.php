<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Foundation\Exception;

/**
 * 管理员类
 */
class Admin
{
    /**
     * 管理员类型
     */
    public const TYPE_DEFAULT = 1;  // 普通管理员
    public const TYPE_SUPER = -1;  // 系统管理员


    /**
     * 查询管理员
     */
    public static function get(string $username) : array
    {
        return Db::table('admin')->where('username', $username)->where('deleted_at', null)->first();
    }

    /**
     * 是否为超级管理员
     */
    public static function isSuper(string $username) : bool
    {
        $admin = static::get($username);
        if (!empty($admin) && $admin['type'] == static::TYPE_SUPER) {
            return true;
        }
        return false;
    }

    /**
     * 管理员列表
     */
    public static function all() : array
    {
        return Db::table('admin')->where('deleted_at', null)->all();
    }

    /**
     * 添加管理员
     */
    public static function add(array $data) : bool
    {
        // 登录密码
        if (isset($data['password'])) {
            $data['password'] = Account::encrypt($data['password']);
        }
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        // 添加数据
        return Db::table('admin')->insert($data) > 0;
    }

    /**
     * 修改管理员
     */
    public static function upd(string $username, array $data) : bool
    {
        // 登录密码
        if (isset($data['password'])) {
            $data['password'] = Account::encrypt($data['password']);
        }
        // 补充时间
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('admin')->where('username', $username)->update($data) > 0;
    }

    /**
     * 删除管理员
     */
    public static function del(string $username) : bool
    {
        return Db::table('admin')->where('username', $username)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }
}