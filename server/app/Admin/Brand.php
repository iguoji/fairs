<?php
declare(strict_types=1);

namespace App\Admin;

use Minimal\Facades\View;

/**
 * 品牌类
 */
class Brand
{
    /**
     * 保存品牌
     */
    public function save($req, $res)
    {
        return View::fetch('Admin/Brand/save.php', [
            'name'      =>  'iguoji',
            'other'     =>  'iJing',
            'list'      =>  ['one', 'two', 'three'],
        ]);
    }
}