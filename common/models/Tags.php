<?php

namespace common\models;

use service\components\Tools;
use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "tags_city_440300".
 *
 * @property integer $entity_id
 * @property integer $wholesaler_id
 * @property integer $product_id
 * @property string $text
 * @property string $color
 */
class Tags extends ActiveRecord
{

    protected static $cityId;

	static $ICON_CU = 'http://assets.lelai.com/assets/secimgs/cu.png?v=2.6';
	static $ICON_FAN = 'http://assets.lelai.com/assets/secimgs/fan.png?v=2.6';
	static $ICON_ZENG = 'http://assets.lelai.com/assets/secimgs/zeng.png?v=2.6';
	static $ICON_PEI = 'http://assets.lelai.com/assets/secimgs/pei.png?v=2.6';
	static $ICON_JIAN = 'http://assets.lelai.com/assets/secimgs/jian.png?v=2.6';
	static $ICON_ZHE = 'http://assets.lelai.com/assets/secimgs/zhe.png?v=2.6';
	static $ICON_QUAN = 'http://assets.lelai.com/assets/secimgs/quan.png?v=2.6';
	static $ICON_JIAN_TEXT = '满减';
    static $ICON_ZHE_TEXT = '满折';
    static $ICON_ZENG_TEXT = '满赠';
	static $ICON_CU_TEXT = '促销';
	static $ICON_FAN_TEXT = '返点';


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
        return 'tags_city_' . self::$cityId;
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('productDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wholesaler_id', 'product_id'], 'required'],
            [['wholesaler_id', 'product_id'], 'integer'],
            [['text'], 'string', 'max' => 20],
            [['color'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'wholesaler_id' => '供应商id',
            'product_id' => '商品id',
            'text' => '显示字符',
            'color' => '颜色，如#ffffff',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(new Products(self::$cityId), ['entity_id' => 'product_id']);
    }

    public static function getTags($city=0, $productId=0){
        if(!$city||!$productId){
            return false;
        }

        $model = new Tags($city);
        $res = $model->find()->where([
            'product_id'=>$productId,
        ])->all();

        $result = array();
        foreach ($res as $re) {
            $data = $re->getAttributes();
            $color = Tools::isColor($data['color'])?$data['color']:'000000';
            $in = [
                'short'=>$data['short'],
                'text'=>$data['text'],
                'color'=>$color,
                'icon'=>$data['icon'],
            ];
            array_push($result, array_filter($in));
        }
        return $result;
    }
}
