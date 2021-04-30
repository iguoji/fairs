<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Cache;

/**
 * 登录令牌类
 */
class Token
{
    /**
     * 保存令牌
     */
    public static function set(string $uid, string $token, int $expire = null) : void
    {
        // UID -> Token
        Cache::set('middleware:token:account:' . $uid, $token, $expire);

        // Token -> UID
        Cache::set('middleware:token:' . $token, $uid, $expire);
    }

    /**
     * 获取令牌
     */
    public static function get(string $token) : string
    {
        return Cache::get('middleware:token:' . $token);
    }

    /**
     * 获取令牌、根据用户编号
     */
    public static function getByUid(string $uid) : string
    {
        if (Cache::has('middleware:token:account:' . $uid)) {
            return self::get(Cache::get('middleware:token:account:' . $uid));
        }
        return '';
    }

    /**
     * 是否存在
     */
    public static function has(string $token) : bool
    {
        return Cache::has('middleware:token:' . $token);
    }

    /**
     * 删除令牌
     */
    public static function remove(string $token) : void
    {
        Cache::delete('middleware:token:' . $token);
    }

    /**
     * 删除令牌 - 根据用户编号
     */
    public static function removeByUid(string $uid) : void
    {
        $token = self::getByUid($uid);
        if (!empty($token)) {
            self::remove($token);
        }
    }
}