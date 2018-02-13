<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/6/16
 * Time: 11:22
 */

namespace service\resources\merchant\v1;

use common\models\CumulativeReturnActivity;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\merchant\SecKillActivityRequest;
use service\message\merchant\SecKillActivityResponse;
use service\resources\MerchantResourceAbstract;

/**
 * 活动专区->活动列表接口
 *
 * Class GetSecKillActivity
 * @package service\resources\merchant\v1
 */
class GetSecKillActivity extends MerchantResourceAbstract
{
    /**
     * @param string $data
     * @return SecKillActivityResponse
     */
    public function run($data)
    {
        /** @var SecKillActivityRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        // 初始化
        $customer = $this->_initCustomer($request);
        if (!$customer->getCity()) {
            return null;
        }

        // 昨天、今天、明天的启用活动
        $activityList = SecKillActivity::getCityNearList($customer->getCity(), SeckillHelper::IS_CACHE);
        if (!$activityList) {
            return null;
        }

        /* 整理数据，昨天的要两个，今天的要全部，明天的要2个 */
        $curDateStart = ToolsAbstract::getDate()->date('Y-m-d 00:00:00');
        $curDateEnd = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $curDateTime = date('Y-m-d H:i:s', $curTimestamp);
        $yesterdayActivities = [];
        $todayActivities = [];
        $tomorrowActivities = [];
        $seckillHelper = new SeckillHelper($customer);
        $flag1 = 0;
        $flag2 = null;

        foreach ($activityList as $k => $activity) {
            if (!$seckillHelper->getProducts($activity['entity_id'])) {
                continue;
            }
            /* status 1:已结束,2:已开抢，3：即将开抢，其他：保留 */
            if ($activity['start_time'] < $curDateStart) { // 昨天
                $status = 1;
                $statusStr = '已结束';
                $leftToEnd = 0;
                $this->addToActArray($yesterdayActivities, $activity, $status, $statusStr, $leftToEnd);
            } elseif ($activity['start_time'] > $curDateEnd) {  // 明天
                $status = 3;
                $statusStr = date('n月j日', strtotime($activity['start_time']));
                $leftToEnd = strtotime($activity['start_time']) - $curTimestamp;
                $this->addToActArray($tomorrowActivities, $activity, $status, $statusStr, $leftToEnd);
            } else { // 今天
                if ($activity['start_time'] <= $curDateTime && $activity['end_time'] >= $curDateTime) {
                    $status = 2;
                    $statusStr = '已开抢';
                    $leftToEnd = strtotime($activity['end_time']) - $curTimestamp;
                    $flag2 = $flag2 === null ? $flag1 : $flag2;
                } else if ($activity['end_time'] < $curDateTime) {
                    $status = 1;
                    $statusStr = '已结束';
                    $leftToEnd = 0;
                } else {
                    $status = 3;
                    $statusStr = '即将开抢';
                    $leftToEnd = strtotime($activity['start_time']) - $curTimestamp;
                    $flag2 = $flag2 === null ? $flag1 : $flag2;
                }
                $this->addToActArray($todayActivities, $activity, $status, $statusStr, $leftToEnd);
                $flag1++;
            }
        }

        $activeIndex = $flag2 ? $flag2 : 0;
        $num = 0;
        $activities = [];
        while (($activity = array_pop($yesterdayActivities)) && $num++ < 2) {
            $activeIndex++;
            array_unshift($activities, $activity);
        }

        $activities = array_merge($activities, $todayActivities);
        $num = 0;
        while (($activity = array_shift($tomorrowActivities)) && $num++ < 2) {
            array_push($activities, $activity);
        }

        $rulesText = ToolsAbstract::getRedis()->get('sk_rules_text');
        $rulesText = $rulesText === false ? '' : $rulesText;
        $respData['rules_text'] = $rulesText; // rules_text活动规则文本，如果设置了rules_url，忽略本字段。
        $respData['rules_url'] = '';
        $respData['activities'] = $activities;
        $respData['active_index'] = $activeIndex;

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * @param array $arr
     * @param array $activity
     */
    private function addToActArray(&$arr, $activity, $status, $statusStr, $leftToEnd)
    {
        array_push($arr, [
            'id' => $activity['entity_id'],
            'time' => substr($activity['start_time'], 11, 5),
            'status' => $status,
            'status_str' => $statusStr,
            'left_to_end' => $leftToEnd
        ]);
    }

    /**
     * @return SecKillActivityRequest
     */
    public static function request()
    {
        return new SecKillActivityRequest();
    }

    /**
     * @return SecKillActivityResponse
     */
    public static function response()
    {
        return new SecKillActivityResponse();
    }
}