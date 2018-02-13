<?php

namespace console\controllers;

use common\models\AvailableCity;
use framework\components\ToolsAbstract;
use yii\console\Controller;

/**
 * Site controller
 */
class ProductController extends Controller
{

    public function actionRefreshProductCache()
    {
        $redis = ToolsAbstract::getRedis();
        $city_all = AvailableCity::find()->all();
        /** @var AvailableCity $city */
        foreach ($city_all as $city) {
            $redis->delete('products_'.$city->city_code);
        }
    }

    public function actionSyncProductCategory()
    {
        $city_all = AvailableCity::find()->all();
        /** @var AvailableCity $city */
        foreach ($city_all as $city) {
            $city_code = $city->city_code;
            $first_category_id = "UPDATE `products_city_{$city_code}` as p,(select lsin,max(first_category_id) as first_category_id from  lelai_slim_pms.catalog_product GROUP BY lsin) as t SET p.first_category_id =t.first_category_id where p.lsin = t.lsin and p.lsin in (select DISTINCT  lsin from  lelai_slim_pms.catalog_product);";
            $second_category_id = "UPDATE `products_city_{$city_code}` as p,(select lsin,max(second_category_id) as second_category_id from  lelai_slim_pms.catalog_product GROUP BY lsin) as t SET p.second_category_id =t.second_category_id where p.lsin = t.lsin and p.lsin in (select DISTINCT  lsin from  lelai_slim_pms.catalog_product);";
            $third_category_id = "UPDATE `products_city_{$city_code}` as p,(select lsin,max(third_category_id) as third_category_id from  lelai_slim_pms.catalog_product GROUP BY lsin) as t SET p.third_category_id =t.third_category_id where p.lsin = t.lsin and p.lsin in (select DISTINCT  lsin from  lelai_slim_pms.catalog_product);";
            print_r($first_category_id);
            echo PHP_EOL;
            print_r($second_category_id);
            echo PHP_EOL;
            print_r($third_category_id);
            echo PHP_EOL;
        }
    }
}
