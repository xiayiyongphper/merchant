<?php

namespace common\models;

use service\components\Tools;
use Yii;
use framework\db\ActiveRecord;


/**
 * Class SpecialProduct
 * @package common\models
 *
 * @property   string $entity_id
 * @property   string $product_id
 * @property   string $activity_id
 * @property   string $wholesaler_id
 * @property   string $lsin
 * @property   string $barcode
 * @property   string $first_category_id
 * @property   string $second_category_id
 * @property   string $third_category_id
 * @property   string $name
 * @property   string $promotion_text
 * @property   string $promotion_text_from
 * @property   string $promotion_text_to
 * @property   string $price
 * @property   string $seckill_price
 * @property   string $special_price
 * @property   string $special_from_date
 * @property   string $special_to_date
 * @property   string $rebates
 * @property   string $is_calculate_lelai_rebates
 * @property   string $rebates_lelai
 * @property   string $sold_qty
 * @property   string $fake_sold_qty
 * @property   string $real_sold_qty
 * @property   string $qty
 * @property   string $minimum_order
 * @property   string $gallery
 * @property   string $brand
 * @property   string $export
 * @property   string $origin
 * @property   string $package_num
 * @property   string $package_spe
 * @property   string $package
 * @property   string $specification
 * @property   string $shelf_life
 * @property   string $description
 * @property   string $status
 * @property   string $sort_weights
 * @property   string $shelf_time
 * @property   string $created_at
 * @property   string $updated_at
 * @property   string $state
 * @property   string $commission
 * @property   string $production_date
 * @property   string $restrict_daily
 * @property   string $subsidies_lelai
 * @property   string $subsidies_wholesaler
 * @property   string $label1
 * @property   string $promotion_title_from
 * @property   string $promotion_title_to
 * @property   string $promotion_title
 * @property   string $rule_id
 * @property   string $most_favorable_sort
 * @property   string $sales_attribute_name
 * @property   string $sales_attribute_value
 * @property   string $specification_unit
 * @property   string $specification_num
 * @property   string $lsin_barcode
 * @property   string $special_rebates_from
 * @property   string $special_rebates_lelai_from
 * @property   string $special_rebates_lelai_to
 * @property   string $special_rebates_lelai
 * @property   string $special_rebates_to
 * @property   string $special_rebates
 * @property   string $buy_limit
 * @property   integer $type
 * @property   integer $type2
 * @property   integer $ori_product_id
 *
 */
class SpecialProduct extends ActiveRecord
{
    /** 秒杀商品状态：1：已结束，2：马上抢（已开始而且有库存），3：已抢光（已开始但无库存），4：即将开始 **/
    const STATUS_END = 1;
    /** 秒杀商品状态：1：已结束，2：马上抢（已开始而且有库存），3：已抢光（已开始但无库存），4：即将开始 **/
    const STATUS_STARTED_HAS_STOCK = 2;
    /** 秒杀商品状态：1：已结束，2：马上抢（已开始而且有库存），3：已抢光（已开始但无库存），4：即将开始 **/
    const STATUS_STARTED_NO_STOCK = 3;
    /** 秒杀商品状态：1：已结束，2：马上抢（已开始而且有库存），3：已抢光（已开始但无库存），4：即将开始 **/
    const STATUS_PREPARED = 4;

    /** @var integer 商品状态 。1：可用。0：不用 */
    const STATUS_ENABLED = 1;
    /** @var integer 商品状态 。1：可用。0：不用 */
    const STATUS_DISABLED = 0;

    /** 掩码 **/
    const MASK = 0x80000000;
    //购物车秒杀商品前缀
    const SECKILL_KEY_PREFIX = 'seckill_cart_key';

    private static $STATUS_MAP = [
        self::STATUS_END => '已结束',
        self::STATUS_STARTED_HAS_STOCK => '马上抢',
        self::STATUS_STARTED_NO_STOCK => '已抢光',
        self::STATUS_PREPARED => '即将开始'
    ];

    /** @var int 普通商品，可以跟其他类型组合 */
    const TYPE_SIMPLE = 0x2000;
    /** @var int 秒杀商品，可以跟其他类型组合 */
    const TYPE_SECKILL = 0x4000;
    /** @var int 特殊商品，可以跟其他类型组合 */
    const TYPE_SPECIAL = 0x6000;
    /** @var int 套餐商品，可以跟其他类型组合 */
    const TYPE_GROUP = 0x4;
    /** @var int 套餐子商品，可以跟其他类型组合 */
    const TYPE_GROUP_SUB = 0x8;

    /** @var integer 商品id */
    public $product_id;
    /** @var integer 商品数量 */
    public $num;
    /** @var integer 剩余时间 */
    public $left_time;
    /** @var integer 购买数量 */
    public $purchased_qty;
    /** @var integer 是否选中 */
    public $selected;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'special_products';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @param integer $status
     * @return mixed|null
     */
    public static function getStatusStr($status)
    {
        return isset(self::$STATUS_MAP[$status]) ? self::$STATUS_MAP[$status] : null;
    }

    /**
     *
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->product_id = $this->entity_id;
        $this->sold_qty = (int)$this->real_sold_qty + (int)$this->fake_sold_qty;
    }

    /**
     * 是否是秒杀商品
     *
     * @param Products $product
     * @param string $idKey
     * @return bool
     */
    public static function isSecKillProduct($product, $idKey = 'entity_id')
    {
        if (!self::isSpecialProduct($product[$idKey])) {
            return false;
        }

        if (isset($product['type2']) && ((0xe000 & $product['type2']) == self::TYPE_SECKILL)) {
            return true;
        } elseif (isset($product['type']) && $product['type'] == 1) {  //只有type,旧值
            return true;
        } elseif (isset($product['type']) && ((0xe000 & $product['type']) == self::TYPE_SECKILL)) { //只有type,新值
            return true;
        }

        return false;
    }

    /**
     * 是否是秒杀商品
     *
     * @param $productId
     * @param $type
     * @return bool
     * @internal param Products $product
     * @internal param string $idKey
     */
    public static function isSecKillProductByIdType($productId, $type)
    {
        return self::isSpecialProduct($productId)
            && !empty($type) && ((0xe000 & $type) == self::TYPE_SECKILL);
    }

    public static function isSecKillProductByIdTypeOld($productId, $type)
    {
        return self::isSpecialProduct($productId) && $type == 1;
    }

    /**
     * 是否是特殊商品,特殊商品可能为：秒杀商品、特价活动商品
     * @param integer $productId
     * @return bool
     */
    public static function isSpecialProduct($productId)
    {
        return ($productId & self::MASK) ? true : false;
    }

    /**
     * Author Jason Y.Wang
     * 判断秒杀商品是否选中
     * @param $customer_id
     * @param $product_id
     * @return string
     */
    public static function getSecKillProductIsSelected($customer_id, $product_id)
    {
        $redis = Tools::getRedis();
        $result = $redis->hGet('sk_products_selected_' . $customer_id, $product_id);
        if ($result === false) {
            return 0;
        }

        if ($result > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Author Jason Y.Wang
     * 判断秒杀商品是否选中
     * @param $customer_id
     * @param $product_id
     * @param $selected
     * @return string
     */
    public static function setSecKillProductIsSelected($customer_id, $product_id, $selected)
    {
        $redis = Tools::getRedis();
        $redis->hSet('sk_products_selected_' . $customer_id, $product_id, $selected);
    }

}
