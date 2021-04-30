<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;

/**
 * 实名认证类
 */
class Authentic
{
    /**
     * 认证类型
     */
    const IDCARD = 1;       // 姓名 + 身份证号码 + 正面照片 + 反面照片

    /**
     * 认证状态
     */
    const EMPTY = -1;
    const SUCCESS = 1;
    const FAIL = 0;
    const WAIT = 2;

    /**
     * 所需资料
     */
    const FIELDS = [
        self::IDCARD    =>  ['name' => '真实姓名', 'code' => '身份证号码', 'front' => '正面照片', 'back' => '反面照片'],
    ];

    /**
     * 提交认证
     */
    public static function post(array $data, int $type = self::IDCARD) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        // 保存数据
        return Db::table('account_authenticate')->insert($data);
    }

    /**
     * 已认证通过
     */
    public static function passed(string $uid, int $type = self::IDCARD) : bool
    {
        return !empty(Db::table('account_authenticate')->where('uid', $uid)->where('type', $type)->where('status', self::SUCCESS)->first());
    }

    /**
     * 认证状态
     */
    public static function status(string $uid, int $type = self::IDCARD) : int|array
    {
        $auth = Db::table('account_authenticate')->where('uid', $uid)->where('type', $type)->orderByDesc('id')->first();
        if (empty($auth)) {
            return self::EMPTY;
        }
        return $auth['status'] == self::FAIL ? [self::FAIL, $auth['reason']] : $auth['status'];
    }
}