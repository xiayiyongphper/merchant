<?php

namespace common\models;

use framework\components\Date;
use service\components\Tools;
use service\events\ServiceEvent;
use service\models\merchant\Observer;
use service\models\payment\Method;
use service\resources\Exception;
use Yii;
use framework\db\ActiveRecord;


/**
 * This is the model class for table "sales_flat_order".
 *
 * @property string $entity_id
 * @property string $state
 * @property string $status
 * @property string $coupon_code
 * @property integer $wholesaler_id
 * @property string $wholesaler_name
 * @property string $phone
 * @property string $store_name
 * @property string $area_id
 * @property string $district
 * @property string $province
 * @property string $city
 * @property string $coupon_discount_amount
 * @property string $customer_id
 * @property string $grand_total
 * @property string $shipping_amount
 * @property string $discount_amount
 * @property string $subtotal
 * @property string $total_paid
 * @property string $total_qty_ordered
 * @property string $payment_method
 * @property integer $signed
 * @property integer $delivery_method
 * @property integer $customer_note_notify
 * @property integer $customer_group_id
 * @property integer $email_sent
 * @property string $total_due
 * @property string $increment_id
 * @property string $applied_rule_ids
 * @property string $order_currency_code
 * @property string $hold_before_state
 * @property string $hold_before_status
 * @property string $remote_ip
 * @property string $x_forwarded_for
 * @property string $customer_note
 * @property string $commission
 * @property string $balance
 * @property string $rebates
 * @property string $promotions
 * @property string $merchant_remarks
 * @property string $reserve_time
 * @property string $reserve_datetime
 * @property string $created_at
 * @property string $pay_time
 * @property string $updated_at
 * @property string $complete_at
 * @property integer $total_item_count
 * @property string $expire_time
 * @property \common\models\SalesOrderStatus $orderstatus
 * @property integer $receipt
 * @property integer $receipt_total
 * @property string $rebates_lelai
 * @property integer $source
 * @property \common\models\SalesFlatOrderAddress $orderaddress
 */
class SalesFlatOrder extends ActiveRecord
{

    /**
     * 新订单
     */
    const STATE_NEW = 'new';
    /**
     * 退款
     */
    const STATE_REFUND = 'refund';
    /**
     * 处理中
     */
    const STATE_PROCESSING = 'processing';
    /**
     * 完成
     */
    const STATE_COMPLETE = 'complete';
    /**
     * 已关闭
     */
    const STATE_CLOSED = 'closed';
    /**
     * 已取消
     */
    const STATE_CANCELED = 'canceled';
    /**
     * 挂起
     */
    const STATE_HOLDED = 'holded';


    /**
     * 完成
     */
    const STATUS_COMPLETE = 'complete';
    /**
     * 关闭
     */
    const STATUS_CLOSED = 'closed';

    /**
     * 关闭
     */
    const STATUS_REJECTED_CLOSED = 'rejected_closed';

    /**
     * 已取消
     */
    const STATUS_CANCELED = 'canceled';
    /**
     * 挂起状态
     */
    const STATUS_HOLDED = 'holded';
    /**
     * 新订单
     */
    const STATUS_PENDING = 'pending';

    /**
     * 待商家确认
     */
    const STATUS_PROCESSING = 'processing';
    /**
     * 商家已接单
     */
    const STATUS_PROCESSING_RECEIVE = 'processing_receive';
    /**
     * 商家已发货
     */
    const STATUS_PROCESSING_SHIPPING = 'processing_shipping';

    /**
     * 退款成功
     */
    const STATUS_REFUND = 'refund';

    /**
     * 退款成功(拒单)
     */
    const STATUS_REJECTED_REFUND = 'rejected_refund';

    /**
     * 等待退款
     */
    const STATUS_WAITING_REFUND = 'waiting_refund';

    /**
     * 等待退款（拒单）
     */
    const STATUS_REJECTED_WAITING_REFUND = 'rejected_waiting_refund';

    /**
     * 待评论
     */
    const STATUS_PENDING_COMMENT = 'pending_comment';

    /**
     * 退款失败
     * @deprecated
     */
    const STATUS_REFUND_FAILURE = 'refund_failure';
    public $receive_time;
    protected $_quote;
    protected $_statusHistory = [];
    /**
     * @var SalesFlatOrderAddress
     */
    protected $_address;
    protected $_items = [];
    protected $_orderStateChanged = false;
    protected $_oriState;
    protected $_oriStatus;
    protected $_traceId;

    const RECEIPT_NO = 0;
    const RECEIPT_ALL = 1;
    const RECEIPT_PARTIAL = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sales_flat_order';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('coreDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wholesaler_id', 'customer_id', 'total_item_count'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity Id',
            'state' => 'State',
            'status' => 'Status',
            'coupon_code' => 'Coupon Code',
            'wholesaler_id' => 'Store Id',
            'customer_id' => 'Customer Id',
            'grand_total' => 'Grand Total',
            'shipping_amount' => 'Shipping Amount',
            'discount_amount' => 'Discount Amount',
            'subtotal' => 'Subtotal',
            'total_paid' => 'Total Paid',
            'total_qty_ordered' => 'Total Qty Ordered',
            'payment_method' => 'Payment Method',
            'delivery_method' => 'Delivery Method',
            'customer_note_notify' => 'Customer Note Notify',
            'customer_group_id' => 'Customer Group Id',
            'email_sent' => 'Email Sent',
            'total_due' => 'Total Due',
            'increment_id' => 'Increment Id',
            'applied_rule_ids' => 'Applied Rule Ids',
            'order_currency_code' => 'Order Currency Code',
            'hold_before_state' => 'Hold Before State',
            'hold_before_status' => 'Hold Before Status',
            'remote_ip' => 'Remote Ip',
            'x_forwarded_for' => 'X Forwarded For',
            'customer_note' => 'Customer Note',
            'balance' => '钱包余额支付金额',
            'rebates' => '整单返现金额（返到钱包余额）',
            'merchant_remarks' => '商家备注',
            'reserve_time' => 'Reserve Time',
            'reserve_datetime' => 'Reserve Datetime',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'total_item_count' => 'Total Item Count',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItem()
    {
        return $this->hasMany(SalesFlatOrderItem::className(), ['order_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(SalesFlatOrderAddress::className(), ['order_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderaddress()
    {
        return $this->hasOne(SalesFlatOrderAddress::className(), ['order_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderstatus()
    {
        return $this->hasOne(SalesOrderStatus::className(), ['status' => 'status']);
    }

    public function updateStatus($arr, $id)
    {
        if ($arr['comment']) {
            $comment = $arr['comment'];
        }
        unset($arr['comment']);
        $update_res = SalesFlatOrder::updateAll($arr, ['entity_id' => $id]);
        if ($update_res) {
            $orderinfo = SalesFlatOrder::findOne($id);
            $status_history = new SalesFlatOrderStatusHistory();
            $status_history->status = $orderinfo->status;
            $status_history->parent_id = $id;
            $status_history->is_customer_notified = 2;
            $status_history->created_at = date('Y-m-d H:i:s');
            $status_history->comment = $comment;
            if ($status_history->save()) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    public static function getGeneralSelectColumns()
    {
        return [
            'entity_id',
            'increment_id',
            'wholesaler_id',
            'state',
            'status',
            'payment_method',
            'phone',
            'customer_note',
            'shipping_amount',
            'total_qty_ordered',
            'subtotal',
            'grand_total',
            'pay_time',
            'complete_at',
            'created_at'
        ];
    }

    /**
     * Function: afterFind
     * Author: Jason Y. Wang
     * magento中存入时间时用的UTC时间，从数据库时拿出时转化为PRC时间
     */
    public function afterFind()
    {
        parent::afterFind(); // TODO: Change the autogenerated stub
        $order_history_status = SalesFlatOrderStatusHistory::find()->where(['status' => 'processing_receive', 'parent_id' => $this->entity_id])->one();
        $this->receive_time = $order_history_status['created_at'];
        $this->_oriState = $this->state;
        $this->_oriStatus = $this->status;
    }

    public function setQuote($quote)
    {
        $this->_quote = $quote;
    }

    /**
     * Order state setter.
     * If status is specified, will add order status history with specified comment
     * the setData() cannot be overriden because of compatibility issues with resource model
     *
     * @param string $state
     * @param string|bool $status
     * @param string $comment
     * @param bool $isCustomerNotified
     * @return $this
     */
    public function setState($state, $status = false, $comment = '', $isCustomerNotified = null)
    {
        return $this->_setState($state, $status, $comment, $isCustomerNotified);
    }

    /**
     * Order state protected setter.
     * By default allows to set any state. Can also update status to default or specified value
     * Сomplete and closed states are encapsulated intentionally, see the _checkState()
     *
     * @param string $state
     * @param string|bool $status
     * @param string $comment
     * @param bool $isCustomerNotified
     * @param $shouldProtectState
     * @return $this
     */
    protected function _setState($state, $status = false, $comment = '',
                                 $isCustomerNotified = null)
    {
        $oldStatus = $this->status;
        $this->state = $state;

        // add status history
        if ($status) {
            if ($status === true) {
                throw new \Exception('Please set status instead of useing true.');
            }
            $this->status = $status;
            $history = $this->addStatusHistoryComment($comment, false); // no sense to set $status again
        }

        if ($oldStatus != $status) {
            $this->setOrderStateChanged(true);
        } else {
            $this->setOrderStateChanged(false);
        }
        return $this;
    }

    /*
     * Add a comment to order
     * Different or default status may be specified
     *
     * @param string $comment
     * @param string $status
     * @return LE_Sales_Model_Order_Status_History
     */
    public function addStatusHistoryComment($comment, $status = false)
    {
        if (false === $status) {
            $status = $this->status;
        } elseif (true === $status) {
            throw new \Exception('Please set status instead of useing true.');
        } else {
            $this->status = $status;
        }
        $date = new Date();
        $history = new SalesFlatOrderStatusHistory();
        $history->status = $status;
        $history->comment = $comment;
        $history->is_customer_notified = 0;
        $history->created_at = $date->gmtTimestamp();
        $this->addStatusHistory($history);
        return $history;
    }

    /**
     * Set the order status history object and the order object to each other
     * Adds the object to the status history collection, which is automatically saved when the order is saved.
     * See the entity_id attribute backend model.
     * Or the history record can be saved standalone after this.
     *
     * @param SalesFlatOrderStatusHistory $history
     * @return $this
     */
    public function addStatusHistory(SalesFlatOrderStatusHistory $history)
    {
        $history->setOrder($this);
        $this->status = $history->status;
        if (!$history->getPrimaryKey()) {
            $this->_statusHistory[] = $history;
        }
        return $this;
    }

    /**
     * 添加地址
     * @param SalesFlatOrderAddress $address
     * @return $this
     */
    public function setAddress(SalesFlatOrderAddress $address)
    {
        $this->_address = $address;
        return $this;
    }

    /**
     * 添加订单商品
     * @param SalesFlatOrderItem $item
     * @return $this
     */
    public function addItem(SalesFlatOrderItem $item)
    {
        if (!$item->getPrimaryKey()) {
            $this->_items[] = $item;
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOrderStateChanged()
    {
        return $this->_orderStateChanged;
    }

    /**
     * @param boolean $orderStateChanged
     */
    public function setOrderStateChanged($orderStateChanged)
    {
        $this->_orderStateChanged = $orderStateChanged;
    }

    /**
     * 订单保存之后的处理
     * @return $this
     */
    public function afterSave($insert, $changedAttributes)
    {
        /**
         * 订单地址数据
         */
        if (null !== $this->_address) {
            $this->_address->order_id = $this->getPrimaryKey();
            $this->_address->save();
        }

        /**
         * 订单商品数据
         */
        if (null !== $this->_items && count($this->_items) > 0) {
            /** @var SalesFlatOrderItem $item */
            foreach ($this->_items as $item) {
                $item->order_id = $this->getPrimaryKey();
                $item->save();
            }
        }

        /**
         * 订单交易数据
         */
        /*if (null !== $this->_transactions) {
            //$this->_transactions->save();
        }*/
        /**
         * 订单状态历史
         */
        if (null !== $this->_statusHistory && count($this->_statusHistory) > 0) {
            /** @var SalesFlatOrderStatusHistory $statusHistory */
            foreach ($this->_statusHistory as $statusHistory) {
                $statusHistory->parent_id = $this->getPrimaryKey();
                $statusHistory->save();
            }
        }

        /**
         * 用户
         */
        /*if (null !== $this->_customer) {
            //$this->_customer->save();
        }*/

        //parent::_afterSave();
        /*
         * 订单保存之后触发事件，并且要求订单状态发生实际改变时才触发事件。
         */
		if ($this->isOrderStateChanged()) {
			Tools::log('isOrderStateChanged');
			$event = new ServiceEvent();
			$event->setEventData(['order' => $this, 'state' => $this->_oriState, 'status' => $this->_oriStatus]);
			//$event->setTraceId($this->getTraceId());
			Observer::orderStateChanged($event);
		}
        return $this;
    }

    public function beforeSave($insert)
    {
        $date = new Date();
        //$this->created_at = $date->gmtDate();
        $this->updated_at = $date->gmtDate();
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * @return mixed
     */
    public function getTraceId()
    {
        return $this->_traceId;
    }

    /**
     * @param mixed $traceId
     */
    public function setTraceId($traceId)
    {
        $this->_traceId = $traceId;
    }

	/**
	 * 供应商是否可以拒单
	 * 只有在待供应商接单状态才能拒单
	 * @return bool
	 */
	public function canClose(){
		if ($this->state==self::STATE_PROCESSING && $this->status==self::STATUS_PROCESSING){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 供应商拒单
	 * @return $this
	 */
	public function close()
	{
		if($this->canClose()){
			$this->setState(self::STATE_CLOSED, self::STATUS_CLOSED);
			$this->setCompletedAt();
		}else{
			Exception::salesOrderCanNotClose();
		}
		return $this;
	}

    /**
     * 超市拒绝订单
     * @return $this
     * @throws Exception
     */
    public function decline()
    {
        if ($this->canDecline()) {
            switch ($this->payment_method) {
                case Method::WECHAT:
                case Method::ALIPAY:
                case Method::WX:
                    $this->setState(self::STATE_REFUND, self::STATUS_REJECTED_WAITING_REFUND);
                    break;
                case Method::OFFLINE:
                    $this->setState(self::STATE_CLOSED, self::STATUS_REJECTED_CLOSED);
					$this->setCompletedAt();
                    break;
            }
        } else {
            Exception::salesOrderCanNotDecline();
        }
        return $this;
    }

    /**
     * 是否可以拒绝订单
     * @return bool
     */
    public function canDecline()
    {
        $status = $this->status;
        $statues = array(
            self::STATUS_PROCESSING,
            self::STATUS_PROCESSING_RECEIVE,
            self::STATUS_PROCESSING_SHIPPING,
            self::STATUS_HOLDED,
        );
        if (in_array($status, $statues)) {
            return true;
        }
        return false;
    }

    /**
     * 撤销"申请取消订单"
     * @return $this
     */
    public function revokeCancel()
    {
        if ($this->canUnhold()) {
            $this->unhold();
        }
        return $this;
    }

    /**
     * 订单是否可以取消挂起
     *
     * @return bool
     */
    public function canUnhold()
    {
        return $this->state == self::STATE_HOLDED;
    }

    /**
     * 取消订单挂起状态
     *
     * @return $this
     * @throws Exception
     */
    public function unhold()
    {
        if (!$this->canUnhold()) {
            Exception::salesOrderCanNotUnHold();
        }
        $this->setState($this->hold_before_state, $this->hold_before_status);
        $this->hold_before_state = null;
        $this->hold_before_status = null;
        return $this;
    }

    public function receiptConfirm()
    {
        if (!$this->canReceiptConfirm()) {
            Exception::salesOrderCanNotReceiptConfirm();
        }
        $this->receipt = self::RECEIPT_ALL;
        $items = $this->getItemsCollection(false);
        /** @var SalesFlatOrderItem $item */
        foreach ($items as $item) {
            $item->receipt = SalesFlatOrderItem::RECEIPT_YES;
            $this->_items[] = $item;
        }
        $this->receipt_total = $this->subtotal;
        $this->setState(self::STATE_COMPLETE, self::STATUS_PENDING_COMMENT,'超市确认全部商品收货');
        $this->setCompletedAt();
        return $this;
    }

    /**
     * comment
     * Author Jason Y. wang
     * 评论
     * @return $this
     * @throws \Exception
     */
    public function comment()
    {
        if (!$this->canComment()) {
            Exception::salesOrderCanNotReview();
        }
        $this->setState(self::STATE_COMPLETE, self::STATUS_COMPLETE,'超市评价');
        return $this;
    }

    public function receiptConfirmPartial($productIds)
    {
        if (!$this->canReceiptConfirm()) {
            Exception::salesOrderCanNotReceiptConfirm();
        }
        $this->receipt = self::RECEIPT_PARTIAL;
        $items = $this->_getItemsCollection(['product_id' => $productIds], false);
        /** @var SalesFlatOrderItem $item */
        $this->receipt_total = 0;
        foreach ($items as $item) {
            $item->receipt = SalesFlatOrderItem::RECEIPT_YES;
            $this->receipt_total += $item->row_total;
            $this->_items[] = $item;
        }
        $this->setState(self::STATE_COMPLETE, self::STATUS_PENDING_COMMENT,'超市确认部分商品收货');
		$this->setCompletedAt();
        return $this;
    }

    public function canReceiptConfirm()
    {
        $receiptConfirmStatus = array(
            self::STATUS_PROCESSING_RECEIVE,
            self::STATUS_PROCESSING_SHIPPING,
            self::STATUS_HOLDED,
        );
        if (in_array($this->status, $receiptConfirmStatus)) {
            return true;
        }
        return false;
    }

    public function canComment()
    {
        $receiptConfirmStatus = array(
            self::STATUS_PENDING_COMMENT,
        );
        if (in_array($this->status, $receiptConfirmStatus)) {
            return true;
        }
        return false;
    }


    /**
     * 申请取消订单
     * @return $this
     */
    public function cancel()
    {
        $status = $this->status;
        $canCancelStatus = array(
            self::STATUS_PENDING,
            self::STATUS_PROCESSING
        );
        if (in_array($status, $canCancelStatus)) {
            $this->setState(self::STATE_CANCELED, self::STATUS_CANCELED, '超市取消订单');
			$this->setCompletedAt();
        } elseif ($status == self::STATUS_PROCESSING_RECEIVE) {
            $this->hold();
        } else {
            Exception::salesOrderCanNotCanceled();
        }
        return $this;
    }

    /**
     * 订单挂起
     * @return $this
     * @throws Exception
     */
    public function hold()
    {
        if (!$this->canHold()) {
            Exception::salesOrderCanNotUnHold();
        }
        $this->hold_before_state = $this->state;
        $this->hold_before_status = $this->status;
        $this->setState(self::STATE_HOLDED, self::STATUS_HOLDED);
        return $this;
    }

    /**
     * 订单是否可挂起
     *
     * @return bool
     */
    public function canHold()
    {
        $status = $this->status;
        if ($status == self::STATUS_PROCESSING_RECEIVE) {
            return true;
        }
        return false;
    }

    public function setCompletedAt(){
		$date = new Date();
		$time = $date->gmtDate();
		$this->complete_at = $time;
		return $this;
	}

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        /** @var SalesOrderStatus $status */
        $status = SalesOrderStatus::find()->where(['status' => $this->status])->one();
        return $status->label;
    }

    /**
     * @param bool|true $asArray
     * @return array|\framework\db\ActiveRecord[]
     */
    public function getItemsCollection($asArray = true)
    {
        return SalesFlatOrderItem::find()->where(['order_id' => $this->getPrimaryKey()])->asArray($asArray)->all();
    }

    /**
     * @param $where ['product_id'=>[1,2,3,4],'barcode'=>[1,2,3,4]]
     * @param bool|true $asArray
     * @return array|\framework\db\ActiveRecord[]
     */
    protected function _getItemsCollection($where, $asArray = true)
    {
        $query = SalesFlatOrderItem::find()->where(['order_id' => $this->getPrimaryKey()]);
        if (is_array($where) && count($where)) {
            $query->andWhere($where);
        }
        return $query->asArray($asArray)->all();
    }

    /**
     * @param $id
     * @param $field
     * @return $this
     */
    public static function loadFromMaster($id, $field = null)
    {
        if (is_null($field)) {
            $field = current(self::primaryKey());
        }
        return SalesFlatOrder::getDb()->useMaster(function ($db) use ($id, $field) {
            return SalesFlatOrder::find()->where([$field => $id])->one($db);
        });
    }


	/**
	 * 订单是否可执行确认订单操作
	 * @return bool
	 */
	public function canConfirm()
	{
		if ($this->status == self::STATUS_PROCESSING) {
			return true;
		}
		return false;
	}

	/**
	 * 确认订单，接单操作
	 * @return $this
	 */
	public function confirm()
	{
		if ($this->canConfirm()) {
			$this->setState(self::STATE_PROCESSING, self::STATUS_PROCESSING_RECEIVE);
		}
		return $this;
	}

	public function getAutoScriptTip(){
		/**
		 * 自动脚本提示
		 * 自动接单和自动同意申请(同意取消订单)
		 * 2016-03-29 13:03后，订单自动关闭
		 * 2016-03-29 13:03后，自动同意申请
		 */
		$date = new Date();
		$autoScriptTip = '';
		if($this->canConfirm()){
			$autoScriptTip = $date->date('Y-m-d H:i', $this->created_at.' +2 day').'后，订单自动关闭';
		}
		if($this->canUnhold()){
			$autoScriptTip = $date->date('Y-m-d H:i', $this->updated_at.' +2 day').'后，自动同意申请';
		}
		return $autoScriptTip;
	}


	public function getPromotions(){

		$promotions = isset($this->promotions)?unserialize($this->promotions):'';
		if(is_array($promotions) && count($promotions)){
			foreach ($promotions as $key => $promotion) {
				$promotion['text'] = str_replace("本单预计可返现", "本单需向超市返现", $promotion['text']);
				$promotion['description'] = str_replace("返现将在送货时为您现金结算，金额以实际送货单为准", "返现将在送货时现金结算，金额以实际送货单为准", $promotion['description']);
				$promotions[$key] = array_filter($promotion);
			}
		}
		return $promotions;
	}


}
