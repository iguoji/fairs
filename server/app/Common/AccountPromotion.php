<?php
declare(strict_types=1);

namespace App\Common;

use Minimal\Facades\Db;
use Minimal\Foundation\Exception;

/**
 * 账户推广
 */
class AccountPromotion
{
    /**
     * 最多几层关系
     */
    const MAX = 5;

    /**
     * 字段列表
     */
    const FIELDS = ['one', 'two', 'three', 'four', 'five'];

    /**
     * 所有数据
     */
    public static function all(array $params) : array
    {
        var_dump($params);
        // 查询对象
        $query = Db::table('account', 'a')
            ->leftJoin('account_promotion', 'ap', 'ap.uid', 'a.uid');

        // 条件：按账号关键字查询
        if (isset($params['keyword'])) {
            $query->orWhere(function($query1) use($params){
                $query1->orWhere('a.username', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.phone', 'like', '%' . $params['keyword'] . '%')
                    ->orWhere('a.email', 'like', '%' . $params['keyword'] . '%');
            });
        }
        // 条件：按上级查询
        if (isset($params['inviter'])) {
            $query->where('a.inviter', $params['inviter']);
        }
        // 条件：有推广数据的人
        if (isset($params['notempty'])) {
            $query->where(function($query1) {
                foreach (static::FIELDS as $key => $field) {
                    $query1->orWhere('ap.' . $field, '!=', 0);
                }
            });
        }
        // 条件：已删除
        $query->where('a.deleted_at');


        // 查询总数
        $total = (clone $query)->count('a.id');
        // 数据排序
        $fields = [];
        foreach (static::FIELDS as $key => $field) {
            $fields[] = 'ap.' . $field;
            $query->orderByDesc('ap.' . $field);
        }
        // 查询数据
        $data = $query->orderByDesc('a.id')
            ->page($params['pageNo'] ?? 1, $params['pageSize'] ?? 20)
            ->all('a.*', ...$fields);
        echo Db::lastSql(), PHP_EOL;
        // 循环处理
        foreach ($data as $key => $value) {
            // 保存
            $data[$key] = $value;
        }

        // 返回结果
        return [$data, $total];
    }

    /**
     * 递归获取所有的上级列表
     * [上级、上上级、上上上级、……]
     */
    public static function getParents(string $uid, int $max = null) : array
    {
        $array = [];
        $inviter = Db::table('account')->where('uid', $uid)->value('inviter');
        if (!empty($inviter)) {
            $array[] = $inviter;
            if (is_null($max) || $max > count($array)) {
                array_push($array, ...static::getParents($inviter));
            }
        }
        return $array;
    }

    /**
     * 获取一级下级列表
     */
    public static function getChilds(string|array $uid, array $excepts = []) : array
    {
        // 查询对象
        $query = Db::table('account');
        // 条件：排除指定账号
        if (!empty($excepts)) {
            $query->where('uid', 'not in', $excepts);
        }
        // 返回结果
        return $query->where('inviter', 'in', is_array($uid) ? $uid : [$uid])->column('uid');
    }

    /**
     * 获取一级下级数量
     */
    public static function getChildsCount(string|array $uid, array $excepts = []) : int|array
    {
        // 查询对象
        $query = Db::table('account', 'a')
            ->leftJoin('account', 'a1', 'a1.inviter', 'a.uid')
            ->groupBy('a.uid')
            ->where('a.uid', 'in', is_array($uid) ? $uid : [$uid])
            ->where('a1.deleted_at');
        // 条件：排除指定下级
        if (!empty($excepts)) {
            $query->where('a1.uid', 'not in', $excepts);
        }
        // 查询数据
        $data = $query->all('a.uid', Db::raw('COUNT(a1.uid) AS `count`'));

        // 返回结果
        return is_array($uid)
            ? array_column($data, 'count', 'uid')
            : ($data[0]['count'] ?? 0);
    }

    /**
     * 更改上级
     */
    public static function change(string $uid, string $newInviter) : bool
    {
        // 1. 找到老的所有上级
        $oldParents = static::getParents($uid, static::MAX);
        // 2. 找到新的所有上级
        $newParents = [$newInviter, ...static::getParents($newInviter, static::MAX - 1)];

        // 3. 老上级依次重新计算并更改
        foreach ($oldParents as $key => $inviter) {
            // 4. 计算下级数量
            $childCount = static::compute($inviter, excepts: [$uid]);
            // 5. 更改下级数量
            if (static::has($inviter)) {
                $bool = static::upd($inviter, array_combine(static::FIELDS, $childCount));
            } else {
                $data = array_combine(static::FIELDS, $childCount);
                $data['uid'] = $inviter;
                $bool = static::add($data);
            }
            if (!$bool) {
                throw new Exception('很抱歉、更新下级数量失败！');
            }
        }

        // 6. 计算自身下级数量
        $myChildCount = static::compute($uid);

        // 7. 新上级依次重新计算并更改
        foreach ($newParents as $key => $inviter) {
            // 8. 计算下级数量
            $childCount = static::compute($inviter, excepts: [$uid]);
            // 9. 合并我的下级数量
            array_unshift($myChildCount, 0 === $key ? 1 : 0);
            for ($i = 0;$i < count($childCount) && $i < count($myChildCount);$i++) {
                $childCount[$i] += $myChildCount[$i];
            }
            // 10. 更改下级数量
            if (static::has($inviter)) {
                $bool = static::upd($inviter, array_combine(static::FIELDS, $childCount));
            } else {
                $data = array_combine(static::FIELDS, $childCount);
                $data['uid'] = $inviter;
                $bool = static::add($data);
            }
            if (!$bool) {
                throw new Exception('很抱歉、更新下级数量失败！');
            }
        }

        // 返回结果
        return true;
    }

    /**
     * 计算指定用户的下级数量
     */
    public static function compute(string $uid, array $excepts = []) : array
    {
        // 下级数量列表，从左至右为一级、二级、三级、四级、五级等
        $dashboard = [];

        // 依次获取下级数量
        $uidList = [$uid];
        for ($i = 0;$i < static::MAX; $i++) {
            // 查询下级数量
            $childCount = static::getChildsCount($uidList, $excepts);
            // 保存下级数量
            $dashboard[] = array_sum(array_values($childCount));
            // 查询下级列表
            $uidList = static::getChilds($uidList, $excepts);
            if (empty($uidList)) {
                // 查询结束，补充数组数量
                $dashboard = array_pad($dashboard, static::MAX, 0);
                break;
            }
        }

        // 返回结果
        return $dashboard;
    }

    /**
     * 获取数据
     */
    public static function get(string $uid) : array
    {
        return Db::table('account_promotion')->where('uid', $uid)->first();
    }

    /**
     * 指定数据是否存在
     */
    public static function has(string $uid) : bool
    {
        return !empty(static::get($uid));
    }

    /**
     * 添加数据
     */
    public static function add(array $data) : bool
    {
        // 补充时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        // 添加数据
        return Db::table('account_promotion')->insert($data) > 0;
    }

    /**
     * 修改数据
     */
    public static function upd(string $uid, array $data) : bool
    {
        // 补充时间
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // 添加数据
        return Db::table('account_promotion')->where('uid', $uid)->update($data) > 0;
    }
}