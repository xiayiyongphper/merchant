<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/9/6
 * Time: 12:13
 */

namespace common\models;


use framework\components\ToolsAbstract;
use framework\db\ActiveRecord;

/**
 * Class SearchTraceRecord
 * @package common\models
 * @property int $entity_id int(10) unsigned NOT NULL AUTO_INCREMENT,
 * @property int $customer_id int(10) unsigned NOT NULL,
 * @property string $search_id varchar(32) NOT NULL,
 * @property int $type tinyint(4) NOT NULL COMMENT '类型。1：搜索前，2：搜索关键词输入，3：搜索结果，4：商品详情，5：购物车',
 * @property string $keyword varchar(255) DEFAULT NULL COMMENT '关键词',
 * @property int $page tinyint(1) DEFAULT NULL COMMENT '分页页数',
 * @property string $ids varchar(255) DEFAULT NULL COMMENT '商品id，多个,分隔',
 * @property string $created_at date NOT NULL,
 */
class SearchTraceRecord extends ActiveRecord
{
    const TYPE_BEFORE_SEARCH = 1;
    const TYPE_SEARCH = 2;
    const TYPE_SEARCH_RESULT = 3;
    const TYPE_PRODUCT_DETAIL = 4;
    const TYPE_CART = 5;

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = ToolsAbstract::getDate()->date();
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'search_trace_record';
    }

    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}