<?php
namespace service\models\payment\tenpay;
use service\models\VarienObject;
use common\models\SalesFlatOrder;
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 14-12-26
 * Time: 下午1:35
 * @method $this setOrder($order)
 * @method SalesFlatOrder getOrder()
 */
class Wechat extends TenpayAbstract
{
    /**
     * characters
     */
    const CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     *tenpay partner id
     */
    const TENPAY_PARTNER_ID = 'payment/tenpay/partner_id';

    /**
     * tenpay partner key
     */
    const TENPAY_PARTNER_KEY = 'payment/tenpay/partner_key';

    /**
     * tenpay app id
     */
    const TENPAY_APP_ID = 'payment/tenpay/app_id';

    /**
     * tenpay app key
     */
    const TENPAY_APP_KEY = 'payment/tenpay/app_key';

    /**
     * tenpay app secret
     */
    const TENPAY_APP_SECRET = 'payment/tenpay/app_secret';

    /**
     * bank type
     */
    const TENPAY_BANK_TYPE = 'WX';

    /**
     * input charset
     */
    const TENPAY_INPUT_CHARSET = 'UTF-8';

    /**
     * sign method
     */
    const TENPAY_SIGN_METHOD = 'sha1';

    /**
     * notify url
     */
    const TENPAY_NOTIFY_URL = 'tenpay/notify';

    /**
     * access token url
     * @var string
     */
    protected $_accessTokenUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';

    /**
     * gen-pre-pay-url for generate prepay order
     * @var string
     */
    protected $_genPrePayUrl = 'https://api.weixin.qq.com/pay/genprepay?access_token=%s';

    const REFUND_URL = 'https://mch.tenpay.com/refundapi/gateway/refund.xml';

    const REFUND_QUERY_URL = 'https://gw.tenpay.com/gateway/normalrefundquery.xml';

    /**
     * @var timestamp
     */
    protected $_timestamp;

    /**
     * 32 bit random string
     * @var string
     */
    protected $_nonceStr;

    protected $_package;

    /**
     * @var Varien_Data_Collection
     */
    protected $_refundCollection;

    /**
     * @return array
     */
    protected function getBasicData()
    {
        return array(
            'bank_type' => self::TENPAY_BANK_TYPE,
            'body' => $this->getOrder()->getIncrementId(),
            'fee_type' => 1,
            'input_charset' => self::TENPAY_INPUT_CHARSET,
            'notify_url' => Mage::getUrl(self::TENPAY_NOTIFY_URL),
            'out_trade_no' => $this->getOrder()->getIncrementId(),
            'partner' => $this->getPartnerId(),
            'spbill_create_ip' => $_SERVER["SERVER_ADDR"],
            'total_fee' => $this->getOrder()->getGrandTotal() * 100,
        );
    }

    protected function getTimestamp()
    {
        if (!$this->_timestamp) {
            $this->_timestamp = time();
        }
        return $this->_timestamp;
    }

    /**
     * package detail
     * @return string
     */
    protected function preparePackage()
    {
        if (!$this->_package) {
            $array = array_merge($this->getBasicData(), array('key' => $this->getPartnerKey()));
            $result = array();
            foreach ($array as $key => $value) {
                $result[] = $key . '=' . $value;
            }
            $sign = strtoupper(md5(implode('&', $result)));
            $this->_package = http_build_query($this->getBasicData()) . '&sign=' . $sign;
        }
        return $this->_package;
    }


    protected function getAppSignature($package)
    {
        $array = array(
            'appid' => $this->getAppId(),
            'appkey' => $this->getAppKey(),
            'noncestr' => $this->getNonceStr(),
            'package' => $package,
            'timestamp' => $this->getTimestamp(),
            'traceid' => $this->getOrder()->getTraceid()
        );
        $stringArray = array();
        foreach ($array as $key => $val) {
            $stringArray[] = $key . "=" . $val;
        }
        $string = implode('&', $stringArray);
        switch (self::TENPAY_SIGN_METHOD) {
            case 'sha1':
                $signature = sha1($string);
                break;
            default:
                $signature = sha1($string);
        }
        return $signature;
    }

    /**
     * pay order
     */
    public function pay()
    {
        $return = json_decode($this->genPrePay(), true);
        $prePay = new VarienObject($return);
        if ($prePay->getErrcode() > 0) {
            throw new Exception($prePay->getErrmsg(), $prePay->getErrcode());
        }
        $signArray = array(
            'appid' => $this->getAppId(),
            'appkey' => $this->getAppKey(),
            'noncestr' => $this->getNonceStr(),
            'package' => "Sign=WXPay",
            'partnerid' => $this->getPartnerId(),
            'prepayid' => $prePay->getPrepayid(),
            'timestamp' => $this->getTimestamp(),
        );
        $newSignArray = array();
        foreach ($signArray as $key => $value) {
            $newSignArray[] = $key . '=' . $value;
        }
        $sign = sha1(implode('&', $newSignArray));
        $result = array(
            'prepay_id' => $prePay->getPrepayid(),
            'nonce_str' => $this->getNonceStr(),
            'time_stamp' => $this->getTimestamp(),
            'package' => "Sign=WXPay",
            'sign' => $sign,
        );
        return $result;
    }

    /**
     *
     * 退款状态：
     * 4，10：退款成功。
     * 3，5，6：退款失败。
     * 8，9，11：退款处理中。
     * 1，2：未确定，需要商户原退款单号重新发起。
     * 7：转入代发，退款到银行发现用户的卡作废或
     * 者冻结了，导致原路退款银行卡失败，资金回
     * 流到商户的现金帐号，需要商户人工干预，通
     * 过线下或者财付通转账的方式进行退款。
     *
     * @throws Exception
     */
    public function refund()
    {
        $transaction = $this->getOrder()->getTransactionCollection()->getFirstItem();
        $transactionId = $transaction->getTxnId();
        $data = $transaction->getAdditionalInformation();

        $merge = Mage::getResourceModel('sales/order_merge_collection');
        $merge->addFieldToFilter('order_id',$this->getOrder()->getEntityId())->getFirstItem();
        foreach ($merge as $item) {
            $_incrementId = $item->getIncrement_merge_id();
        }

        if(!empty($_incrementId))
        {
            $out_trade_no = $_incrementId;
        }else{
            $out_trade_no = $this->getOrder()->getIncrementId();
        }
        $params = array(
            'partner' => $this->getPartnerId(),
            'out_trade_no' => $out_trade_no,
            'transaction_id' => $transactionId,
            'out_refund_no' => time() . rand(1, 1000),
            'total_fee' => $data['total_fee'],
            'refund_fee' => $this->getOrder()->getTotalPaid(), 'op_user_id' => $this->getOrder()->getOpUserId(),
            'op_user_passwd' => $this->getOrder()->getOpUserPasswd()
        );
        ksort($params);
        $array = array();
        foreach ($params as $key => $value) {
            $array[] = $key . '=' . $value;
        }
        $string1 = implode('&', $array);
        $stringSignTemp = $string1 . '&key=' . $this->getHelper()->getPartnerKey();
        $signValue = strtoupper(md5($stringSignTemp));
        $string2 = http_build_query($params);
        $package = $string2 . '&sign=' . $signValue;
        $url = self::REFUND_URL . '?' . $package;
        //$this->log($url);
        $cert = Mage::getModuleDir('data', 'LE_Tenpay') . DS . '1226223001_20141223164400.pem';
        $ca = Mage::getModuleDir('data', 'LE_Tenpay') . DS . 'tenpay_ca_cert.crt';
        $this->log($cert);
        $result = $this->getHttpResponsePOST($url, $cert, $ca);
        $this->log($result);
        if ($result) {
            $xml = new DOMDocument();
            $xml->loadXML($result);
            $refundStatus = $xml->getElementsByTagName('refund_status');
            $refundStatus = $refundStatus->item(0)->nodeValue;
            $retCode = $xml->getElementsByTagName('retcode');
            $retCode = $retCode->item(0)->nodeValue;
            $transactionId = $xml->getElementsByTagName('transaction_id');
            $transactionId = $transactionId->item(0)->nodeValue;
            if ($retCode == 0 && $transactionId) {
                $flag = true;
                switch ($refundStatus) {
                    case 8:
                    case 9:
                    case 11:
                        $isClosed = 0;
                        $status = LE_Sales_Model_Order::STATUS_SUBMIT_REFUND;
                        break;
                    case 3:
                    case 5:
                    case 6:
                        $isClosed = 1;
                        $status = LE_Sales_Model_Order::STATUS_REFUND_FAILURE;
                        break;
                    case 4:
                    case 10:
                        $isClosed = 1;
                        $status = LE_Sales_Model_Order::STATUS_BUSY_REFUND;
                        break;
                    case 1:
                    case 2:
                        $flag = false;
                        Mage::log($result, null, 'unconfirmed.refund.log');
                        break;
                    case 7:
                        $flag = false;
                        Mage::log($result, null, 'forward.refund.log');
                        break;
                }
                if ($flag) {
                    $refundFee = $xml->getElementsByTagName('refund_fee');
                    $refundFee = $refundFee->item(0)->nodeValue;
                    //9已提交至银行
                    $order = $this->getOrder();
                    $order->setState(LE_Sales_Model_Order::STATE_REFUND, $status);
                    $paymentTransaction = Mage::getModel('sales/order_payment_transaction');
                    /* @var $paymentTransaction LE_Sales_Model_Order_Payment_Transaction */
                    $paymentTransaction->addData(array(
                        'payment_method' => LE_Payment_Model_Method::WECHAT,
                        'txn_id' => $transactionId,
                        'txn_type' => 'refund',
                        'total_fee' => number_format($refundFee / 100, 2),
                        'is_closed' => $isClosed,
                        'created_at' => time(),
                        'additional_information' => $result,
                    ));
                    $order->addTransaction($paymentTransaction);
                    $order->save();
                }
            }
        }
        $this->log($result);
    }

    public function refundQuery()
    {
        $transaction = $this->getOrder()->getTransactionCollection()->getFirstItem();
        $transactionId = $transaction->getTxnId();
        $params = array(
            'partner' => $this->getPartnerId(),
            'transaction_id' => $transactionId,
        );
        ksort($params);
        $array = array();
        foreach ($params as $key => $value) {
            $array[] = $key . '=' . $value;
        }
        $string1 = implode('&', $array);
        $stringSignTemp = $string1 . '&key=' . $this->getHelper()->getPartnerKey();
        $signValue = strtoupper(md5($stringSignTemp));
        $string2 = http_build_query($params);
        $package = $string2 . '&sign=' . $signValue;
        $url = self::REFUND_QUERY_URL . '?' . $package;
        $this->log($url);
        $result = $this->getHttpResponsePOST($url);
        $this->log($result);
        if ($result) {
            $xml = new DOMDocument();
            $xml->loadXML($result);
            $retCode = $xml->getElementsByTagName('retcode');
            $retCode = $retCode->item(0)->nodeValue;
            $transactionId = $xml->getElementsByTagName('transaction_id');
            $transactionId = $transactionId->item(0)->nodeValue;
            if ($retCode == 0 && $transactionId) {
                //多次退款逻辑，这里默认只有单次退款。统一只处理第一条退款信息，并且更新订单状态。
                $refundCount = $xml->getElementsByTagName('refund_count');
                $refundCount = $refundCount->item(0)->nodeValue;
                for ($i = 0; $i < $refundCount; $i++) {
                    $refundState = $xml->getElementsByTagName('refund_state_' . $i);
                    $refundState = $refundState->item(0)->nodeValue;
                    break;
                }
                $flag = true;
                switch ($refundState) {
                    case 3:
                    case 5:
                    case 6:
                        $status = LE_Sales_Model_Order::STATUS_REFUND_FAILURE;
                        break;
                    case 4:
                    case 10:
                        $status = LE_Sales_Model_Order::STATUS_BUSY_REFUND;
                        break;
                    case 8:
                    case 9:
                    case 11:
                    case 1:
                    case 2:
                    case 7:
                    default:
                        //退款状态未更改
                        $flag = false;
                        break;
                }
                if ($flag) {
                    $order = $this->getOrder();
                    $order->setState(LE_Sales_Model_Order::STATE_REFUND, $status);
                    $order->save();
                }
            }
        }
        return $result;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param string $url 指定URL完整路径地址
     * @param string $cert 指定当前工作目录绝对路径
     * @param string $ca 指定当前工作目录绝对路径
     * @return string 远程输出的数据
     */
    public function getHttpResponsePOST($url, $cert = null, $ca = null)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        if ($cert) {
            curl_setopt($curl, CURLOPT_SSLCERT, $cert);
            curl_setopt($curl, CURLOPT_SSLCERTPASSWD, '296006');
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
        }
        if ($ca) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_CAINFO, $ca);
        }
        $responseText = curl_exec($curl);
        if ($responseText == false) {
            Mage::log(curl_error($curl), null, 'refund.curl.log');
        }
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);
        return $responseText;
    }

    /**
     * @return LE_Tenpay_Helper_Data
     */
    protected function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('tenpay');
        }
        return $this->_helper;
    }

    /**
     * @return Varien_Data_Collection
     */
    protected function getRefundCollection()
    {
        return $this->_refundCollection;
    }

    public function addRefundItem($item)
    {
        if (!$this->_refundCollection) {
            $this->_refundCollection = new Varien_Data_Collection();
        }
        $this->_refundCollection->addItem($item);
        return $this;
    }

    /**
     * gen prepay order,retrieve prepay-id for mobile app to submit order.
     * @return mixed
     */
    protected function genPrePay()
    {
        $package = $this->preparePackage();
        $data = array(
            "appid" => $this->getAppId(),
            "traceid" => $this->getOrder()->getTraceid(),
            "noncestr" => $this->getNonceStr(),
            "package" => $package,
            "timestamp" => $this->getTimestamp(),
            "app_signature" => $this->getAppSignature($package),
            "sign_method" => self::TENPAY_SIGN_METHOD
        );
        return $this->sendRequest($this->getGenPrePayUrl(), $data);
    }

    /**
     * get prepay url
     * @return string
     */
    protected function getGenPrePayUrl()
    {
        return sprintf($this->_genPrePayUrl, $this->getAccessToken());
    }

    /**
     * get app secret
     * @return mixed
     */
    protected function getSecret()
    {
        return $this->getConfig(self::TENPAY_APP_SECRET, true);
    }

    /**
     * get partner id
     * @return mixed
     */
    protected function getPartnerId()
    {
        return $this->getConfig(self::TENPAY_PARTNER_ID, true);
    }

    /**
     * get partner key
     * @return mixed
     */
    protected function getPartnerKey()
    {
        return $this->getConfig(self::TENPAY_PARTNER_KEY, true);
    }

    /**
     * get app key
     * @return mixed
     */
    protected function getAppKey()
    {
        return $this->getConfig(self::TENPAY_APP_KEY, true);
    }

    /**
     * get app id
     * @return mixed
     */
    protected function getAppId()
    {
        return $this->getConfig(self::TENPAY_APP_ID, true);
    }

    /**
     * get access token
     */
    protected function getAccessToken()
    {
        $key = md5('tenpay_wechat_access_token');
        if (!$token = Mage::app()->loadCache($key)) {
            $url = $this->getAccessTokenUrl();
            $json = $this->sendRequest($url, null, 'GET');
            $json = json_decode($json, true);
            if (isset($json['errcode'])) {
                throw new Exception($json['errmsg'], $json['errcode']);
            }
            $token = $json['access_token'];
            Mage::app()->saveCache($token, $key, array('Tenpay', 'WeChat', 'Access_Token'), $json['expires_in']);
        }
        return $token;
    }

    /**
     * get a replaced access-token-url
     * @return string
     */
    protected function getAccessTokenUrl()
    {
        return sprintf($this->_accessTokenUrl, $this->getAppId(), $this->getSecret());
    }

    protected function getRamdomString($length = 10)
    {
        $characters = self::CHARACTERS;
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    protected function getNonceStr()
    {
        if (!$this->_nonceStr) {
            $this->_nonceStr = $this->getRamdomString(15);
        }
        return $this->_nonceStr;
    }

    /**
     * the user's unique identifier, if use WeChat SSO, recommended here authorized users openid
     */
    protected function getTraceId()
    {
        return uniqid();
    }
}
