<?php
declare(strict_types=1);

namespace App\Listener;

use Exception;
use Minimal\Facades\Db;
use Minimal\Facades\Log;
use Minimal\Facades\Cache;
use Minimal\Contracts\Listener;

/**
 * 地区事件
 */
class Region implements Listener
{
    /**
     * 监听事件
     */
    public function events() : array
    {
        return [
            'Region:OnDivide',
        ];
    }

    /**
     * 事件处理
     */
    public function handle(string $event, array $arguments = []) : bool
    {
        // 行政划分
        if ($event == 'Region:OnDivide') {
            return $this->divide();
        }

        // 返回结果
        return true;
    }

    /**
     * 行政划分
     *
     * 国家数据
     * https://reg.taobao.com/member/reg/fast/union_reg?_regfrom=TB
     * 在控制台输出：JSON.stringify(countryList.map(o => {return {name:o.areaName,code:o.code}}));
     *
     * 省市区数据
     * https://github.com/modood/Administrative-divisions-of-China
     * https://raw.githubusercontent.com/modood/Administrative-divisions-of-China/master/dist/pcas-code.json
     */
    public function divide() : bool
    {
        // 国家数据
        $countryList = json_decode("[{\"name\":\"中国大陆\",\"code\":\"86\"},{\"name\":\"中国台湾\",\"code\":\"886\"},{\"name\":\"中国香港\",\"code\":\"852\"},{\"name\":\"马来西亚\",\"code\":\"60\"},{\"name\":\"新加坡\",\"code\":\"65\"},{\"name\":\"日本\",\"code\":\"81\"},{\"name\":\"韩国\",\"code\":\"82\"},{\"name\":\"美国\",\"code\":\"1\"},{\"name\":\"加拿大\",\"code\":\"1\"},{\"name\":\"澳大利亚\",\"code\":\"61\"},{\"name\":\"新西兰\",\"code\":\"64\"},{\"name\":\"中国澳门\",\"code\":\"853\"},{\"name\":\"丹麦\",\"code\":\"45\"},{\"name\":\"乌克兰\",\"code\":\"380\"},{\"name\":\"乌兹别克斯坦\",\"code\":\"998\"},{\"name\":\"乌干达\",\"code\":\"256\"},{\"name\":\"乌拉圭\",\"code\":\"598\"},{\"name\":\"乍得\",\"code\":\"235\"},{\"name\":\"也门\",\"code\":\"967\"},{\"name\":\"以色列\",\"code\":\"972\"},{\"name\":\"伯利兹\",\"code\":\"501\"},{\"name\":\"佛得角\",\"code\":\"238\"},{\"name\":\"俄罗斯\",\"code\":\"7\"},{\"name\":\"保加利亚\",\"code\":\"359\"},{\"name\":\"克罗地亚\",\"code\":\"385\"},{\"name\":\"冈比亚\",\"code\":\"220\"},{\"name\":\"几内亚\",\"code\":\"224\"},{\"name\":\"几内亚比绍\",\"code\":\"245\"},{\"name\":\"加蓬\",\"code\":\"241\"},{\"name\":\"匈牙利\",\"code\":\"36\"},{\"name\":\"南非\",\"code\":\"27\"},{\"name\":\"卡塔尔\",\"code\":\"974\"},{\"name\":\"卢旺达\",\"code\":\"250\"},{\"name\":\"卢森堡\",\"code\":\"352\"},{\"name\":\"印度\",\"code\":\"91\"},{\"name\":\"印度尼西亚\",\"code\":\"62\"},{\"name\":\"危地马拉\",\"code\":\"502\"},{\"name\":\"吉尔吉斯斯坦\",\"code\":\"996\"},{\"name\":\"吉布提\",\"code\":\"253\"},{\"name\":\"哥伦比亚\",\"code\":\"57\"},{\"name\":\"哥斯达黎加\",\"code\":\"506\"},{\"name\":\"喀麦隆\",\"code\":\"237\"},{\"name\":\"土库曼斯坦\",\"code\":\"993\"},{\"name\":\"土耳其\",\"code\":\"90\"},{\"name\":\"圭亚那\",\"code\":\"592\"},{\"name\":\"坦桑尼亚\",\"code\":\"255\"},{\"name\":\"埃及\",\"code\":\"20\"},{\"name\":\"塔吉克斯坦\",\"code\":\"992\"},{\"name\":\"塞内加尔\",\"code\":\"221\"},{\"name\":\"塞尔维亚\",\"code\":\"381\"},{\"name\":\"塞拉利昂\",\"code\":\"232\"},{\"name\":\"塞浦路斯\",\"code\":\"357\"},{\"name\":\"塞舌尔\",\"code\":\"248\"},{\"name\":\"墨西哥\",\"code\":\"52\"},{\"name\":\"多哥\",\"code\":\"228\"},{\"name\":\"奥地利\",\"code\":\"43\"},{\"name\":\"委内瑞拉\",\"code\":\"58\"},{\"name\":\"安哥拉\",\"code\":\"244\"},{\"name\":\"尼加拉瓜\",\"code\":\"505\"},{\"name\":\"尼日利亚\",\"code\":\"234\"},{\"name\":\"尼日尔\",\"code\":\"227\"},{\"name\":\"巴勒斯坦\",\"code\":\"970\"},{\"name\":\"巴哈马\",\"code\":\"1242\"},{\"name\":\"巴布亚新几内亚\",\"code\":\"675\"},{\"name\":\"巴拿马\",\"code\":\"507\"},{\"name\":\"巴林\",\"code\":\"973\"},{\"name\":\"巴西\",\"code\":\"55\"},{\"name\":\"布基纳法索\",\"code\":\"226\"},{\"name\":\"希腊\",\"code\":\"30\"},{\"name\":\"开曼群岛\",\"code\":\"1345\"},{\"name\":\"德国\",\"code\":\"49\"},{\"name\":\"意大利\",\"code\":\"39\"},{\"name\":\"拉脱维亚\",\"code\":\"371\"},{\"name\":\"挪威\",\"code\":\"47\"},{\"name\":\"摩尔多瓦\",\"code\":\"373\"},{\"name\":\"摩洛哥\",\"code\":\"212\"},{\"name\":\"文莱\",\"code\":\"673\"},{\"name\":\"斯威士兰\",\"code\":\"268\"},{\"name\":\"斯洛伐克\",\"code\":\"421\"},{\"name\":\"斯洛文尼亚\",\"code\":\"386\"},{\"name\":\"斯里兰卡\",\"code\":\"94\"},{\"name\":\"智利\",\"code\":\"56\"},{\"name\":\"柬埔寨\",\"code\":\"855\"},{\"name\":\"格林纳达\",\"code\":\"1473\"},{\"name\":\"格鲁吉亚\",\"code\":\"995\"},{\"name\":\"比利时\",\"code\":\"32\"},{\"name\":\"毛里塔尼亚\",\"code\":\"222\"},{\"name\":\"毛里求斯\",\"code\":\"230\"},{\"name\":\"沙特阿拉伯\",\"code\":\"966\"},{\"name\":\"法国\",\"code\":\"33\"},{\"name\":\"波兰\",\"code\":\"48\"},{\"name\":\"泰国\",\"code\":\"66\"},{\"name\":\"津巴布韦\",\"code\":\"263\"},{\"name\":\"洪都拉斯\",\"code\":\"504\"},{\"name\":\"爱尔兰\",\"code\":\"353\"},{\"name\":\"爱沙尼亚\",\"code\":\"372\"},{\"name\":\"牙买加\",\"code\":\"1876\"},{\"name\":\"特立尼达和多巴哥\",\"code\":\"1868\"},{\"name\":\"玻利维亚\",\"code\":\"591\"},{\"name\":\"瑞典\",\"code\":\"46\"},{\"name\":\"瑞士\",\"code\":\"41\"},{\"name\":\"白俄罗斯\",\"code\":\"375\"},{\"name\":\"科威特\",\"code\":\"965\"},{\"name\":\"科摩罗\",\"code\":\"269\"},{\"name\":\"秘鲁\",\"code\":\"51\"},{\"name\":\"突尼斯\",\"code\":\"216\"},{\"name\":\"立陶宛\",\"code\":\"370\"},{\"name\":\"约旦\",\"code\":\"962\"},{\"name\":\"纳米比亚\",\"code\":\"264\"},{\"name\":\"罗马尼亚\",\"code\":\"40\"},{\"name\":\"肯尼亚\",\"code\":\"254\"},{\"name\":\"芬兰\",\"code\":\"358\"},{\"name\":\"苏里南\",\"code\":\"597\"},{\"name\":\"英国\",\"code\":\"44\"},{\"name\":\"英属维尔京群岛\",\"code\":\"1284\"},{\"name\":\"荷兰\",\"code\":\"31\"},{\"name\":\"莫桑比克\",\"code\":\"258\"},{\"name\":\"莱索托\",\"code\":\"266\"},{\"name\":\"菲律宾\",\"code\":\"63\"},{\"name\":\"萨尔瓦多\",\"code\":\"503\"},{\"name\":\"葡萄牙\",\"code\":\"351\"},{\"name\":\"蒙古\",\"code\":\"976\"},{\"name\":\"西班牙\",\"code\":\"34\"},{\"name\":\"贝宁\",\"code\":\"229\"},{\"name\":\"赞比亚\",\"code\":\"260\"},{\"name\":\"赤道几内亚\",\"code\":\"240\"},{\"name\":\"越南\",\"code\":\"84\"},{\"name\":\"阿塞拜疆\",\"code\":\"994\"},{\"name\":\"阿尔巴尼亚\",\"code\":\"355\"},{\"name\":\"阿曼\",\"code\":\"968\"},{\"name\":\"阿根廷\",\"code\":\"54\"},{\"name\":\"阿联酋\",\"code\":\"971\"},{\"name\":\"马尔代夫\",\"code\":\"960\"},{\"name\":\"马拉维\",\"code\":\"265\"},{\"name\":\"马达加斯加\",\"code\":\"261\"},{\"name\":\"马里\",\"code\":\"223\"}]", true);
        foreach ($countryList as $country) {
            $this->divideSave(1, $country['code'], $country['name']);
        }

        // 省级数据
        $json = file_get_contents(dirname(dirname(__DIR__)) . '/config/region.json');
        $provinces = json_decode($json, true);

        // 循环省级
        foreach ($provinces as $province) {
            // 保存省级
            $provinceItem = $this->divideSave(2, $province['code'], $province['name']);

            if (!empty($province['children'])) {
                // 循环市级
                foreach ($province['children'] as $city) {
                    // 保存市级
                    $cityItem = $this->divideSave(3, $city['code'], $city['name'], $provinceItem);

                    if (!empty($city['children'])) {
                        // 循环县级
                        foreach ($city['children'] as $county) {
                            // 保存县级
                            $countyItem = $this->divideSave(4, $county['code'], $county['name'], $cityItem);

                            if (!empty($county['children'])) {
                                // 循环镇级
                                foreach ($county['children'] as $town) {
                                    // 保存镇级
                                    $townItem = $this->divideSave(5, $town['code'], $town['name'], $countyItem);
                                }
                            }
                        }
                    }
                }
            }
        }

        // 返回结果
        return true;
    }

    /**
     * 行政划分 - 数据保存
     */
    public function divideSave(int $type, string $code, string $name, array $parent = [], array $extra = []) : array
    {
        $data = array_merge([
            'type'              =>  null,
            'zone'              =>  null,
            'zip'               =>  null,
            'country'           =>  '86',
            'country_name'      =>  '中国',
            'province'          =>  null,
            'province_name'     =>  null,
            'city'              =>  null,
            'city_name'         =>  null,
            'county'            =>  null,
            'county_name'       =>  null,
            'town'              =>  null,
            'town_name'         =>  null,
            'village'           =>  null,
            'village_type'      =>  null,
            'village_name'      =>  null,
            'address'           =>  null,
        ], $parent, $extra);
        $data['type'] = $type;
        $data['created_at'] = date('Y-m-d H:i:s');

        if ($type == 1) {
            $data['country'] = $code;
            $data['country_name'] = $name;
        } else if ($type == 2) {
            $data['province'] = $code;
            $data['province_name'] = $name;
        } else if ($type == 3) {
            $data['city'] = $code;
            $data['city_name'] = $name;
        } else if ($type == 4) {
            $data['county'] = $code;
            $data['county_name'] = $name;
        } else if ($type == 5) {
            $data['town'] = $code;
            $data['town_name'] = $name;
        } else if ($type == 6) {
            $data['village'] = $code;
            $data['village_type'] = $vtype;
            $data['village_name'] = $name;
        }
        $data['address'] = ($data['address'] ?? '') . $name;

        Db::table('region')->insert($data);

        return $data;
    }

}