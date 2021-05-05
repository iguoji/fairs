<?php
declare(strict_types=1);

namespace App\Open;

use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Foundation\Exception;

/**
 * 首页
 */
class Index
{
    public function index($req, $res)
    {
        $debug = Cache::inc('debug');

        try {
            Db::beginTransaction();


            Db::table('test')->insert([
                'content'   =>  $debug,
                'time'      =>  microtime(true),
            ]);

            if (mt_rand(1, 2) === 2) {
                throw new Exception('测试！');
            } else {
                ajax('https://www.ruciwan.com/search.php?mod=forum&searchid=867&orderby=lastpost&ascdesc=desc&searchsubmit=yes&kw=%B4%AB%C6%E6%B5%A5%BB%FA%B0%E6');
            }

            Log::info('我是[' . $debug . ']，我成功了');

            Db::commit();
        } catch (\Throwable $th) {
            // Log::info('我是[' . $debug . ']，我失败了');
            Db::rollback();
            throw $th;
        }

        // 返回结果
        return $debug;
    }
}