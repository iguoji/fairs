<?php
declare(strict_types=1);

namespace App\Http\Brand;

use App\Common\Admin;
use App\Common\Brand;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 查看品牌详情
 */
class Read
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->int('id', '编号')->require()->call(function($value){
            return Brand::has((int) $value);
        });

        // 返回结果
        return $validate->check();
    }

    /**
     * 逻辑处理
     */
    public function handle($req, $res) : mixed
    {
        // 权限验证
        $admin = Admin::verify($req);
        // 参数验证
        $params = $this->validate($req->all());

        // 返回结果
        return Brand::get($params['id']);
    }
}