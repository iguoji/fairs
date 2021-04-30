<?php
declare(strict_types=1);

namespace App\Listener;

use Exception;
use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Contracts\Listener;

/**
 * 银行事件
 */
class Bank implements Listener
{
    /**
     * 监听事件
     */
    public function events() : array
    {
        return [
            'Bank:OnInit',
        ];
    }

    /**
     * 事件处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 初始化
        if ($event == 'Bank:OnInit') {
            return $this->init();
        }

        // 返回结果
        return true;
    }

    /**
     * 初始化数据
     * 京东：https://authpay.jd.com/card/addQuickCardVm
     * 打开控制台：var data = [];$('.bankList1').each((idx, ele) => { data.push({ name: $(ele).data('name'), code: $(ele).data('code'), type: $(ele).data('cardtype') }) });console.log(JSON.stringify(data));
     */
    public function init() : bool
    {
        // 获取数据
        $json = file_get_contents(dirname(dirname(__DIR__)) . '/config/bank.json');
        $banks = json_decode($json, true);

        // 循环数据
        $data = [];
        $date = date('Y-m-d H:i:s');
        foreach ($banks as $bank) {
            $bank['type'] = ($bank['type'] == 1 ? 'DC' : 'CC');
            $bank['status'] = 1;
            $bank['sort'] = 1;
            $bank['created_at'] = $date;
            $data[] = $bank;
        }

        // 添加数据
        Db::table('bank')->insert($data);

        // 返回结果
        return true;
    }
}