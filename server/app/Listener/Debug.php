<?php
declare(strict_types=1);

namespace App\Listener;

use Exception;
use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Contracts\Listener;

/**
 * 调试事件
 */
class Debug implements Listener
{
    /**
     * 监听事件
     */
    public function events() : array
    {
        return [
            'Debug:OnInit',
        ];
    }

    /**
     * 事件处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {

        $arr = [
            'a1'    =>  [
                'b1'    =>  [
                    'c1'    =>  [1, 2, 3],
                    'c2'    =>  [4, 5, 6],
                ],
                'b2'    =>  [
                    'c1'    =>  [1, 2, 3],
                    'c2'    =>  [4, 5, 6],
                ],
            ],
        ];

        var_dump($arr?->a1?->b1);

        return true;
    }
}