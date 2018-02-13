<?php

/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/10/13
 * Time: 16:24
 */

namespace common\models;

use framework\components\ToolsAbstract;
use framework\db\ActiveRecord;

/**
 * Class LeMerchantTriggerMsg
 * @package common\models
 * @property integer $entity_id
 * @property int $trigger_type 操作触发类型。0：未知。1：注册，2：登录，3：首页，4：订单创建，5：订单确认收货，6：订单评价
 * @property int $custumer_id
 * @property string $result
 * @property int $type 1：零钱，2：优惠券，其他保留
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 */
class LeMerchantTriggerMsg extends ActiveRecord
{
    const STATUS_UNREAD = 1;
    const STATUS_READ = 2;

    const TYPE_BALANCE = 1;
    const TYPE_COUPON = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_trigger_msg';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    public function afterFind()
    {
        $this->result = json_decode($this->result, 1);
        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!is_scalar($this->result)) {
            $this->result = json_encode($this->result);
        }

        $curDateTime = ToolsAbstract::getDate()->date();
        if ($insert) {
            $this->created_at = $curDateTime;
        }
        $this->updated_at = $curDateTime;

        return parent::beforeSave($insert);
    }
}