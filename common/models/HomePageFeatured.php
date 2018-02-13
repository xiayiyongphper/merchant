<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "products_city_%".
 *
 * @property string $entity_id
 * @property integer $wholesaler_id
 * @property string $name
 * @property string $lsin
 * @property string $price
 * @property integer $sold_qty
 * @property integer $real_sold_qty
 * @property string $barcode
 * @property integer $first_category_id
 * @property integer $second_category_id
 * @property integer $third_category_id
 * @property string $specification
 * @property string $description
 * @property string $gallery
 * @property integer $status
 * @property string $special_price
 * @property string $special_from_date
 * @property string $special_to_date
 * @property string $created_at
 * @property string $updated_at
 * @property integer $inventory_qty
 * @property integer $sort_weights
 * @property string $brand
 *
 */
class HomePageFeatured extends ActiveRecord
{

    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return 'catalog_featured_page';
    }
 
   
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['area', 'content'], 'required'],
        ];
    }
}
