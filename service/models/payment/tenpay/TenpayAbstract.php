<?php
namespace service\models\payment\tenpay;
use service\components\Tools;
use service\models\payment\PaymentInterface;
use service\models\VarienObject;

/**
 * Created by PhpStorm.
 * User: henry
 * Date: 14-12-26
 * Time: 下午1:38
 */
abstract class TenpayAbstract extends VarienObject implements PaymentInterface
{
    protected $_logFile = 'tenpay.log';

    /**
     * @param $message
     */
    protected function log($message)
    {
        Tools::log($message, $this->_logFile);
    }

    /**
     * @param $e
     */
    protected function logException($e)
    {
        Tools::logException($e);
    }

    protected function sendRequest($url, $data = array(), $action = 'POST')
    {
        $this->log(__METHOD__);
        $this->log($url);
        $this->log($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        switch ($action) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        $output = curl_exec($ch);
        if ($output == false) {
            $this->logException(new LE_Tenpay_Model_Exception(curl_error($ch)));
        } else {
            $this->log($output);
        }
        return $output;
    }

    protected function getConfig($path, $throwExceptionIfNull = false, $storeId = null)
    {
        $value = Mage::getStoreConfig($path, $storeId);
        if ($throwExceptionIfNull && is_null($value)) {
            throw new LE_Tenpay_Model_Exception(sprintf('Config path:%s value is null', $path));
        }
        return $value;
    }
}