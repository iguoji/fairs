<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Foundation\Exception;

/**
 * 权限类
 */
class Rbac
{
    /**
     * 获取指定角色的所有权限
     */
    public static function getRolePowers(int $role) : array
    {
        return Db::table('rbac_relation', 'rr')
            ->join('rbac_node', 'rn', 'rn.id', 'rr.node')
            ->where('rr.role', $role)
            ->where('rr.deleted_at', null)
            ->where('rn.deleted_at', null)
            ->orderByDesc('rn.sort')
            ->all('rr.id', 'rn.type', 'rn.status', 'rn.parent', 'rn.sort', 'rn.name', 'rn.path', 'rn.icon');
    }

    /**
     * 添加权限
     */
    public static function addPower(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $date['created_at'] = date('Y-m-d H:i:s');
        }

        // 添加数据
        return Db::table('rbac_relation')->insert($data) > 0;
    }

    /**
     * 删除权限
     */
    public static function delPower(int $id) : bool
    {
        return Db::table('rbac_relation')->where('id', $id)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * 删除指定角色的所有权限
     */
    public static function delRolePower(int $role) : bool
    {
        return Db::table('rbac_relation')->where('role', $role)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * 获取指定角色
     */
    public static function getRole(int $id) : array
    {
        return Db::table('rbac_role')->where('id', $id)->where('deleted_at', null)->first();
    }

    /**
     * 获取所有角色
     */
    public static function getRoles(int $parent = 0) : array
    {
        return Db::table('rbac_role')->where('parent', $parent)->where('deleted_at', null)->all();
    }

    /**
     * 添加角色
     */
    public static function addRole(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $date['created_at'] = date('Y-m-d H:i:s');
        }

        // 添加数据
        return Db::table('rbac_role')->insert($data) > 0;
    }

    /**
     * 修改角色
     */
    public static function updRole(int $id, array $data) : bool
    {
        // 补充时间
        if (!isset($data['updated_at'])) {
            $date['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('rbac_role')->where('id', $id)->update($data) > 0;
    }

    /**
     * 删除角色
     */
    public static function delRole(int $id) : bool
    {
        return Db::table('rbac_role')->where('id', $id)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * 获取指定节点
     */
    public static function getNode(int $id) : array
    {
        return Db::table('rbac_node')->where('id', $id)->where('deleted_at', null)->first();
    }

    /**
     * 获取所有节点
     */
    public static function getNodes(int $parent = 0) : array
    {
        return Db::table('rbac_node')->where('parent', $parent)->where('deleted_at', null)->orderByDesc('sort')->all();
    }

    /**
     * 添加节点
     */
    public static function addNode(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $date['created_at'] = date('Y-m-d H:i:s');
        }

        // 添加数据
        return Db::table('rbac_node')->insert($data) > 0;
    }

    /**
     * 修改节点
     */
    public static function updNode(int $id, array $data) : bool
    {
        // 补充时间
        if (!isset($data['updated_at'])) {
            $date['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('rbac_node')->where('id', $id)->update($data) > 0;
    }

    /**
     * 删除节点
     */
    public static function delNode(int $id) : bool
    {
        return Db::table('rbac_node')->where('id', $id)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }
}