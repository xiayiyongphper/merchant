<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/31
 * Time: 18:31
 */

namespace service\resources\merchant\v1;

use common\models\LeBanner;
use common\models\Topic;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\merchant\ClassPageHomeRequest;
use service\message\merchant\ClassPageHomeResponse;
use service\models\homepage\classPageConfig;
use service\resources\MerchantResourceAbstract;
use yii\base\Exception;

class classPageHome extends MerchantResourceAbstract
{
    protected $_customer;
    protected $_areaId;
    protected $_cityId;
    protected $_classPageId;

    /**
     * @param \ProtocolBuffers\Message $data
     * @return ClassPageHomeResponse
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var ClassPageHomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        //接口验证用户
        $customer = $this->_initCustomer($request);
        $this->_classPageId = $request->getClassPageId();

        $response = $this->response();
        $config = new classPageConfig($customer,$this->getAppVersion(),$this->_classPageId);
        $result['home_page_config'] = $config->toArray();

        $response->setFrom(Tools::pb_array_filter($result));

        return $response;
    }

    public static function request()
    {
        return new ClassPageHomeRequest();
    }

    public static function response()
    {
        return new ClassPageHomeResponse();
    }

}
