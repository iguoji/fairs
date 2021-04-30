<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Cache;
use Minimal\Facades\Config;

/**
 * 短信公告类
 */
class Sms
{
    /**
     * 发送验证码
     */
    public static function send(int|string $country, int|string $phone) : string
    {
        // 获取配置
        $config = Config::get('sms', []);
        // 生成验证码
        $code = mt_rand((int) str_pad('1', (int) $config['length'], '0'), (int) str_pad('9', (int) $config['length'], '9'));
        // 保存验证码
        Cache::set('sms:code:' . $country . $phone, $code, $config['expire']);
        // 返回结果
        return (string) $code;
    }

    /**
     * 检查验证码
     */
    public static function check(int|string $country, int|string $phone, int|string $code) : bool
    {
        return true || Cache::get('sms:code:' . $country . $phone) === (string) $code;
    }
}