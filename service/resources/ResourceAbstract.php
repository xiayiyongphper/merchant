<?php
namespace service\resources;
use framework\resources\ApiAbstract;
use service\components\Tools;
use service\components\Proxy;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ContractorResponse;
use service\message\customer\CustomerAuthenticationRequest;
use service\message\customer\CustomerResponse;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:10
 */
abstract class ResourceAbstract extends ApiAbstract
{

    /**
     * @param \framework\protocolbuffers\Message $data
     *
     * @return CustomerResponse
     * @throws \Exception
     */
    protected function _initCustomer($data)
    {

        $request = new CustomerAuthenticationRequest();
        $request->setAuthToken($data->getAuthToken());
        $request->setCustomerId($data->getCustomerId());
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('customers.customerAuthentication');
        $header->setTraceId($this->getTraceId());
        $timeStart = microtime(true);
        $message = Proxy::sendRequest($header, $request);
        $timeEnd = microtime(true);
        $response = new CustomerResponse();
		$response->parseFromString($message->getPackageBody());

        $elapsed = $timeEnd - $timeStart;
        //Tools::log("Time ".$elapsed);
        return $response;
    }

    /**
     * @param $contractor_id
     * @param $auth_token
     * Author Jason Y. wang
     *
     * @return ContractorResponse
     */
    protected function _initContractor($contractor_id,$auth_token)
    {

        $request = new ContractorAuthenticationRequest();
        $request->setAuthToken($auth_token);
        $request->setContractorId($contractor_id);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('contractor.contractorAuthentication');
        $header->setTraceId($this->getTraceId());
        $timeStart = microtime(true);
        $message = Proxy::sendRequest($header, $request);
        $timeEnd = microtime(true);
        $response = new ContractorResponse();
		$response->parseFromString($message->getPackageBody());

        $elapsed = $timeEnd - $timeStart;
        //Tools::log("Time ".$elapsed);
        return $response;
    }

    public function init()
    {
        $this->initEvents();
        parent::init();
    }

    public function initEvents()
    {
        if (isset(\Yii::$app->params['events'])) {
            $events = \Yii::$app->params['events'];
            foreach ($events as $eventName => $observers) {
                foreach ($observers as $observerKey => $observer) {
                    $class = $observer['class'];
                    $method = $observer['method'];
                    $this->on($eventName, [$class, $method]);
                }
            }
        } else {
            Tools::log('param events not found in config');
        }
    }
}
