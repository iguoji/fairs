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
     * 渲染
     */
    public static function fetch() : string
    {
        // 获取节点
        $nodes = [];
        if (Admin::isSuper('admin')) {
            $nodes = static::getNodes();
        } else {
            $nodes = static::getRolePower(0);
        }

        // 关联节点
        $map = [];
        foreach ($nodes as $key => $node) {
            if (0 == $node['parent']) {
                $map[] = static::nodeChild($node, $nodes);
            }
        }


        // 循环处理
        $html = static::fetchNodes($map);

        // 返回结果
        return $html;
    }

    /**
     * 节点下级
     */
    protected static function nodeChild(array $node, array $nodes) : array
    {
        foreach ($nodes as $id => $item) {
            if ($item['parent'] == $node['id']) {
                $node['child'][] = static::nodeChild($item, $nodes);
            }
        }
        return $node;
    }

    /**
     * 渲染节点
     */
    public static function fetchNodes(array $nodes, int $depth = 1) : string
    {
        // 节点容器
        $html = '';
        if ($depth == 1) {
            $html .= '<div class="navbar-collapse" id="navbar-menu">';
            $html .= '<ul class="navbar-nav pt-lg-3">';
        } else {
            $html .= '<div class="dropdown-menu">';
            if ($depth == 2) {
                $html .= '<div class="dropdown-menu-columns">';
                $html .= '<div class="dropdown-menu-column">';
            }
        }
        // 循环处理
        foreach ($nodes as $key => $node) {
            // 是否为父节点
            $isParent = !empty($node['child']);
            // 按深度处理代码
            if ($depth == 1) {
                // 一级菜单
                $html .= '<li class="nav-item' . ($isParent ? ' dropdown' : '') . '">';
                // 添加子节点
                if ($isParent) {
                    $html .= '<a class="nav-link dropdown-toggle" href="#dropdown" data-bs-toggle="dropdown" role="button" aria-expanded="false">';
                        $html .= '<span class="nav-link-icon d-md-none d-lg-inline-block">';
                        $html .= $node['icon'];
                        $html .= '</span>';
                        $html .= '<span class="nav-link-title">' . $node['name'] . '</span>';
                    $html .= '</a>';
                    $html .= static::fetchNodes($node['child'], $depth + 1);
                } else {
                    $html .= '<a class="nav-link" href="' . ($node['path'] ?: 'javascript:;') . '">';
                        $html .= '<span class="nav-link-icon d-md-none d-lg-inline-block">';
                        $html .= $node['icon'];
                        $html .= '</span>';
                        $html .= '<span class="nav-link-title">' . $node['name'] . '</span>';
                    $html .= '</a>';
                }
                $html .= '</li>';
            } else {
                // 二级菜单
                if ($isParent) {
                    $html .= '<div class="dropend">';
                        $html .= '<a class="dropdown-item dropdown-toggle" href="#dropdown" data-bs-toggle="dropdown" role="button" aria-expanded="false">';
                        $html .= $node['name'];
                        $html .= '</a>';
                        $html .= static::fetchNodes($node['child'], $depth + 1);
                    $html .= '</div>';
                } else {
                    $html .= '<a class="dropdown-item" href="' . ($node['path'] ?: 'javascript:;') . '">' . $node['name'] . '</a>';
                }
            }
        }
        // 节点容器 - 结束
        if ($depth == 1) {
            $html .= '</ul>';
            $html .= '</div>';
        } else {
            if ($depth == 2) {
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * 获取指定角色的所有权限
     */
    public static function getRolePower(int $role) : array
    {
        return Db::table('rbac_relation', 'rr')
            ->join('rbac_node', 'rn', 'rn.id', 'rr.node')
            ->where('rr.role', $role)
            ->where('rr.deleted_at', null)
            ->where('rn.deleted_at', null)
            ->orderByDesc('rn.sort')
            ->orderBy('rn.id')
            ->all(
                'rr.id',
                'rn.type', 'rn.status', 'rn.parent', 'rn.sort', 'rn.name', 'rn.path', 'rn.icon'
            );
    }

    /**
     * 添加权限
     */
    public static function addPower(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
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
        return Db::table('rbac_role')->where('id', $id)->where('deleted_at')->first();
    }

    /**
     * 获取所有角色
     */
    public static function getRoles(int $parent = 0) : array
    {
        return Db::table('rbac_role')->where('parent', $parent)->where('deleted_at')->all();
    }

    /**
     * 添加角色
     */
    public static function addRole(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
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
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('rbac_role')->where('id', $id)->update($data) > 0;
    }

    /**
     * 删除角色
     */
    public static function delRole(int $id) : bool
    {
        return static::updRole($id, [
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]);
    }





    /**
     * 获取指定节点
     */
    public static function getNode(int $id) : array
    {
        return Db::table('rbac_node')->where('id', $id)->where('deleted_at')->first();
    }

    /**
     * 获取所有节点
     */
    public static function getNodes(int $parent = null) : array
    {
        $query = Db::table('rbac_node');
        if (!is_null($parent)) {
            $query->where('parent', $parent);
        }
        return $query->where('deleted_at')
            ->orderByDesc('sort')
            ->orderBy('id')
            ->all();
    }

    /**
     * 添加节点
     */
    public static function addNode(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
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
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 修改数据
        return Db::table('rbac_node')->where('id', $id)->update($data) > 0;
    }

    /**
     * 删除节点
     */
    public static function delNode(int $id) : bool
    {
        return static::updNode($id, [
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]);
    }
}