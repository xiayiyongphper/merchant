<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use common\models\CumulativeReturnActivity;
use service\components\Proxy;
use service\components\Tools;
use service\message\merchant\ButtomTabRedDotRequest;
use service\message\merchant\ButtomTabRedDotResponse;
use service\resources\MerchantResourceAbstract;

/**
 * 首页底部TAB红点
 *
 * Class getButtomTabRedDot
 * @package service\resources\merchant\v1
 */
class getButtomTabRedDot extends MerchantResourceAbstract
{
    /**
     * @param string $data
     * @return ButtomTabRedDotResponse
     */
    public function run($data)
    {
        /** @var ButtomTabRedDotRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        // 初始化
        $customer = $this->_initCustomer($request);

        $response = self::response();
        if (!$customer->getCity()) {
            return $response;
        }

        $type = 4;  // 类型， 1：我的，2：订单列表，3：累计满返活动详情，4：首页红点
        /** @var \service\message\sales\GetCumulativeReturnDetailResponse $proxyResp */
        $proxyResp = Proxy::getCumulativeReturnActivity($customer, $type);
        if (!$proxyResp || !$proxyResp->getLevels()) {
            return $response;
        }

        $response->setFrom(Tools::pb_array_filter([
            'mine' => 1,
        ]));
        return $response;
    }

    /**
     * @return ButtomTabRedDotRequest
     */
    public static function request()
    {
        return new ButtomTabRedDotRequest();
    }

    /**
     * @return ButtomTabRedDotResponse
     */
    public static function response()
    {
        return new ButtomTabRedDotResponse();
    }
}