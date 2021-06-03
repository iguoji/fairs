<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Facades\App;
use Minimal\Facades\Config;
use Minimal\Facades\Request;
use Minimal\Foundation\Exception;

/**
 * 权限类
 */
class Rbac
{
    /**
     * 初始化
     */
    public static function init() : bool
    {
        return \Swoole\Coroutine\run(function(){
            // 获取数据库
            $config = Config::get('db', []);
            $config['pool'] = 1;
            App::set('database', new \Minimal\Database\Manager($config, 1));

            // 图标样式
            $icon_index = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>';
            $icon_goods = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3" /><line x1="12" y1="12" x2="20" y2="7.5" /><line x1="12" y1="12" x2="12" y2="21" /><line x1="12" y1="12" x2="4" y2="7.5" /><line x1="16" y1="5.25" x2="8" y2="9.75" /></svg>';

            // 准备数据
            // ['菜单名称', '链接地址', '图标样式', [子菜单列表], 是否显示, 排列顺序]
            $menus = [
                ['后台首页', '/index.html', $icon_index],

                ['商品管理', '', $icon_goods, [
                    ['类目', '/catalog/index.html', '', [
                        ['添加类目', '/catalog/save.html', '', [], 0],
                        ['编辑类目', '/catalog/edit.html', '', [], 0],
                        ['删除类目', '/catalog/remove.html', '', [], 0],
                    ]],
                    ['仓库', '/store/index.html'],
                ]],

                ['权限管理', '', $icon_goods, [
                    ['角色', '/rbac/role.html', '', [
                        ['添加角色', '/rbac/role/save.html', '', [], 0],
                        ['编辑角色', '/rbac/role/edit.html', '', [], 0],
                        ['删除角色', '/rbac/role/remove.html', '', [], 0],
                    ]],
                    ['管理员', '/rbac/admin/index.html', '', [
                        ['添加管理员', '/rbac/admin/save.html', '', [], 0],
                        ['编辑管理员', '/rbac/admin/edit.html', '', [], 0],
                        ['删除管理员', '/rbac/admin/remove.html', '', [], 0],
                    ]],
                    ['操作日志', '/rbac/log.html'],
                ]],
                ['系统管理', '', $icon_goods, [
                ]],
            ];

            try {
                // 先清空数据
                Db::table('rbac_node')->truncate();

                // 开启事务
                Db::beginTransaction();

                // 循环添加
                static::initRbacNodes($menus, 0, count($menus, 1));

                // 提交事务
                Db::commit();
            } catch (\Throwable $th) {
                Db::rollback();
                throw $th;
            }
        }) > 0;
    }

    /**
     * 初始化 - 批量添加权限节点
     */
    public static function initRbacNodes(array $menus, string|int $parent, int $count) : int
    {
        $date = date('Y-m-d H:i:s');
        foreach ($menus as $menu) {
            // 上级
            $data['parent'] = $parent;
            // 排序
            $data['sort'] = $count--;
            // 名称
            $data['name'] = $menu[0];
            // 链接
            $data['path'] = $menu[1];
            // 图标
            $data['icon'] = $menu[2] ?? '';
            // 可见
            $data['visible'] = $menu[4] ?? 1;
            // 时间
            $data['created_at'] = $date;
            // 保存数据
            if (!static::addNode($data)) {
                throw new Exception('很抱歉、节点' . $data['name'] . '添加失败！');
            }

            // 获取编号
            $id = Db::lastInsertId();

            // 子菜单
            $child = $menu[3] ?? [];
            if (!empty($child)) {
                $count = static::initRbacNodes($child, (int) $id, $count);
            }
        }

        return $count;
    }

    /**
     * 菜单渲染
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
        // 当前页面
        $path = Request::path();
        // 选中节点列表
        $choose = [];
        // 找到当前页面对应节点的所有上级节点
        $findParents = function($pid) use($nodes, &$choose, &$findParents){
            foreach ($nodes as $key => $node) {
                if ($node['id'] == $pid) {
                    $choose[] = $node['id'];
                    $findParents($node['parent']);
                }
            }
        };
        // 找到当前页面对应的节点
        foreach ($nodes as $key => $node) {
            if ($node['path'] == $path) {
                $choose[] = $node['id'];
                $findParents($node['parent']);
            }
        }

        // 循环处理
        $html = '';
        foreach ($nodes as $key => $node) {
            if (0 == $node['parent'] && 1 == $node['visible']) {
                $html .= static::fetchNodes($node, $nodes, $choose);
            }
        }
        // 返回结果
        return $html;
    }

    /**
     * 渲染节点
     */
    public static function fetchNodes(array $node, array $nodes, array $choose) : string
    {
        // 最终结果
        $html = '';

        // 当前节点
        $html .= '<div class="navbar-menu' . (in_array($node['id'], $choose) ? ' active' : '') . '">';
            // 子节点
            $subHtml = '';
            foreach ($nodes as $key => $subNode) {
                if ($subNode['parent'] == $node['id'] && 1 == $subNode['visible']) {
                    $subHtml .= static::fetchNodes($subNode, $nodes, $choose);
                }
            }
            // 当前节点
            $html .= '<a class="nav-link' . (empty($subHtml) ? '' : ' dropdown') . '" href="' . ($node['path'] ?: 'javascript:;') . '">';
                $html .= '<span class="nav-link-icon d-md-none d-lg-inline-block">';
                $html .= $node['icon'];
                $html .= '</span>';
                $html .= '<span class="nav-link-title">' . $node['name'] . '</span>';
            $html .= '</a>';
            // 添加子节点
            if (!empty($subHtml)) {
                $html .= $subHtml;
            }
        $html .= '</div>';

        // 返回结果
        return $html;
    }

    /**
     * 获取指定角色的所有权限
     */
    public static function getRolePower(string|int $role) : array
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
    public static function delPower(string|int $id) : bool
    {
        return Db::table('rbac_relation')->where('id', $id)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * 删除指定角色的所有权限
     */
    public static function delRolePower(string|int $role) : bool
    {
        return Db::table('rbac_relation')->where('role', $role)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }





    /**
     * 获取指定角色
     */
    public static function getRole(string|int $id) : array
    {
        return Db::table('rbac_role')->where('id', $id)->where('deleted_at')->first();
    }

    /**
     * 获取所有角色
     */
    public static function getRoles(string|int $parent = 0, bool $isTree = true, int $index = 0) : array
    {
        $result = [];

        $roles = Db::table('rbac_role')->where('parent', $parent)->where('deleted_at')->orderByDesc('sort')->orderBy('id')->all();
        foreach ($roles as $key => $role) {
            $role['index'] = $index;
            $childs = static::getRoles($role['id'], $isTree, $index + 1);
            if ($isTree) {
                $role['childs'] = $childs;
                $result[] = $role;
            } else {
                $result[] = $role;
                array_push($result, ...$childs);
            }
        }

        return $result;
    }

    /**
     * 角色是否存在
     */
    public static function hasRole(string|int $id) : bool
    {
        return !empty(static::getRole($id));
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
    public static function updRole(string|int $id, array $data) : bool
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
    public static function delRole(string|int $id) : bool
    {
        return static::updRole($id, [
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]);
    }





    /**
     * 获取指定节点
     */
    public static function getNode(string|int $id) : array
    {
        return Db::table('rbac_node')->where('id', $id)->where('deleted_at')->first();
    }

    /**
     * 获取所有节点
     */
    public static function getNodes(string|int $parent = null) : array
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
    public static function updNode(string|int $id, array $data) : bool
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
    public static function delNode(string|int $id) : bool
    {
        return static::updNode($id, [
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]);
    }
}