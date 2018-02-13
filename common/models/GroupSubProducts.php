<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 * Class SpecialProduct
 * @package common\models
 *
 * @property   int $group_product_id
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
 * @property   integer $ori_product_id
 * @property   integer $sub_product_num
 *
 */
class GroupSubProducts extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group_sub_products';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}