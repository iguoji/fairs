<?php
declare(strict_types=1);

namespace App\Http\Rbac\Admin;

use App\Common\Rbac;
use App\Common\Admin;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 读取管理员
 */
class Read
{
    /**
     * 参数验证
     */
    public static function verify(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 管理员编号
        $validate->int('id', '管理员编号')->require()->call(function($value){
            return Admin::hasById($value);
        });

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 最终结果
        $result = [];

        // 权限验证
        $admin = Admin::verify($req);
        // 参数验证
        $data = self::verify($req->all());
        // 查询数据
        $result = Admin::getById($data['id']);

        // 返回结果
        return $result;
    }
}