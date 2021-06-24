<?php
declare(strict_types=1);

namespace App\Http\Brand;

use App\Common\Admin;
use App\Common\Brand;
use Minimal\Http\Validate;

/**
 * 品牌列表
 */
class Index
{
    /**
     * 参数验证
     */
    public function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 参数细节
        $validate->int('status', '状态');
        $validate->int('parent', '上级编号');

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

        // 数据列表
        list($list, $total) = Brand::all($params);

        // 返回结果
        return $res->html('admin/brand/index.html', [
            'params'    =>  $params,
            'list'      =>  $list,
            'total'     =>  $total,
        ]);
    }
}