<?php

namespace common\models;

use service\components\search\Search;
use service\components\Tools;
use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "products_city_440300".
 *
 * @property integer $entity_id
 * @property integer $wholesaler_id
 * @property string $lsin
 * @property string $barcode
 * @property integer $first_category_id
 * @property integer $second_category_id
 * @property integer $third_category_id
 * @property string $name
 * @property string $price
 * @property string $special_price
 * @property string $special_from_date
 * @property string $special_to_date
 * @property integer $sold_qty
 * @property integer $real_sold_qty
 * @property integer $qty
 * @property integer $minimum_order
 * @property string $gallery
 * @property string $brand
 * @property integer $export
 * @property string $origin
 * @property integer $package_num
 * @property string $package_spe
 * @property string $package
 * @property string $specification
 * @property string $shelf_life
 * @property string $description
 * @property integer $status
 * @property integer $sort_weights
 * @property string $shelf_time
 * @property string $created_at
 * @property string $updated_at
 * @property integer $state
 *
 */
class ProductView extends ActiveRecord
{

    protected static $cityId;

    /**
     * @param int $city_id
     * @throws \Exception
     */
    public function __construct($city_id = 0)
    {
        if ($city_id > 0) {
            self::$cityId = $city_id;
        } else {
            Yii::trace('城市ID找不到');
        }
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'products_city_' . self::$cityId.'_view';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('productDb');
    }

}
