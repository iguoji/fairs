<?php
declare(strict_types=1);

namespace App\Http\Brand;

use App\Common\Admin;
use App\Common\Brand;
use Minimal\Http\Validate;
use Minimal\Foundation\Exception;

/**
 * 编辑品牌
 */
class Edit
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
        $validate->int('sort', '排列顺序');
        $validate->int('status', '状态');
        $validate->string('icon', '图标')->nullable(true);
        $validate->string('name', '名称')->require()->length(1, 100)->call(function($value){
            return !empty($value);
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
        // 参数列表
        $params = $this->validate($req->all());

        // 编辑数据
        $id = $params['id'];
        unset($params['id']);
        if (!Brand::upd($id, $params)) {
            throw new Exception('很抱歉、编辑失败请重试！');
        }

        // 返回结果
        return $res->redirect('/brands.html');
    }
}