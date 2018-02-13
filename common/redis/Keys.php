<?php
namespace common\redis;
/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-30
 * Time: 上午11:33
 */
class Keys
{
	/**
	 *
	 * 获取用户每日钱包已使用额度的key
	 *
	 * @param $customerId
	 *
	 * @return string
	 */
	public static function getBalanceDailyLimitKey($customerId)
	{
		return 'balance_daily_limit_' . $customerId;
	}


	/**
	 * 获取每日限购的ｋｅｙ
	 * @param $customerId
	 * @param $city
	 * @return string
	 */
	public static function getDailyPurchaseHistory($customerId, $city)
	{
		return 'daily_purchase_history_' . $customerId . '_' . $city;
	}

	public static function getRedisESQueueKey()
	{
		return ENV_SYS_NAME . '_es_queue';
	}

    /**
     * 获取用户享受优惠活动次数key
     * @param $customerId
     * @param $ruleId
     * @return string
     */
    public static function getEnjoyTimesKey($customerId,$ruleId){
        return  'enjoy_times_key_'.$customerId."_".$ruleId;
    }
}