<?php
namespace service\models\payment\alipay;
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 14-12-26
 * Time: 下午1:35
 * @method $this setOrder($order)
 * @method LE_Sales_Model_Order getOrder()
 */
class Express extends AlipayAbstract
{
    /**
     * partner id which start with 2088
     * @var string
     */
    protected $_partnerId;

    /**
     * 签名方式 不需修改
     */
    const SIGN_TYPE = 'RSA';
    const REFUND_SIGN_TYPE = 'MD5';

    /**
     * 字符编码格式 目前支持 gbk 或 utf-8
     * @var string
     */
    const INPUT_CHARSET = 'utf-8';

    const SERVICE = 'mobile.securitypay.pay';

    const PAYMENT_TYPE = 1;

    /**
     * notify url
     */
    const NOTIFY_URL = 'alipay/notify';

    /**
     * notify url
     */
    const NOTIFY_REFUND_URL = 'alipay/notify/refund';

    const REFUND_URL = 'https://mapi.alipay.com/gateway.do?';

    /**
     * show url
     */
    const SHOW_URL = 'm.alipay.com';

    /**
     * it-b-pay
     */
    const IT_B_PAY = '30m';

    const PAYMENT_METHOD_VALUE = 2;
    const PAYMENT_METHOD_CODE = 'alipay_express';

    /**
     * @var Mage_Core_Helper_Abstract
     */
    protected $_helper;
    /**
     * @var Varien_Data_Collection
     */
    protected $_refundCollection;
    /**
     * @param array $data
     * @return array|string
     */
    protected function packString($data = array())
    {
        $array = array(
            'partner' => $this->getHelper()->getPartnerId(),
            'seller_id' => $this->getHelper()->getSellerId(),
            'out_trade_no' => $this->getOrder()->getIncrementId(),
            'subject' => $this->getOrder()->getIncrementId(),
            'body' => $this->getOrder()->getIncrementId(),
            'total_fee' => (float)$this->getOrder()->getGrandTotal(),
            'notify_url' => Mage::getUrl(self::NOTIFY_URL),
            'service' => self::SERVICE,
            'payment_type' => self::PAYMENT_TYPE,
            '_input_charset' => self::INPUT_CHARSET,
            'it_b_pay' => self::IT_B_PAY,
            'show_url' => self::SHOW_URL
        );
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $array[$key] = $value;
            }
        }
        $_array = array();
        foreach ($array as $_key => $_value) {
            $_array[] = "$_key=\"$_value\"";
        }
        return implode('&', $_array);
    }

    public function pay()
    {
        return array('order_str' => $this->packString(array('sign' => urlencode($this->getHelper()->rsaSign($this->packString(), $this->getHelper()->getPrivateKey())), 'sign_type' => self::SIGN_TYPE)));
    }

    public function refund()
    {
        $collection = $this->getRefundCollection();
        $url = '';
        if($collection->count()){
            $detailDataArray = array();
            foreach($collection as $order){
                /* @var $order LE_Sales_Model_Order */
                $transactions = $order->getTransactionCollection();
                $transaction = $transactions->getFirstItem();
                if($transaction){
                    $data = $transaction->getAdditionalInformation();
                    $detailDataArray[] = implode('^',array($data['trade_no'],sprintf("%.2f", $order->getTotalPaid()),'协商退款'));
                }
            }

            if(count($detailDataArray)){
                $data = array(
                    'service' => 'refund_fastpay_by_platform_pwd',
                    'partner' => $this->getHelper()->getPartnerId(),
                    '_input_charset' => self::INPUT_CHARSET,
                    'return_url' => Mage::getUrl(self::NOTIFY_REFUND_URL),
                    'batch_no' => sprintf('%s%s',date('Ymd'),uniqid()),
                    'batch_num' => count($detailDataArray),
                    'seller_email' => $data['seller_email'],
                    'seller_user_id' => $data['seller_id'],
                    'detail_data' => implode('#',$detailDataArray),
                    'refund_date' => date('Y-m-d H:i:s')
                );
                ksort($data);
                $data['sign']= $this->getHelper()->buildRequestMysign($data);
                $data['sign_type']= self::REFUND_SIGN_TYPE;
                $url = self::REFUND_URL.http_build_query($data);
            }
        }
        return $url;
    }

    /**
     * @return Varien_Data_Collection
     */
    protected function getRefundCollection(){
        return $this->_refundCollection;
    }

    public function addRefundItem($item){
        if(!$this->_refundCollection){
            $this->_refundCollection = new Varien_Data_Collection();
        }
        $this->_refundCollection->addItem($item);
        return $this;
    }

    protected function getTransaction(){
        $transactions = $this->getOrder()->getTransactionCollection();
        return $transactions->getFirstItem();
    }

    /**
     * @return LE_Alipay_Helper_Data
     */
    protected function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('alipay');
        }

        return $this->_helper;
    }
}
