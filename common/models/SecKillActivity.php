<?php

namespace common\models;

use framework\components\ToolsAbstract;
use service\components\Tools;
use Yii;
use framework\db\ActiveRecord;


/**
 * Class SecKillActivity
 * @package common\models
 * @property  integer $entity_id
 * @property  integer $city
 * @property  string $start_time
 * @property  string $end_time
 * @property  integer $status
 * @property  string $created_at
 * @property  string $updated_at
 */
class SecKillActivity extends ActiveRecord
{
    const EXPIRE_SECONDS = 7200;
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    const STATUS_UNPUSHED = 0;
    const STATUS_PUSHED = 1;

    /**
     * 1:已结束,2:已开抢，3：即将开抢，其他：保留
     */
    const INT_STATUS_END = 1;
    /**
     * 1:已结束,2:已开抢，3：即将开抢，其他：保留
     */
    const INT_STATUS_STARTED = 2;
    /**
     * 1:已结束,2:已开抢，3：即将开抢，其他：保留
     */
    const INT_STATUS_PREPARED = 3;

    private static $STATUS_MAP = [
        self::INT_STATUS_END => '已结束',
        self::INT_STATUS_STARTED => '已开始',
        self::INT_STATUS_PREPARED => '即将开抢',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'seckill_activity';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @param int $id
     * @param int $city
     * @param bool $cache 是否缓存，默认false
     * @return array|null
     */
    public static function getEnabledActivityByIdCity($id, $city, $cache = false)
    {
        /** @var array $activity */
        $activity = null;
        if ($cache) {
            $activity = self::getActivityByCache($city, $id);
        } else {
            $activity = self::find()->asArray(true)->where([
                'entity_id' => $id,
                'city' => $city
            ])->one();
        }

        if ($activity && $activity['status'] == self::STATUS_ENABLED) {
            return $activity;
        }
        return null;
    }

    /**
     * 获取该城市活跃（要展示）的活动。
     * 显示的是当前城市今天或者明天中按照开始时间排序结束时间大于现在时间（未结束）的已启用的活动
     * @param integer $city
     * @param bool $cache 是否缓存，默认false
     * @return array|null
     */
    public static function getCityActiveActivity($city, $cache = false)
    {
        $curDateTime = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
        $cacheKey = sprintf('sk_act_soon_%s_%s', $city, substr($curDateTime, 5, 5));
        if ($cache) {
            $actId = ToolsAbstract::getRedis()->get($cacheKey);
            if ($actId !== false) {
                return self::getActivityByCache($city, $actId);
            }
        }

        $tomorrowTimestamp = ToolsAbstract::getDate()->timestamp('tomorrow');
        $dateStart = ToolsAbstract::getDate()->date('Y-m-d 00:00:00');
        $dateEnd = date('Y-m-d 23:59:59', $tomorrowTimestamp);

        $activity = self::find()->asArray(true)->where([
            'city' => $city,
            'status' => self::STATUS_ENABLED
        ])->andWhere(['>=', 'start_time', $dateStart])
            ->andWhere(['<=', 'start_time', $dateEnd])
            ->andWhere(['>=', 'end_time', $dateStart])
            ->andWhere(['<=', 'end_time', $dateEnd])
            ->andWhere(['>=', 'end_time', $curDateTime])
            ->orderBy('start_time ASC')->one();

        if ($cache) {
            $expiredTime = $activity['end_time'];
            ToolsAbstract::getRedis()->set($cacheKey, $activity['entity_id'], self::getCacheExpireSeconds($expiredTime));
            self::addToActivityCacheKey($city, $cacheKey);
        }
        return $activity;
    }

    /**
     * 获取要推送的活动
     */
    public static function getPushActivities()
    {
        $now = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
        //Tools::log('$now===========','hl.log');
        //Tools::log($now,'hl.log');
        //5分钟后
        $curDateTime = date('Y-m-d H:i:s', strtotime($now) + 5 * 60);
        Tools::log('$curDateTime===========', 'hl.log');
        Tools::log($curDateTime, 'hl.log');

        $result = self::find()->where([
            'has_pushed' => self::STATUS_UNPUSHED,
            'status' => self::STATUS_ENABLED
        ])->andWhere(['<=', 'start_time', $curDateTime])
            ->andWhere(['>', 'start_time', $now])
            ->asArray()
            ->all();

        return $result;
    }


    /**
     * 获取该城市最近的活动列表（现在获取的是昨天、今天、明天的）
     *
     * @param integer $city
     * @param bool $cache 是否缓存，默认false
     * @return array|null
     */
    public static function getCityNearList($city, $cache = false)
    {
        $curDateTime = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
        $cacheKey = sprintf('sk_act_near_%s_%s', $city, substr($curDateTime, 5, 5));
        if ($cache) {
            $actIds = ToolsAbstract::getRedis()->get($cacheKey);
            if ($actIds !== false) {
                return self::getActivityByCache($city, json_decode($actIds, 1));
            }
        }

        /* 获取昨天、今天和明天启用的活动 */
        $yesterdayTimestamp = ToolsAbstract::getDate()->timestamp('yesterday');
        $tomorrowTimestamp = ToolsAbstract::getDate()->timestamp('tomorrow');
        $dateStart = date('Y-m-d 00:00:00', $yesterdayTimestamp);
        $dateEnd = date('Y-m-d 23:59:59', $tomorrowTimestamp);

        $activities = self::find()->asArray(true)->where([
            'city' => $city,
            'status' => self::STATUS_ENABLED
        ])->andWhere(['>=', 'start_time', $dateStart])
            ->andWhere(['<=', 'start_time', $dateEnd])
            ->andWhere(['>=', 'end_time', $dateStart])
            ->andWhere(['<=', 'end_time', $dateEnd])
            ->orderBy('start_time ASC')->all();

        if ($cache) {
            $cacheContent = json_encode(array_column($activities, 'entity_id'));
            ToolsAbstract::getRedis()->set($cacheKey, $cacheContent, self::getCacheExpireSeconds());
            self::addToActivityCacheKey($city, $cacheKey);
        }

        return $activities;
    }

    /**
     *
     * @param integer $city
     * @param bool $cache 是否缓存，默认false
     * @return array|null
     */
    public static function getCityCurActivity($city, $cache = false)
    {
        $curDateTime = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
        $cacheKey = sprintf('sk_act_cur_%s_%s', $city, substr($curDateTime, 5, 5));
        if ($cache) {
            $actId = ToolsAbstract::getRedis()->get($cacheKey);
            if ($actId !== false) {
                return self::getActivityByCache($city, $actId);
            }
        }

        /* 先判断活动是否存在 */
        /** @var array $activity */
        $activity = SecKillActivity::find()->asArray(true)->where([
            'city' => $city,
            'status' => SecKillActivity::STATUS_ENABLED,
        ])->andWhere(['<=', 'start_time', $curDateTime])
            ->andWhere(['>=', 'end_time', $curDateTime])
            ->one();

        if ($cache) {
            if ($activity) {
                $ttl = self::getCacheExpireSeconds($activity['end_time']);
                ToolsAbstract::getRedis()->set($cacheKey, $activity['entity_id'], $ttl);
            } else {
                $nextActivity = self::getCityActiveActivity($city, $cache);
                $ttl = $nextActivity ? self::getCacheExpireSeconds($nextActivity['start_time']) : self::EXPIRE_SECONDS;
                ToolsAbstract::getRedis()->set($cacheKey, '', $ttl < 0 ? -1 : $ttl);
            }
            self::addToActivityCacheKey($city, $cacheKey);
        }

        return $activity;
    }

    /**
     * 根据活动信息获取状态值
     * 返回数字。1:已结束,2:已开抢，3：即将开抢，其他：保留
     * @param SecKillActivity $activity
     * @return integer
     */
    public static function getStatusInfo($activity)
    {
        $curDateStart = ToolsAbstract::getDate()->date('Y-m-d 00:00:00');
        $curDateEnd = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $curDateTime = date('Y-m-d H:i:s', $curTimestamp);

        if ($activity['start_time'] < $curDateStart) { // 昨天
            return self::INT_STATUS_END;
        } elseif ($activity['start_time'] > $curDateEnd) {  // 明天
            return self::INT_STATUS_PREPARED;
        } else {    // 今天
            if ($activity['start_time'] <= $curDateTime && $activity['end_time'] >= $curDateTime) {
                return self::INT_STATUS_STARTED;
            } else if ($activity['end_time'] < $curDateTime) {
                return self::INT_STATUS_END;
            } else {
                return self::INT_STATUS_PREPARED;
            }
        }
    }

    /**
     * @param int $status
     * @return mixed|null
     */
    public static function getStatusStr($status)
    {
        return isset(self::$STATUS_MAP[$status]) ? self::$STATUS_MAP[$status] : null;
    }

    /**
     * 获取剩余时间。已开始状态返回距离结束的剩余时间；未开始状态返回距离开始的时间；结束状态返回0
     *
     * @param SecKillActivity $activity
     * @param int $status
     * @return int
     */
    public static function getLeftTime($activity, $status = null)
    {
        if ($status == null) {
            $status = self::getStatusInfo($activity);
        }

        if ($status === self::INT_STATUS_END) {
            return 0;
        } elseif ($status === self::INT_STATUS_STARTED) {
            $curTimestamp = ToolsAbstract::getDate()->timestamp();
            return (strtotime($activity['end_time']) - $curTimestamp);
        } elseif ($status === self::INT_STATUS_PREPARED) {
            $curTimestamp = ToolsAbstract::getDate()->timestamp();
            return (strtotime($activity['start_time']) - $curTimestamp);
        }
    }

    /**
     * @param int $city
     * @param int[] $actIds
     * @return array|mixed|null
     */
    public static function getActivityByCache($city, $actIds = null)
    {
        $cacheKey = sprintf('sk_act_list_%s', $city);
        $queryActIds = $actIds;
        if (is_scalar($actIds) && !is_numeric($actIds)) {
            return null;
        }

        if (is_numeric($actIds)) {
            $queryActIds = [$actIds];
        }

        if ($queryActIds) {
            $cacheResult = ToolsAbstract::getRedis()->hMGet($cacheKey, $queryActIds);
        } else {
            $cacheResult = ToolsAbstract::getRedis()->hGetAll($cacheKey);
        }

        if ($cacheResult === false) {
            return null;
        }

        $ret = [];
        foreach ($cacheResult as $item) {
            $ret[] = json_decode($item, 1);
        }

        if (is_numeric($actIds)) {
            return isset($ret[0]) ? $ret[0] : null;
        }

        return $ret;
    }

    /**
     *
     * @param array $activity
     */
    public static function setActivityCache(array $activity)
    {
        $cacheKey = sprintf('sk_act_list_%s', $activity['city']);
        $ttl = $activity ? self::getCacheExpireSeconds($activity['end_time']) : self::EXPIRE_SECONDS;
        ToolsAbstract::getRedis()->hSet($cacheKey, serialize($activity), $ttl);
    }

    /**
     * 添加到活动缓存键集，可以用来清空缓存
     *
     * @param int $city
     * @param string $key
     */
    private static function addToActivityCacheKey($city, $key)
    {
        $cacheKey = sprintf('sk_act_keys_%s', $city);
        ToolsAbstract::getRedis()->sAdd($cacheKey, $key);
    }

    /**
     * 获取缓存过期时间
     * @param int|string|null $expireTimestamp
     * @return int
     */
    private static function getCacheExpireSeconds($expireTimestamp = null)
    {
        if ($expireTimestamp == null) {
            $expireTimestamp = strtotime(ToolsAbstract::getDate()->date('Y-m-d 23:59:59'));
        } else if (!is_numeric($expireTimestamp)) {
            $expireTimestamp = strtotime($expireTimestamp);
        }

        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        return ($expireTimestamp > $curTimestamp + 10) ? $expireTimestamp - $curTimestamp - 10 : -1;
    }
}
