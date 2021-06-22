<?php
declare(strict_types=1);

namespace App\Http\Account\Authentication;

use App\Common\Admin;
use App\Common\Account;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 查看账户认证详情
 */
class Read
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        return [];
    }
}