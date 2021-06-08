<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Facades\Config;
use Minimal\Facades\Request;
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
     * 初始化
     */
    public static function init() : bool
    {
        return \Swoole\Coroutine\run(function(){
            // 获取数据库
            $config = Config::get('db', []);
            $config['pool'] = 1;
            \Minimal\Facades\App::set('database', new \Minimal\Database\Manager($config, 1));

            // 账号密码
            $username = 'admin';
            $password = '123123';

            // 是否存在
            $admin = static::get($username, 'username');
            if (!empty($admin)) {
                // 修改密码
                if (!static::upd($admin['id'], [
                    'password'  =>  $password,
                ])) {
                    throw new Exception('很抱歉、系统管理员密码重置失败！');
                }
            } else {
                // 增加账号
                if (!static::add([
                    'type'      =>  static::TYPE_SUPER,
                    'username'  =>  $username,
                    'password'  =>  $password,
                    'nickname'  =>  $username,
                ])) {
                    throw new Exception('很抱歉、系统管理员添加失败！');
                }
            }
        }) > 0;
    }

    /**
     * 授权验证
     */
    public static function verify($req) : mixed
    {
        // 获取信息
        $admin = $req->session->get('admin');
        if (empty($admin)) {
            throw new Exception('很抱歉、请登录后再操作！', 302, ['/signin.html']);
        }

        // 当前页面
        $path = $req->path();
        // 判断权限
        if (!static::check($path)) {
            throw new Exception('很抱歉、您没有权限执行该操作！', 302, ['/signout.html']);
        }

        // 日志记录
        static::log($req, $admin['id']);

        // 返回结果
        return $admin;
    }

    /**
     * 检查角色是否拥有该路径的权限
     */
    public static function check(string ...$paths) : bool
    {
        // 获取会话
        $admin = Request::session('admin');
        if (empty($admin)) {
            return false;
        }
        // 超级管理员
        if (Admin::isSuper($admin['id'])) {
            return true;
        }
        // 获取角色
        $role = $admin['role'];
        if (is_null($role)) {
            return false;
        }
        // 检查路径
        $nodes = Rbac::getRolePowers($role);
        $nodePaths = array_column($nodes, 'path');
        foreach ($paths as $key => $path) {
            $path = str_ends_with($path, '.html') ? $path : $path . '.html';
            if (!in_array($path, $nodePaths)) {
                return false;
            }
        }

        // 返回结果
        return true;
    }

    /**
     * 记录日志
     */
    public static function log($req, $adminId) : bool
    {
        return Db::table('admin_log')->insert([
            'admin'     =>  $adminId,
            'path'      =>  $req->path(),
            'method'    =>  $req->method(),
            'param'     =>  json_encode($req->all()),
            'ip'        =>  $req->header('x-real-ip'),
            'ua'        =>  $req->header('user-agent'),
            'created_at'=>  date('Y-m-d H:i:s'),
        ]) > 0;
    }

    /**
     * 日志列表
     */
    public static function logs(int $pageNo, int $size = 20) : array
    {
        // 数据总数
        $total = Db::table('admin_log', 'al')->count('id');
        // 查询数据
        $data = Db::table('admin_log', 'al')
            ->join('admin', 'a', 'a.id', 'al.admin')
            ->page($pageNo, $size)
            ->orderByDesc('al.id')
            ->all(
                'al.*', 'a.username', 'a.nickname'
            );
        // 返回结果
        return [$data, $total];
    }

    /**
     * 登录授权
     */
    public static function signin($req, $res, $admin, $expire = null) : string
    {
        // 过期时间
        $expire = $expire ?? $req->session->getConfig('expire');
        // 保存数据
        $req->session->set('admin', $admin, $expire);
        // SessionId
        $sessionId = $req->session->id();
        // 输出令牌
        $res->cookie($req->session->name(), $sessionId, time() + $expire);
        // 清理老的SessionId
        static::getout($req, $admin['id'], $sessionId);
        // 记录SessionId
        Cache::hSet('session:admin', (string) $admin['id'], $sessionId);

        // 返回结果
        return $sessionId;
    }

    /**
     * 退出登录
     */
    public static function signout($req, $res) : void
    {
        // 清除会话
        $req->session->clear();
        // 清除令牌
        $res->cookie($req->session->name(), 'deleted', time() - 3600);
    }

    /**
     * 针对某个账号执行退出
     */
    public static function getout($req, $adminId, $sessionId = null) : void
    {
        $oldSessionId = Cache::hGet('session:admin', (string) $adminId);
        if (!empty($oldSessionId)) {
            if (empty($sessionId) || $oldSessionId != $sessionId) {
                $req->session->clear($oldSessionId);
            }
        }
    }

    /**
     * 查询管理员
     */
    public static function get(string|int $id, string $field = 'id') : array
    {
        return Db::table('admin', 'a')
            ->leftJoin('rbac_role', 'rr', 'rr.id', 'a.role')
            ->where('a.' . $field, $id)
            ->where('a.deleted_at', null)
            ->where('rr.deleted_at', null)
            ->first(
                'a.*',
                ['rr.name' => 'roleName']
            );
    }

    /**
     * 是否存在管理员
     */
    public static function has(string|int $id, string $field = 'id') : bool
    {
        return !empty(static::get($id, $field));
    }

    /**
     * 是否为超级管理员
     */
    public static function isSuper(string|int $id, string $field = 'id') : bool
    {
        $admin = static::get($id, $field);
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
        return Db::table('admin', 'a')
            ->leftJoin('rbac_role', 'rr', 'rr.id', 'a.role')
            ->where('a.deleted_at', null)
            ->where('rr.deleted_at', null)
            ->all(
                'a.*',
                ['rr.name' => 'roleName'],
            );
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
    public static function upd(string|int $id, array $data) : bool
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
        return Db::table('admin')->where('id', $id)->update($data) > 0;
    }

    /**
     * 删除管理员
     */
    public static function del(string|int $id) : bool
    {
        return Db::table('admin')->where('id', $id)->update([
            'deleted_at'    =>  date('Y-m-d H:i:s')
        ]) > 0;
    }
}