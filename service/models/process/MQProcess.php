<?php
namespace service\models\process;

use framework\components\es\Console;
use framework\components\ToolsAbstract;
use framework\core\ProcessInterface;
use framework\core\SWServer;
use framework\mq\MQAbstract;
use PhpAmqpLib\Message\AMQPMessage;
use service\components\Tools;
use service\models\merchant\Observer;
use service\models\merchant\observer\Customer;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-2
 * Time: 上午11:12
 */

/**
 * Class MQProcess
 * @package service\models\process
 */
class MQProcess implements ProcessInterface
{
    /**
     * @inheritDoc
     */
    public function run(SWServer $SWServer, \swoole_process $process)
    {
        try {
            Tools::getMQ(true)->consume(function ($msg) {
                $body = [];
                try {
                    /** @var  AMQPMessage $msg */
                    Console::get()->log($msg->body, null, [__METHOD__]);
                    Tools::log($msg->body, 'mq_process.log');
                    $body = json_decode($msg->body, true);

                    $tags = [];
                    $key = ToolsAbstract::arrayGetString($body, 'key');
                    $data = ToolsAbstract::arrayGetString($body, 'value');
                    Tools::log($key, 'mq_process_key.log');
                    switch ($key) {
                        // 新订单,发推送给商家app
                        case MQAbstract::MSG_ORDER_NEW:
                            $tags[] = MQAbstract::MSG_ORDER_NEW;
                            Observer::orderNew($data['order']);
                            Observer::reduceSeckillProductStock($data['order'], $data['extra']);
                            Observer::updateGroupProductStocksOnOrderNew($data['order'], $data['extra']);
                            break;
                        // 用户申请取消订单,发推送给商家app
                        case MQAbstract::MSG_ORDER_APPLY_CANCEL:
                            $tags[] = MQAbstract::MSG_ORDER_APPLY_CANCEL;
                            Observer::orderApplyCancel($data['order']);
                            break;
                        // 供应商拒接订单
                        case MQAbstract::MSG_ORDER_CLOSED:
                            $tags[] = MQAbstract::MSG_ORDER_CLOSED;
                            Tools::log(MQAbstract::MSG_ORDER_CLOSED, 'orderRevertQty.log');
                            Observer::orderRevertQty($data['order']);
                            Observer::updateGroupProductStocksOnOrderImcomplete($data['order'], $data['extra']);
                            break;
                        // 用户拒收订单
                        case MQAbstract::MSG_ORDER_REJECTED_CLOSED:
                            $tags[] = MQAbstract::MSG_ORDER_REJECTED_CLOSED;
                            Tools::log(MQAbstract::MSG_ORDER_REJECTED_CLOSED, 'orderRevertQty.log');
                            Observer::orderReject($data['order']);
                            Observer::orderRevertQty($data['order']);
                            Observer::updateGroupProductStocksOnOrderImcomplete($data['order'], $data['extra']);
                            break;
                        // 用户确认签收
                        case MQAbstract::MSG_ORDER_PENDING_COMMENT: // 超市签收，待评价
                            $tags[] = MQAbstract::MSG_ORDER_PENDING_COMMENT;
                            Observer::orderComplete($data['order']);
                            break;
                        case MQAbstract::MSG_PRODUCT_UPDATE: //商品更新信息
                            $tags[] = MQAbstract::MSG_PRODUCT_UPDATE;
                            Tools::log($data, 'mq_process_key.log');
                            Observer::productUpdate($data);
                            Observer::updateGroupProductStocksOnProUpdate($data);
                            break;
                        case MQAbstract::MSG_PRODUCT_DELETE: //删除商品
                            $tags[] = MQAbstract::MSG_PRODUCT_DELETE;
                            Observer::productDelete($data);
                            break;
                        // 套餐子商品更新
                        case MQAbstract::MSG_GROUP_SUB_PRODUCT_UPDATE:
                            $tags[] = MQAbstract::MSG_GROUP_SUB_PRODUCT_UPDATE;
                            Observer::updateGroupProductStockOnSubProductUpdate($data);
                            break;
                        // 用户被审核通过,同步
                        case MQAbstract::MSG_CUSTOMER_APPROVED:
                            $tags[] = MQAbstract::MSG_CUSTOMER_APPROVED;
                            Customer::customerInfoSyncToRelationship($data);
                            break;
                        // 用户修改资料,同步
                        case MQAbstract::MSG_CUSTOMER_UPDATE:
                            $tags[] = MQAbstract::MSG_CUSTOMER_UPDATE;
                            Customer::customerInfoSyncToRelationship($data);
                            break;
                        // 取消订单，同步库存
                        case MQAbstract::MSG_ORDER_CANCEL:
                            $tags[] = MQAbstract::MSG_ORDER_CANCEL;
                            Observer::orderRevertQty($data['order']);
                            Observer::updateGroupProductStocksOnOrderImcomplete($data['order'], $data['extra']);
                            break;
                        case MQAbstract::MSG_ORDER_AGREE_CANCEL:
                            $tags[] = MQAbstract::MSG_ORDER_AGREE_CANCEL;
                            Observer::orderRevertQty($data['order']);
                            Observer::updateGroupProductStocksOnOrderImcomplete($data['order'], $data['extra']);
                            break;
                        default:
                            $tags[] = MQAbstract::MSG_INVALID_KEY;
                    }

                    Console::get()->log($msg, null, $tags);

                } catch (\Exception $e) {
                    Tools::logException($e);
                    Tools::log($body, 'mq_exception.log');
                }

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

            });
        } catch (\Exception $e) {
            Tools::logException($e);
        }
    }
}