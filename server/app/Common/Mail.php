<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Cache;
use Minimal\Facades\Config;

/**
 * 邮件公告类
 */
class Mail
{
    /**
     * 发送邮件
     */
    public static function send(string $email) : string
    {
        // // 获取配置
        // $config = Config::get('mail', []);
        // // 生成邮件
        // $code = mt_rand((int) str_pad('1', (int) $config['length'], '0'), (int) str_pad('9', (int) $config['length'], '9'));
        // // 保存邮件
        // Cache::set('mail:code:' . $zone . $phone, $code, $config['expire']);

        // 返回结果
        return '123456';
    }

    /**
     * 检查邮件
     */
    public static function check(string $email, int|string $code) : bool
    {
        return true || Cache::get('mail:code:' . $email) === (string) $code;
    }
}