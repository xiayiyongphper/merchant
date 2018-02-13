<?php

namespace service\tasks;

use common\models\AvailableCity;
use common\models\Products;
use Elasticsearch\ClientBuilder;
use framework\tasks\TaskAbstract;
use service\components\Tools;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class updateEsProduct extends TaskAbstract
{
    public $index = 'products';

    public $filterFields = ['special_from_date', 'special_to_date', 'promotion_text_from', 'promotion_text_to', 'production_date', 'promotion_title_from', 'promotion_title_to',
        'special_rebates_from', 'special_rebates_to', 'special_rebates_lelai_from', 'special_rebates_lelai_to'];

    public $fields = [
        'p.entity_id', 'p.rebates', 'p.name', 'a.name as first_category_name', 'b.name as second_category_name', 'c.name as third_category_name', 'd.sort as wholesaler_weight',
        'p.status', 'p.commission', 'p.label1', 'br.sort as brand_weight', 'promotion_text', 'barcode', 'wholesaler_id', 'first_category_id',
        'second_category_id', 'third_category_id', 'brand', 'package_num', 'package_spe', 'state', 'sort_weights', 'sold_qty',
        'price', 'special_price', 'rule_id', 'special_from_date', 'special_to_date', 'promotion_text_from', 'promotion_text_to',
        'real_sold_qty', 'qty', 'minimum_order', 'gallery', 'export', 'origin', 'package', 'specification', 'shelf_life', 'description', 'production_date',
        'restrict_daily', 'subsidies_lelai', 'subsidies_wholesaler', 'promotion_title_from', 'promotion_title_to', 'promotion_title', 'sales_attribute_name', 'sales_attribute_value',
        'specification_num', 'specification_unit', 'fake_sold_qty', 'special_rebates_from', 'special_rebates_to', 'special_rebates_lelai_from', 'special_rebates_lelai_to',
        'special_rebates_lelai', 'special_rebates', 'is_calculate_lelai_rebates', 'rebates_lelai',
    ];

    public function run($data = null)
    {
        $this->updateAllProduct();
    }

    public function updateAllProduct()
    {
        $city_all = AvailableCity::find()->all();
        /** @var AvailableCity $city */
        foreach ($city_all as $city) {
            $city_code = $city->city_code;
            $this->actionImport($city_code);
        }
    }

    public function actionImport($city_code)
    {
        //Tools::log($city_code, 'updateEsProduct.log');
        //es设置
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        if (empty($city_code)) {
            echo 'city null';
            return;
        }
        $productModel = new Products($city_code);
        $max_id = $productModel->find()->max('entity_id');
        for ($i = 0; $i <= $max_id; $i += 100) {
            $products = $productModel->find()->alias('p')
                ->select($this->fields)
                ->leftJoin('lelai_slim_pms.catalog_category as a', 'a.entity_id = first_category_id')
                ->leftJoin('lelai_slim_pms.catalog_category as b', 'b.entity_id = second_category_id')
                ->leftJoin('lelai_slim_pms.catalog_category as c', 'c.entity_id = third_category_id')
                ->leftJoin('lelai_slim_merchant.le_merchant_store as d', 'd.entity_id = wholesaler_id')
                ->leftJoin('lelai_slim_common.brand as br', 'br.name = brand')
                ->where(['between', 'p.entity_id', $i, $i + 100])->asArray()->all();
            if (count($products) > 0) {
                $params = [];
                foreach ($products as $product) {

                    foreach ($this->filterFields as $field){
                        if(isset($product[$field])){
                            if(strpos($product[$field],'0000-00-00') !== false){
                                $product[$field] = null;
                            }
                        }
                    }

                    Tools::log($product['entity_id'], 'updateEsProduct.log');
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
                    $product['specification_num'] = floatval($product['specification_num']);
                    $product['subsidies_wholesaler'] = floatval($product['subsidies_wholesaler']);
                    $product['special_rebates_lelai'] = floatval($product['special_rebates_lelai']);
                    $product['rebates_lelai'] = floatval($product['rebates_lelai']);
                    $product['subsidies_lelai'] = floatval($product['subsidies_lelai']);

                    $product['special_price'] = floatval($product['special_price']);
                    $product['price'] = floatval($product['price']);
                    $product['rule_id'] = intval($product['rule_id']);
                    $product['wholesaler_weight'] = intval($product['wholesaler_weight']);

                    $product['brand_weight'] = intval($product['brand_weight']);

                    //auto complete
                    $product['brand_suggest'] = $product['brand'];
                    $product['name_suggest'] = $product['name'];
                    $product['specification_num_unit'] = $product['specification_num'] . $product['specification_unit'];

                    $params['body'][] = [
                        'index' => [
                            '_index' => $this->index,
                            '_type' => $city_code,
                            '_id' => $product['entity_id']
                        ]
                    ];

                    $params['body'][] = $product;
                }

                $client->bulk($params);
            }
        }
    }
}