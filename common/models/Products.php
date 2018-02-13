<?php

namespace common\models;

use common\redis\Keys;
use framework\components\ToolsAbstract;
use service\components\search\Search;
use service\components\Tools;
use service\message\customer\CustomerResponse;
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
 * @property integer $restrict_daily
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
 * @property integer $fake_sold_qty
 * @property int $type
 *
 */
class Products extends ActiveRecord
{
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

    //状态:1：待审核，2：审核通过，3：审核不通过，4：系统下架
    const STATE_PENDING = 1;
    const STATE_APPROVED = 2;
    const STATE_DISAPPROVED = 3;
    const STATE_DISABLED = 4;
    //状态:1：上架，2：下架
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;
    protected static $cityId;
    /** @var  Search $searchModel */
    public $searchModel;

    public function search()
    {
        return $this->searchModel->search();
    }

    public function barcodeSearch()
    {
        return $this->searchModel->barcodeSearch();
    }

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
        return 'products_city_' . self::$cityId;
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
            [['wholesaler_id', 'lsin', 'barcode', 'first_category_id', 'second_category_id', 'third_category_id', 'name', 'price', 'special_price', 'sold_qty', 'real_sold_qty', 'qty', 'package_num', 'package_spe'], 'required'],

            [['price', 'special_price'], 'number'],
            [['special_from_date', 'special_to_date', 'shelf_time', 'created_at', 'updated_at'], 'safe'],
            [['gallery', 'specification', 'description'], 'string'],
            [['lsin'], 'string', 'max' => 32],
            [['barcode'], 'string', 'max' => 48],
            [['name'], 'string', 'max' => 128],
            [['brand'], 'string', 'max' => 100],
            [['origin'], 'string', 'max' => 60],
            [['package_spe'], 'string', 'max' => 10],
            [['package'], 'string', 'max' => 20],
            [['shelf_life'], 'string', 'max' => 50]
        ];
    }

    /**
     *
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->sold_qty = (int)$this->real_sold_qty + (int)$this->fake_sold_qty;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity id',
            'wholesaler_id' => '批发商',
            'lsin' => '平台唯一商品代码',
            'barcode' => '条码',
            'first_category_id' => '一级分类',
            'second_category_id' => '二级分类',
            'third_category_id' => '三级分类',
            'name' => '商品名称',
            'price' => '商品价格',
            'special_price' => '商品特价',
            'special_from_date' => '特价开始时间',
            'special_to_date' => '特价截止时间',
            'sold_qty' => '展示销量',
            'real_sold_qty' => '实际销量',
            'qty' => '库存数量',
            'gallery' => '商品相册',
            'brand' => '品牌',
            'export' => 'Export',
            'origin' => '产地',
            'package_num' => '打包内含物件数',
            'package_spe' => '包装的单件规格，如“瓶”',
            'package' => '包装',
            'specification' => '规格',
            'shelf_life' => '保质期',
            'description' => '描述',
            'status' => '状态:1：上架，2：下架',
            'sort_weights' => '权重',
            'shelf_time' => '上架时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'state' => '状态:1：待审核，2：审核通过，3：审核不通过，4：系统下架',
        ];
    }

    public static function convertArray($data, $isDetailPage = false)
    {
        $price = $data['price'];
        $finalPrice = Tools::getPrice($data);

        $discount = 1 - ($price - $finalPrice) / $price;
        $discount = $discount * 10;
        $discount = round($discount, 1);

        $name = self::getProductNameText($data);
        $specification = self::getProductSpecificationText($data);
        $gallery = explode(';', $data['gallery']);
        //$image = isset($gallery[0]) ? $gallery[0] : '';
        $_data = [
            "product_id" => $data['entity_id'],
            "name" => $name,
            "url" => '',
            "image" => Tools::getImage($data['gallery'], '388x388'),
            "price" => $finalPrice,
            "original_price" => Tools::formatPrice($price),
            'discount' => $discount,
            "sold" => $data['sold_qty'],
            'qty' => $data['qty'],
            'specification' => $specification,
            'wholesaler_id' => $data['wholesaler_id'],
            "wholesaler_name" => "",
            "wholesaler_url" => "",
        ];
        if ($isDetailPage) {
            $_data = array_merge($data, $_data);
        }

        return array_filter($_data);
    }

    /**
     * @param $data
     * Author Jason Y. wang
     * 标题促销语+空格+品牌+品名+空格+规格数字+规格单位+*+打包数量
     * 例如：热销 怡宝矿泉水 500ml*15
     *
     * @return string
     */
    static public function getProductNameText($data)
    {
        $name = '';
        // 标题促销语，需要判断生效时间
        if (!empty($data['promotion_title']) && Tools::dataInRange($data['promotion_title_from'], $data['promotion_title_to'])) {
            $name .= $data['promotion_title'];
        }
        // 品牌
        if (isset($data['brand'])) {
            $name .= $data['brand'];
        }
        // 商品名
        if (isset($data['name'])) {
            $name .= $data['name'];
        }
        // 规格信息
        $specification = self::getProductSpecificationText($data);
        //规则加在名称前面
        if ($specification) {
            $name = $specification . ' | ' . $name;
        }
        return $name;
    }

    /**
     * Author Jason Y.Wang
     * @param $data
     * @return mixed
     * 售卖单位
     */
    public static function getSaleUnit($data)
    {
        if ($data['package_num'] == 1) {
            return $data['package_spe'] ?: '个';
        } else {
            return $data['package'] ?: '件';
        }
    }

    /**
     * 根据$productModel拼接产品规格信息
     *
     * @param \yii\base\Model $data
     *
     * @return string
     */
    static public function getProductSpecificationText($data)
    {

        $num = $data['specification'];        // 70ml
        $specification_num = isset($data['specification_num']) ? $data['specification_num'] : 0;        // 70ml
        $specification_unit = isset($data['specification_unit']) ? $data['specification_unit'] : '';        // 70ml
        //$spe = $data['package_spe'];        //解决saas用单品单位做销售单位的问题，临时解决
        $spe = '';        // 瓶
        $num_pac = $data['package_num'];    // 5  包装数量
        //$spe_pac = $data['package'];		// 箱  2.9版本去掉
        // 最终输出字串

        if ($specification_num > 0) {
            $num = $specification_num . $specification_unit;
        }

        // "70ml"字段有斜杠,则无视"瓶"字段
        if (strpos($num, '/') !== false) {
            $spe = '';
        }

        // 打包内含物数量大于1,才生效
        if ($num_pac <= 1) {
            $num_pac = 0;
        }

        // 最终输出字串
        $specification = '';

        // 有"70ml"字段才显示规格字串
        if ($num) {
            // 拼接70ml
            $specification .= $num;
            // 打包内含物个数
            if ($num_pac) {
                // 填写了数量就要显示
                $specification .= '×' . $num_pac;
                // 最后拼单品和打包规格
                if ($spe) {
                    $specification .= $spe;
                }

            }
            //2.9版本去掉
//            else{
//                // 没填内含物数量则拼单品规格
//                if($spe){
//                    $specification .= '/'.$spe;
//                }
//            }
        }

        return $specification;

    }

    public function getAttributes($names = null, $except = [])
    {
        $data = parent::getAttributes($names, $except);
        // 处理返点的小数点问题
        $data['rebates'] = floatval($data['rebates']);
        return $data;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductTags()
    {
        return $this->hasMany(new Tags(self::$cityId), ['product_id' => 'entity_id']);
    }

    /**
     * @param $city
     * @param $productId
     * @return array
     */
    public function getTags($city = 0, $productId = 0)
    {
        //return $this->hasMany(Tags::className(), ['product_id' => 'entity_id']);

        if (!$city) {
            $city = self::$cityId;
        }
        if (!$productId) {
            $productId = $this->getAttribute('entity_id');
        }
        if (!$city || !$productId) {
            return false;
        }

        // 连表查tags
        $tags = Tags::getTags($city, $productId);
        //Tools::log($result, 'exception.log');

        return $tags;
    }

    public static function formatProductToES($product_id, $city)
    {
        $fields = [
            'p.entity_id as entity_id',
            'p.rebates as rebates',
            'p.name as name',
            'a.name as first_category_name',
            'b.name as second_category_name',
            'c.name as third_category_name',
            'd.sort as wholesaler_weight',
            'p.status',
            'p.commission',
            'p.label1',
            'p.type',
            'p.type2',
            'p.sales_type',
            'promotion_text',
            'lsin',
            'barcode',
            'wholesaler_id',
            'first_category_id',
            'second_category_id',
            'third_category_id',
            'brand',
            'package_num',
            'package_spe',
            'state',
            'sort_weights',
            'sold_qty',
            'price',
            'special_price',
            'rule_id',
            'special_from_date',
            'special_to_date',
            'promotion_text_from',
            'promotion_text_to',
            'real_sold_qty',
            'qty',
            'minimum_order',
            'gallery',
            'export',
            'origin',
            'package',
            'specification',
            'shelf_life',
            'description',
            'production_date',
            'restrict_daily',
            'subsidies_lelai',
            'subsidies_wholesaler',
            'promotion_title_from',
            'promotion_title_to',
            'promotion_title',
            'sales_attribute_name',
            'sales_attribute_value',
            'specification_num',
            'specification_unit',
            'fake_sold_qty',
            'special_rebates_from',
            'special_rebates_to',
            'special_rebates_lelai_from',
            'special_rebates_lelai_to',
            'special_rebates_lelai',
            'special_rebates',
            'is_calculate_lelai_rebates',
            'rebates_lelai',
            'shelf_from_date',
            'shelf_to_date',
        ];
        $productModel = new Products($city);
        $product = $productModel->find()->alias('p')
            ->select($fields)
            ->leftJoin('lelai_slim_pms.catalog_category as a', 'a.entity_id = first_category_id')
            ->leftJoin('lelai_slim_pms.catalog_category as b', 'b.entity_id = second_category_id')
            ->leftJoin('lelai_slim_pms.catalog_category as c', 'c.entity_id = third_category_id')
            ->leftJoin('lelai_slim_merchant.le_merchant_store as d', 'd.entity_id = wholesaler_id')
            ->where(['p.entity_id' => $product_id])->asArray()->one();

        if(empty($product)){
            return;
        }

        $filterFields = ['special_from_date', 'special_to_date', 'promotion_text_from', 'promotion_text_to', 'production_date', 'promotion_title_from', 'promotion_title_to',
            'special_rebates_from', 'special_rebates_to', 'special_rebates_lelai_from', 'special_rebates_lelai_to'];
        foreach ($filterFields as $field) {
            if (isset($product[$field])) {
                if (strpos($product[$field], '0000-00-00') !== false) {
                    $product[$field] = null;
                }
            }
        }

        $product['entity_id'] = intval($product['entity_id']);
        $product['wholesaler_id'] = intval($product['wholesaler_id']);
        $product['first_category_id'] = intval($product['first_category_id']);
        $product['second_category_id'] = intval($product['second_category_id']);
        $product['third_category_id'] = intval($product['third_category_id']);
        $product['package_num'] = intval($product['package_num']);
        $product['status'] = intval($product['status']);
        $product['state'] = intval($product['state']);
        $product['package_num'] = intval($product['package_num']);
        $product['sort_weights'] = intval($product['sort_weights']);
        $product['sold_qty'] = intval($product['sold_qty']);
        $product['qty'] = intval($product['qty']);
        $product['restrict_daily'] = intval($product['restrict_daily']);
        $product['export'] = intval($product['export']);
        $product['real_sold_qty'] = intval($product['real_sold_qty']);
        $product['minimum_order'] = intval($product['minimum_order']);
        $product['fake_sold_qty'] = intval($product['fake_sold_qty']);
        $product['is_calculate_lelai_rebates'] = intval($product['is_calculate_lelai_rebates']);
        $product['sales_type'] = intval($product['sales_type']);

        $product['specification_num'] = floatval($product['specification_num']);
        $product['subsidies_wholesaler'] = floatval($product['subsidies_wholesaler']);
        $product['special_rebates_lelai'] = floatval($product['special_rebates_lelai']);
        $product['rebates_lelai'] = floatval($product['rebates_lelai']);
        $product['subsidies_lelai'] = floatval($product['subsidies_lelai']);

        $product['special_price'] = floatval($product['special_price']);
        $product['price'] = floatval($product['price']);
        $product['rule_id'] = intval($product['rule_id']);
        $product['wholesaler_weight'] = intval($product['wholesaler_weight']);

        //auto complete
        $product['brand_suggest'] = $product['brand'];
        $product['brand_agg'] = $product['brand'];
        $product['name_suggest'] = $product['name'];
        $product['specification_num_unit'] = $product['specification_num'] . $product['specification_unit'];
        $product['search_text'] = $product['promotion_text'] . $product['brand'] . $product['name'];
        return $product;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $curDateTime = ToolsAbstract::getDate()->date();
        if ($insert) {
            $this->created_at = $curDateTime;
        }
        $this->updated_at = $curDateTime;
        return parent::beforeSave($insert);
    }
}
