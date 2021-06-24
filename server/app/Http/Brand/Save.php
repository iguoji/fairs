<?php
declare(strict_types=1);

namespace App\Http\Brand;

use App\Common\Admin;
use App\Common\Brand;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 添加类目
 */
class Save
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->int('sort', '排列顺序')->default(0);
        $validate->int('status', '状态')->default(1);
        $validate->string('name', '名称')->require()->length(1, 100)->call(function($value){
            return !empty($value);
        });
        $validate->string('icon', '图标');

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
        // 参数列表
        $params = $this->validate($req->all());

        // 添加数据
        if (!Brand::add($params)) {
            throw new Exception('很抱歉、添加失败请重试！');
        }

        // 返回结果
        return $res->redirect('/brands.html');
    }
}