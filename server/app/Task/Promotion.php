<?php
declare(strict_types=1);

namespace App\Task;

use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
use App\Common\AccountPromotion;
use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Facades\Config;
use Minimal\Contracts\Task;
use Minimal\Foundation\Exception;

/**
 * 推广数据计划任务
 */
class Promotion implements Task
{
    /**
     * 每次处理多少人
     */
    public int $people = 500;

    /**
     * 是否激活
     */
    public function active() : bool
    {
        return true;
    }

    /**
     * 时间间隔
     */
    public function interval() : int
    {
        // 一分钟处理一次
        return 1000 * 60;
    }

    /**
     * 处理程序
     */
    public function handle() : bool
    {
        // 开始时间
        $start_at = microtime(true);
        // Log::debug('推广数据自动修正开始，时间：' . $start_at);

        try {
            // 任务分组
            $wg = new WaitGroup();
            // 上次编号
            $lastId = Cache::get('task:promotion:lastid', 0);
            // 获取账户
            $accounts = Db::table('account')->where('id', '>', $lastId)->orderBy('id')->limit($this->people)->all('id', 'uid');
            if (empty($accounts)) {
                $lastId = 0;
            }
            // 循环处理
            foreach ($accounts as $key => $account) {
                // 协程中处理
                $wg->add();
                Coroutine::create(function() use($wg, $account){
                    try {
                        // 计算下级数量
                        $childCount = AccountPromotion::compute($account['uid']);
                        // 更改下级数量
                        $bool = AccountPromotion::upd($account['uid'], array_combine(AccountPromotion::FIELDS, $childCount));
                        if (!$bool) {
                            throw new Exception('很抱歉、更新下级数量失败！');
                        }
                    } catch (\Throwable $th) {
                        Log::error('推广数据自动修正：' . $th->getMessage());
                    }
                    // 协程完成
                    $wg->done();
                });
                // 标记最后一人
                $lastId = $account['id'];
            }
            // 更新最后编号
            Cache::set('task:promotion:lastid', $lastId);
            // 等待全部任务完成
            $wg->wait();
        } catch (\Throwable $th) {
            Log::error('推广数据自动修正：' . $th->getMessage());
        }

        // Log::debug('推广数据自动修正，耗时：' . (microtime(true) - $start_at) . '秒');
        // 返回结果
        return true;
    }
}