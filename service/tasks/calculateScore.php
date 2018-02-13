<?php
/**
 * 供货商综合得分规则
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:22
 */

namespace service\tasks;

use framework\components\ToolsAbstract;
use framework\tasks\TaskAbstract;

use common\models\SalesFlatOrder;
use common\models\LeMerchantStore;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use service\components\Tools;
use common\models\Products;

class calculateScore extends TaskAbstract
{
    private $self_wholesaler_name = ['t', 'T', '特通渠道', '乐来供应链', '测试'];
    private $test_wholesaler_ids = [2, 4, 5, 12, 42, 260, 285];
    private $test_customer_id = [1021, 1206, 1208, 1215, 1245, 2299, 2376, 2476, 1942, 1650, 2541];
    private $calculate_status = ['pending', 'processing', 'processing_receive', 'processing_shipping', 'processing_arrived', 'pending_comment', 'complete'];

    private $reward_score = [100, 80, 60, 40, 20];

    //活动得分倍率
    const PROMOTION_RATE = 20;
    //特价商品得分倍率
    const SPECIAL_OFFER_RATE = 20;

    public function run($data)
    {
        ToolsAbstract::log(str_repeat('*--*', 32), 'debug.txt');
        $start_time = date("Y-m-d", strtotime("-1day"));
        $end_time = date("Y-m-d");
        //$end_time = date("Y-m-d", strtotime("+1day"));  //用于测试环境验证数据
        try {
            //查询前一天销售额前5名
            $saleTop5Score = $this->CalculateSale($start_time, $end_time);
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', $saleTop5Score:' . print_r($saleTop5Score, true) . PHP_EOL, 'debug.txt');
            //查询所有供货商列表
            $wholesalerIds = MerchantResourceAbstract::getAllWholesalerIds();

            //所有供货商信息[活动得分、特价商品得分]
            //$wholesalers = MerchantResourceAbstract::getStoreDetail2($wholesalerIds, 0);
            $wholesalers = $this->getStoreInfo($wholesalerIds, 0);
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', $wholesalers:' . print_r($wholesalers, true) . PHP_EOL, 'debug.txt');

            //供货商权重得分
            $wholesalerScores = $this->getWholesalerScore($wholesalerIds);
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', $wholesalerScores:' . print_r($wholesalerScores, true) . PHP_EOL, 'debug.txt');
            //ToolsAbstract::log(str_repeat('-', 30), 'debugCalculate.txt');
            ToolsAbstract::log('|  供货商  |  供货商id  |  销售额得分   |   活动得分   |    特价商品得分  |   供货商权重分数  |  综合得分 |', 'debugCalculate.txt');
            ToolsAbstract::log('|--------|--------|--------|--------|--------|--------|--------|', 'debugCalculate.txt');

            //供货商综合得分 = 　销售额得分 + 活动得分 + 特价商品得分 + 供货商权重分数
            $compositeScore = [];
            foreach ($wholesalers as $k => $wholesaler) {
                $debugLogStr = "| ";  //记录log
                $debugLogStr .= $wholesaler['wholesaler_name'] . "  |";
                $wholesalerId = $wholesaler['wholesaler_id'];
                $debugLogStr .= " $wholesalerId  |";
                //销售额得分
                $totalScore = isset($saleTop5Score[$wholesalerId]) ? $saleTop5Score[$wholesalerId] : 0;
                $debugLogStr .= " $totalScore  |";
                //活动得分
                $promotionScore = 0;
                if (isset($wholesaler['promotion_message_in_tag']) && !empty($wholesaler['promotion_message_in_tag'])) {
                    $promotionScore = count($wholesaler['promotion_message_in_tag']) * self::PROMOTION_RATE;
                }
                $debugLogStr .= " $promotionScore(" . count($wholesaler['promotion_message_in_tag']) . ")  |";
                $totalScore += $promotionScore;
                //特价商品得分
                $specialOfferScore = 0;
                if (isset($wholesaler['special_product_number']) && !empty($wholesaler['special_product_number'])) {
                    $specialOfferScore = intval($wholesaler['special_product_number']) * self::SPECIAL_OFFER_RATE;
                }
                $debugLogStr .= " $specialOfferScore(" . intval($wholesaler['special_product_number']) . ")  |";
                $totalScore += $specialOfferScore;
                //供货商权重分数
                $scoreWeight = isset($wholesalerScores[$wholesalerId]) ? $wholesalerScores[$wholesalerId] : 0;
                $debugLogStr .= " $scoreWeight  |";
                $totalScore += $scoreWeight;

                $debugLogStr .= " $totalScore  |";
                ToolsAbstract::log($debugLogStr, 'debugCalculate.txt');
                //
                $compositeScore[$wholesalerId] = intval($totalScore);
            }
            ToolsAbstract::log(str_repeat('-', 30), 'debugCalculate.txt');
            unset($saleTop5Score, $wholesalerIds, $wholesalers, $wholesalerScores, $wholesaler);
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', $compositeScore:' . print_r($compositeScore, true) . PHP_EOL, 'debug.txt');
            if (empty($compositeScore)) {
                ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', exit by empty $compositeScore.', 'debug.txt');
                return false;
            }
        } catch (\Exception $e) {
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', throw Exception:' . $e->getMessage(), 'debug.txt');
        }

        //DB操作，事务
        $tr = LeMerchantStore::getDb()->beginTransaction();
        try {
            $table = LeMerchantStore::tableName();
            $sql = "UPDATE {$table} SET sort_score =0 WHERE sort_score>0;";
            LeMerchantStore::getDb()->createCommand($sql)->query();
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', sql:' . $sql . PHP_EOL, 'debug.txt');
            //
            $sql = $this->generateUpdateSQL($table, $compositeScore);
            LeMerchantStore::getDb()->createCommand($sql)->query();
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', sql:' . $sql . PHP_EOL, 'debug.txt');
            //提交
            $tr->commit();
        } catch (\Exception $e) {
            //回滚
            ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', rollBack.' . PHP_EOL, 'debug.txt');
            $tr->rollBack();
        }
        //print_r($compositeScore);
        return false;
    }


    /**
     * 查询前一天销售额前5的供货商
     * 有效单：前一天下单(订单创建时间)且未被取消或者拒单
     * @param $start_time
     * @param $end_time
     * @return array
     */
    private function CalculateSale($start_time, $end_time)
    {
        $sales = SalesFlatOrder::find()->select(['wholesaler_id', 'SUM(grand_total) as total_sale'])
            ->where(['between', 'created_at', $start_time, $end_time])
            ->andWhere(['not like', 'wholesaler_name', $this->self_wholesaler_name])
            ->andWhere(['not in', 'wholesaler_id', $this->test_wholesaler_ids])
            ->andWhere(['not in', 'customer_id', $this->test_customer_id])
            ->andWhere(['in', 'status', $this->calculate_status])
            ->groupBy('wholesaler_id')
            ->orderBy('total_sale desc');
        $sql = $sales->createCommand()->getRawSql();
        ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', $sql.' . $sql . PHP_EOL, 'debug.txt');
        $sales = $sales->asArray()->all();
        if (empty($sales)) {
            return [];
        }
        //
        ToolsAbstract::log('F:' . __FUNCTION__ . ', L' . __LINE__ . ', $sales:' . print_r($sales, true) . PHP_EOL, 'debug.txt');
        $level = count($this->reward_score);
        $total_sale = $sales[0]['total_sale'];
        $saleReward = [];
        $index = 0;
        ToolsAbstract::log(str_repeat('*--*', 30), 'debugCalculate.txt');
        ToolsAbstract::log('| 供货商id | 销售额 | 得分 |', 'debugCalculate.txt');
        ToolsAbstract::log('|--------|--------|--------|', 'debugCalculate.txt');
        foreach ($sales as $row) {
            if ($index >= $level) {
                break;
            }
            if ($row['total_sale'] < $total_sale) {
                $index++;
                $total_sale = $row['total_sale'];
            }
            if (!isset($this->reward_score[$index])) {
                break;
            }
            $saleReward[$row['wholesaler_id']] = $this->reward_score[$index];
            ToolsAbstract::log('| ' . $row['wholesaler_id'] . ' | ' . $row['total_sale'] . ' | ' . $this->reward_score[$index] . ' |', 'debugCalculate.txt');
        }
        ToolsAbstract::log(str_repeat('-', 30), 'debugCalculate.txt');
        return $saleReward;
    }


    /**
     * 供应商信息
     * @param $wholesalerIds
     * @return array|\framework\db\ActiveRecord[]
     */
    private function getWholesalersSimple($wholesalerIds)
    {
        //查出所有供应商
        $order = implode(',', $wholesalerIds);
        $order_by = [new Expression("FIELD (`entity_id`," . $order . ")")];     //按顺序查出所有供应商
        $wholesalers = LeMerchantStore::find()->select(['entity_id', 'sort'])->where(['in', 'entity_id', $wholesalerIds])
            ->orderBy($order_by)->asArray()->all();
        return $wholesalers;
    }


    /**
     * 获取供应商对应的权重得分
     * @param $wholesalerIds
     * @return array
     */
    private function getWholesalerScore($wholesalerIds)
    {
        $wholesalers_simple = $this->getWholesalersSimple($wholesalerIds);
        $wholesalerScores = [];
        foreach ($wholesalers_simple as $row) {
            $wholesalerScores[$row['entity_id']] = $row['sort'];
        }
        return $wholesalerScores;
    }

    /**
     * 批量更新sql
     * @param $table
     * @param $compositeScore
     * @return string
     */
    private function generateUpdateSQL($table, $compositeScore)
    {
        $clauseSql = "";
        foreach ($compositeScore as $entity_id => $sort_score) {
            $clauseSql .= " WHEN {$entity_id} THEN {$sort_score} ";
        }
        $wholesalerIds = implode(',', array_keys($compositeScore));
        $sql = "UPDATE `{$table}` SET `sort_score` = CASE `entity_id` {$clauseSql} END WHERE `entity_id` IN ({$wholesalerIds});";
        return $sql;
    }

    /**
     * 根据$storeModel返回商家详情数组
     * promotion_message_in_tag
     * special_product_number
     * @param $wholesalerIds
     * @param $areaId
     * @return array
     */
    private function getStoreInfo($wholesalerIds)
    {
        $data = [];
        if (!is_array($wholesalerIds) || count($wholesalerIds) == 0) {
            return $data;
        }

        //查出所有供应商
        $order = implode(',', $wholesalerIds);
        $order_by = [new Expression("FIELD (`entity_id`," . $order . ")")];     //按顺序查出所有供应商
        $wholesalers = LeMerchantStore::find()->where(['in', 'entity_id', $wholesalerIds])
            ->orderBy($order_by)->asArray()->all();

        //供应商促销信息[全部优惠]
        $rules = Tools::getWholesalerAllPromotions(array_unique($wholesalerIds));

        //组织数据
        foreach ($wholesalers as $merchantInfo) {
            $promotion_message_in_tag = MerchantResourceAbstract::getWholesalerPromotionMessageInTag($rules, $merchantInfo['entity_id']);
            $data[$merchantInfo['entity_id']] = [
                'wholesaler_id' => $merchantInfo['entity_id'],
                'wholesaler_name' => $merchantInfo['store_name'],
                'promotion_message_in_tag' => $promotion_message_in_tag,
                'special_product_number' => 0, //特价商品数量
            ];
            //
            $now = date("Y-m-d H:i:s");
            $product_ids = [];
            $model = new Products($merchantInfo['city']);
            //特价商品
            $product_ids = $model->find()
                ->where(['wholesaler_id' => $merchantInfo['entity_id']])
                ->andWhere(['state' => Products::STATE_APPROVED])
                ->andWhere(['status' => Products::STATUS_ENABLED])
                ->andWhere(['>', 'special_price', 0])
                ->andWhere(['<', 'special_from_date', $now])
                ->andWhere(['>', 'special_to_date', $now])
                ->column();
            $data[$merchantInfo['entity_id']]['special_product_number'] = count($product_ids);
        }
        return $data;
    }


}