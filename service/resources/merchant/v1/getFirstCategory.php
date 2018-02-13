<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use common\models\LeMerchantStore;
use common\models\Products;
use service\components\Redis;
use service\components\Tools;
use service\message\common\CategoryNode;
use service\message\core\getCategoryRequest;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;


class getFirstCategory extends MerchantResourceAbstract
{
    protected $ids = [484, 485, 486, 487, 488, 491, 490, 489, 492, 493, 494];

    public function run($data)
    {
        /** @var getCategoryRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $wholesalerId = $request->getWholesalerId();

        $store = LeMerchantStore::findOne(['entity_id' => $wholesalerId]);
        $cityId = 0;
        if ($store) {
            $cityId = $store->city;
        } else {
            Exception::resourceNotFound();
        }

        $cityId = $cityId ? $cityId : '441800';
        //$cityId = $request->getCity();

        $productModel = new Products($cityId);
        //返回有商品的一级分类
        $firstCategories = $productModel::find()->select('first_category_id')->where(['wholesaler_id' => $wholesalerId])
            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
            ->groupBy('first_category_id')->asArray()->all();

        //分类展示顺序
        $categories = Redis::getCategories($this->ids);


        if (count($categories) == 0) {
            Exception::resourceNotFound();
        }

        // 加icon过滤,只要1级分类
        $res = array();
        foreach ($categories as $category) {
            foreach ($firstCategories as $firstCategory) {
                if ($category['id'] == $firstCategory['first_category_id']) {
                    $icon = Tools::getCategoryIconUrl($category['id']);
                    if ($icon) {
                        $category['icon'] = $icon;
                    }
                    unset($category['child_category']);
                    array_push($res, $category);
                }
            }
        }

        $response = $this->response();

        if (count($res)) {
            $pmsCategory = [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Root',
                'path' => '1',
                'level' => '0',
                'child_category' => $res,
            ];
            $response->setFrom(Tools::pb_array_filter($pmsCategory));
        } else {
            throw new \Exception('未找到分类', 4601);
        }

        return $response;
    }

    public static function request()
    {
        return new getCategoryRequest();
    }

    /**
     * @return CategoryNode
     */
    public static function response()
    {
        return new CategoryNode();
    }
}
