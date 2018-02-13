<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 1/2/2016
 * Time: 2:17 PM
 */

namespace service\resources\merchant\v1;

use common\models\GroupSubProducts;
use common\models\Products;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\components\Transaction;
use service\message\common\Product;
use service\message\merchant\reduceQtyRequest;
use service\message\merchant\reduceQtyResponse;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;

class reduceQty extends MerchantResourceAbstract
{
    public $isFixGroupSubProducts = true;
    public $checkCustomer = null;

    public function run($data)
    {

        /** @var reduceQtyRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        // 检查登录态
        if ($this->checkCustomer === false) {
            // 主动设置false，肯定不检测
        } elseif (!$this->isRemote()) {
            // 主动设置local，则不检测
        } else {
            $customer = $this->_initCustomer($request);
        }

        if ($request->getAuthToken() && (string)$request->getAuthToken() === 'crontab_not_fix_group') {
            $this->isFixGroupSubProducts = false; // 指定为crontab_not_fix_group，不修复套餐子商品，定时系统用
        } else {
            $this->isFixGroupSubProducts = true;
        }

        // 商品列表
        $products = $request->getProducts();

        return $this->reduce($products);

    }


    /**
     * @param $products array 商品列表
     *
     * @return reduceQtyResponse
     * @throws \Exception
     */
    public function reduce($products)
    {

        // 商品列表
        // $products = $request->getProducts();

        // 减库存的transaction
        $transaction = new Transaction();

        // 按商家id分组商品列表
        $productsByWholesaler = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $wholesaler_id = $product->getWholesalerId();
            if (!isset($productsByWholesaler[$wholesaler_id])) {
                $productsByWholesaler[$wholesaler_id] = [];
            }
            array_push($productsByWholesaler[$wholesaler_id], $product->toArray());
        }


        /*
         * 检查上下架和审核状态
         */
        foreach ($productsByWholesaler as $wholesaler_id => $items) {
            // 查商家所在城市
            $merchantModel = $this->getWholesaler($wholesaler_id);
            $city = $merchantModel->getAttribute('city');

            // 检查这个商家的商品是否下架或者未审核通过
            $this->checkItemsState($wholesaler_id, $city, $items);
        }

        /*
         * 检查库存
         */
        /** @var \yii\db\Connection $db */
        $sql_list = [];// 记下所有需要执行的sql
        $specialProductSqlList = [];//特价活动商品退库存的sql
        $clear_redis = [];
        foreach ($productsByWholesaler as $wholesaler_id => $items) {
            // 查商家所在城市
            $merchantModel = $this->getWholesaler($wholesaler_id);
            $city = $merchantModel->getAttribute('city');

            // 如果需要修复套餐商品数量，则修复。默认修复。不修复的情况是下单退单时候，商品列表为全部订单商品（包括了子商品）
            if ($this->isFixGroupSubProducts) {
                $items = $this->fixGroupSubProductItems($items);
            }

            // 检查这个商家的库存
            $this->checkItemsQty($wholesaler_id, $city, $items);

            ToolsAbstract::log($items, 'reduceQty.log');

            $table = 'products_city_' . $city;
            foreach ($items as $item) {
                $pid = $item['product_id'];
                $clear_redis['products_' . $city][] = $pid;
                $num = $item['num'];
                if (SpecialProduct::isSpecialProduct($pid)) {
                    //只有特价活动商品才需要退库存，秒杀商品不需要退库存
                    $specialProductSqlList[] = "UPDATE special_products SET `qty` = `qty`-{$num}, `sold_qty` = `sold_qty` + {$num}, `real_sold_qty` = `real_sold_qty` + {$num} WHERE `wholesaler_id` = '{$wholesaler_id}' AND `entity_id`='{$pid}' AND `type2` =" . SpecialProduct::TYPE_SPECIAL;
                } else {
                    $sql_list[] = "UPDATE {$table} SET `qty` = `qty`-{$num}, `sold_qty` = `sold_qty` + {$num}, `real_sold_qty` = `real_sold_qty` + {$num} WHERE `wholesaler_id` = '{$wholesaler_id}' AND `entity_id`='{$pid}';";
                }
            }
        }

        ToolsAbstract::log($specialProductSqlList, 'reduceQty.log');
        ToolsAbstract::log($sql_list, 'reduceQty.log');

        /*
         * 库存检查成功,开始减库存
         */
        // 能运行到这里说明之前都没抛异常,可以开始减库存
        try {
            $db = \Yii::$app->get('productDb');
            $transaction = $db->beginTransaction();
            foreach ($sql_list as $sql) {
                $db->createCommand($sql)->execute();
            }
            $transaction->commit();
            //删除redis 重新拉库存
            $redis = Tools::getRedis();
            foreach ($clear_redis as $city => $product_ids) {
                foreach ($product_ids as $product_id) {
                    $redis->hDel($city, $product_id);
                }
            }
        } catch (\Exception $e) {
            Tools::logException($e);
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            throw $e;
        }

        //执行到当前位置，说明之前的代码执行正常
        try {
            if (count($specialProductSqlList) > 0) {
                /** @var \yii\db\Connection $mainDb */
                $mainDb = \Yii::$app->get('mainDb');
                $mainTransaction = $mainDb->beginTransaction();
                foreach ($specialProductSqlList as $sql) {
                    $mainDb->createCommand($sql)->execute();
                }
                $mainTransaction->commit();
            }
            //删除redis 重新拉库存
            $redis = Tools::getRedis();
            foreach ($clear_redis as $city => $product_ids) {
                foreach ($product_ids as $product_id) {
                    $redis->hDel($city, $product_id);
                }
            }
            $success = true;
        } catch (\Exception $e) {
            Tools::logException($e);
            if (isset($mainTransaction)) {
                $mainTransaction->rollBack();
            }
            throw $e;
        }

        /** @var reduceQtyResponse $response */
        $response = $this->response();
        if ($success) {
            $response->setCode(0);
            $response->setMessage('ok');
        } else {
            $response->setCode(0);
            $response->setMessage('unknow error');
        }

        return $response;

    }

    public static function request()
    {
        return new reduceQtyRequest();
    }

    /**
     * 检查所给商品数组里是否存在未审核通过或者下架商品
     *
     * @param $wholesaler_id
     * @param $city
     * @param $items
     *
     * @throws \Exception
     */
    private function checkItemsState($wholesaler_id, $city, $items)
    {
        // 检查这个商家的商品是否下架或者未审核通过
        $productModel = new Products($city);
        $pCondition = [];
        foreach ($items as $item) {
            // $sCondition => state!=2 or status!=2
            $sCondition = ['!=', 'state', 2];
            $sCondition = ['or', $sCondition,
                ['!=', 'status', 1]// 状态
            ];
            // $sCondition => entity_id=xxx and (state!=2 or status!=2)
            $iCondition = ['and', $sCondition, ['entity_id' => $item['product_id']]];

            // $pCondition => (entity_id=xxx and (state!=2 or status!=2)) or (entity_id=xxx and (state!=2 or status!=2))
            if (count($pCondition) == 0) {
                $pCondition = $iCondition;
            } else {
                $pCondition = ['or', $pCondition, $iCondition];
            }
        }
        $condition = ['and', ['wholesaler_id' => $wholesaler_id], $pCondition];
        $productList = $productModel->find()
            ->where($condition);
        if ($productList->count() > 0) {
            // 找到,则说明有商品状态不对
            Exception::catalogProductNotFound();
        }
    }

    private function checkItemsQty($wholesaler_id, $city, $items)
    {
        // 检查这个商家的库存
        $productModel = new Products($city);
        $pCondition = [];
        foreach ($items as $item) {
            $iCondition = ['entity_id' => $item['product_id']];
            $iCondition = ['and', $iCondition,
                ['<', 'qty', $item['num']]// 库存小于购买数
            ];
            if (count($pCondition) == 0) {
                $pCondition = $iCondition;
            } else {
                $pCondition = ['or', $pCondition, $iCondition];
            }
        }
        $condition = ['and', ['wholesaler_id' => $wholesaler_id], $pCondition];
        $productList = $productModel->find()
            ->where($condition);
        if ($productList->count() > 0) {
            // 找到就是库存不足
            $names = [];
            foreach ($productList->all() as $product) {
                $name = $product->getAttribute('brand') . $product->getAttribute('name');
                array_push($names, $name);
            }
            Exception::catalogProductSoldOut2(implode('、', $names));
        }
    }

    /**
     * @param array $items
     * @return mixed
     */
    private function fixGroupSubProductItems($items)
    {
        ToolsAbstract::log('call fixGroupSubProductItems()', 'reduceQty.log');
        /*
         * 套餐商品在下单时候扣减相应的子商品库存，取消订单的时候再回库存。
         * 这是解决套餐商品和子商品（单独买）的问题。
         */
        $productKeys = [];
        $notCalcSubProducts = [];
        foreach ($items as $k => $v) {
            $productKeys[$v['product_id']] = $k;
            // 套餐商品，查找出相应子商品
            if (isset($v['type']) && ($v['type'] & Products::TYPE_GROUP)) {
                $subProducts = GroupSubProducts::find()
                    ->select('entity_id,group_product_id,ori_product_id,sub_product_num')
                    ->where(['group_product_id' => $v['product_id']])
                    ->all();
                ToolsAbstract::log($subProducts, 'reduceQty.log');
                if ($subProducts) {
                    // 修改相应子商品的数量
                    /** @var GroupSubProducts $subProduct */
                    foreach ($subProducts as $subProduct) {
                        $subProductTotal = $subProduct->sub_product_num * $v['num'];
                        if (isset($productKeys[$subProduct->ori_product_id])) { // 如果子商品（单独）在前面，则加上相应的数量
                            $items[$productKeys[$subProduct->ori_product_id]]['num'] += $subProductTotal;
                        } else { // 存储到还没加上的子商品数组
                            $notCalcSubProducts[$subProduct->ori_product_id] = [
                                'num' => $subProductTotal,
                                'product_id' => $subProduct->ori_product_id,
                            ];
                        }
                    }
                }
            } elseif (isset($notCalcSubProducts[$v['product_id']])) {  // 子商品（单独）在后面，则也加上相应的数量
                $v['num'] += $notCalcSubProducts[$v['product_id']]['num'];
                $items[$k] = $v;
                unset($notCalcSubProducts[$v['product_id']]);
            }
        }

        if ($notCalcSubProducts) {
            foreach ($notCalcSubProducts as $productId => $notCalcSubProduct) {
                $items[] = [
                    'product_id' => $notCalcSubProduct['product_id'],
                    'num' => $notCalcSubProduct['num'],
                ];
            }
        }
        return $items;
    }

    public static function response()
    {
        return new reduceQtyResponse();
    }
}