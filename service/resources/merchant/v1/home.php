<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */
namespace service\resources\merchant\v1;

use service\components\Proxy;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\core\HomeRequest;
use service\message\core\HomeResponse;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\message\merchant\getStoresByAreaIdsResponse;
use service\models\HomePageConfig;
use service\resources\MerchantResourceAbstract;

class home extends MerchantResourceAbstract
{
    /**
     * @param \ProtocolBuffers\Message $data
     * @return HomeResponse
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var HomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customerResponse = $this->_initCustomer($request);
        //接口验证用户
        $response = $this->response();
        $areaId = $customerResponse->getAreaId();
        $city = $customerResponse->getCity();
        //区域内店铺IDs
        $wholesalerIds = $this->getWholesalerIdsByAreaId($areaId);
        $homepage = new HomePageConfig($areaId, $city, $wholesalerIds, $this->isRemote());
        $data = $homepage->toArray();
        $response->setFrom(Tools::pb_array_filter($data));
        return $response;
    }

    public static function request()
    {
        return new HomeRequest();
    }

    public static function response()
    {
        return new HomeResponse();
    }

    function array_remove_empty($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->array_remove_empty($haystack[$key]);
            }

            if (is_null($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

}