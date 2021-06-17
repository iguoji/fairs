<?php
declare(strict_types=1);

namespace App\Http\Region;

use App\Common\Region;
use Minimal\Http\Validate;

/**
 * 地区数据
 */
class Index
{
    /**
     * 参数验证
     */
    public static function validate(array $params) : array
    {
        // 验证对象
        $validate = new Validate($params);

        // 地区类型
        $validate->int('type', '地区类型')->default(1);
        // 地区编号
        $validate->string('country', '国家编号')->digit();
        $validate->string('province', '省份编号')->digit();
        $validate->string('city', '城市编号')->digit();
        $validate->string('county', '区县编号')->digit();

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

        // 参数验证
        $params = self::validate($req->all());
        // 查询数据
        $result = Region::all($params);

        // 返回结果
        return $result;
    }
}