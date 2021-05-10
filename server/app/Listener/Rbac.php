<?php
declare(strict_types=1);

namespace App\Listener;

use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Application;
use Minimal\Facades\Cache;
use Minimal\Support\Arr;
use Minimal\Contracts\Listener;
use Minimal\Foundation\Exception;
use App\Common\Rbac as RbacCommon;
use App\Common\Admin as AdminCommon;
use Minimal\Database\Manager as Database;
use Swoole\Coroutine;

/**
 * 后台权限事件
 */
class Rbac implements Listener
{
    /**
     * 构造函数
     */
    public function __construct(protected Application $app)
    {}

    /**
     * 监听事件
     */
    public function events() : array
    {
        return [
            'Rbac:OnInit',
        ];
    }

    /**
     * 事件处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 绑定数据库
        Coroutine::create(function(){
            $config = $this->app->config->get('db', []);
            $config['pool'] = 1;
            $this->app->set('database', new Database($config, 1));
        });

        // 初始化
        if ($event == 'Rbac:OnInit') {
            Coroutine::create(function(){
                // 系统管理员
                $this->admin();
                // 导航菜单
                $this->menus();
            });
        }

        // 返回结果
        return true;
    }

    /**
     * 系统管理员
     */
    public function admin() : bool
    {
        // 账号密码
        $username = 'admin';
        $password = '123123';

        // 是否存在
        if (AdminCommon::get($username)) {
            // 修改密码
            if (!AdminCommon::upd($username, [
                'password'  =>  $password,
            ])) {
                throw new Exception('很抱歉、系统管理员密码重置失败！');
            }
        } else {
            // 增加账号
            if (!AdminCommon::add([
                'type'      =>  AdminCommon::TYPE_SUPER,
                'username'  =>  $username,
                'password'  =>  $password,
                'nickname'  =>  $username,
            ])) {
                throw new Exception('很抱歉、系统管理员添加失败！');
            }
        }

        // 返回结果
        return true;
    }

    /**
     * 导航菜单
     */
    public function menus() : bool
    {
        // 图标样式
        $icon_index = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>';
        $icon_goods = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 12 3" /><line x1="12" y1="12" x2="20" y2="7.5" /><line x1="12" y1="12" x2="12" y2="21" /><line x1="12" y1="12" x2="4" y2="7.5" /><line x1="16" y1="5.25" x2="8" y2="9.75" /></svg>';

        // 准备数据
        // ['菜单名称', '链接地址', '图标样式', [子菜单列表], 是否显示, 排列顺序]
        $menus = [
            ['后台首页', '/admin/index.html', $icon_index],

            ['商品管理', '', $icon_goods, [
                ['类目', '/admin/catalog/index.html', '', [
                    ['添加类目', '/admin/catalog/save.html'],
                    ['编辑类目', '/admin/catalog/edit.html'],
                    ['删除类目', '/admin/catalog/remove.html'],
                ]],
                ['仓库', '/admin/store/index.html'],
            ]],

            ['权限管理', '', $icon_goods, [
                ['角色', '/admin/rbac/role/index.html', '', [
                    ['添加角色', '/admin/rbac/role/save.html', '', [], 0],
                    ['编辑角色', '/admin/rbac/role/edit.html', '', [], 0],
                    ['删除角色', '/admin/rbac/role/remove.html', '', [], 0],
                ]],
                ['管理员', '/admin/rbac/admin/index.html', '', [
                    ['添加管理员', '/admin/rbac/admin/save.html', '', [], 0],
                    ['编辑管理员', '/admin/rbac/admin/edit.html', '', [], 0],
                    ['删除管理员', '/admin/rbac/admin/remove.html', '', [], 0],
                ]],
                ['操作日志', '/admin/rbac/log.html'],
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
            $this->addRbacNodes($menus, 0, count($menus, 1));

            // 提交事务
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }


        // 返回结果
        return true;
    }

    /**
     * 批量添加权限节点
     */
    public function addRbacNodes(array $menus, int $parent, int $count) : int
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
            if (!RbacCommon::addNode($data)) {
                throw new Exception('很抱歉、节点' . $data['name'] . '添加失败！');
            }

            // 获取编号
            $id = Db::lastInsertId();

            // 子菜单
            $child = $menu[3] ?? [];
            if (!empty($child)) {
                $count = $this->addRbacNodes($child, (int) $id, $count);
            }
        }

        return $count;
    }
}