<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Facades\App;
use Minimal\Facades\Config;
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
            App::set('database', new \Minimal\Database\Manager($config, 1));

            // 账号密码
            $username = 'admin';
            $password = '123123';

            // 是否存在
            if (self::get($username)) {
                // 修改密码
                if (!self::upd($username, [
                    'password'  =>  $password,
                ])) {
                    throw new Exception('很抱歉、系统管理员密码重置失败！');
                }
            } else {
                // 增加账号
                if (!self::add([
                    'type'      =>  self::TYPE_SUPER,
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
    public static function verify($req, $res) : mixed
    {
        // 从会话中获取
        $adminId = $req->session()->get('admin');

        // 从Header中获取
        if (empty($adminId)) {
            if (empty($req->header('authorization')) || !str_starts_with($req->header('authorization'), 'Admin ')) {
                throw new Exception('很抱歉、请登录后再操作！', 302, ['/signin.html']);
            }

            $token = substr($req->header('authorization'), 6);
            if (!Token::has($token)) {
                throw new Exception('很抱歉、登录超时请重新登录！', 302, ['/signin.html']);
            }

            $adminId = Token::get($token);
        }

        // 返回结果
        return $adminId;
    }

    /**
     * 查询管理员
     */
    public static function get(string $username) : array
    {
        return Db::table('admin')->where('username', $username)->where('deleted_at', null)->first();
    }

    /**
     * 是否存在管理员
     */
    public static function has(string $username) : bool
    {
        return !empty(static::get($username));
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