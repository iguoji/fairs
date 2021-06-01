<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Support\Str;
use Minimal\Facades\Cache;

/**
 * 登录令牌类
 */
class Token
{
    /**
     * 拼合Key
     */
    public static function key(string|int ...$keys) : string
    {
        array_unshift($keys, 'session');

        return implode(':', $keys);
    }

    /**
     * 生成令牌
     */
    public static function new(string|int $primaryKey, string $module = 'account', int $expire = null) : string
    {
        // 生成Token
        do {
            $token = Str::random(64);
        } while(Cache::has($token));

        // 删除老的令牌
        static::clear($primaryKey, $module);

        // primaryKey -> Token
        Cache::set(static::key($module, $primaryKey), $token, $expire);

        // Token -> primaryKey
        Cache::set(static::key($token), $primaryKey, $expire);

        // 返回Token
        return $token;
    }

    /**
     * 获取主键
     */
    public static function get(string $token) : string
    {
        return Cache::get(static::key($token));
    }

    /**
     * 是否存在
     */
    public static function has(string $token) : bool
    {
        return Cache::has(static::key($token));
    }

    /**
     * 移除令牌
     */
    public static function clear(string|int $primaryKey, string $module = 'account') : void
    {
        $token = Cache::get(static::key($module, $primaryKey));
        if (!empty($token)) {
            Cache::delete(static::key($module, $primaryKey));
            Cache::delete(static::key($token));
        }
    }
}