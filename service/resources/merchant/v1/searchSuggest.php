<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\merchant\v1;

use framework\message\Message;
use service\components\search\ElasticSearch;
use service\components\search\SphinxSearch;
use service\components\Tools;
use service\message\merchant\searchSuggestRequest;
use service\message\merchant\searchSuggestResponse;
use service\resources\MerchantResourceAbstract;


class searchSuggest extends MerchantResourceAbstract
{

    /**
     * Function: run
     * Author: Jason Y. Wang
     * sphinx搜索建议
     * @param Message $data
     * @return null|searchSuggestResponse
     */
    public function run($data)
    {
        /** @var searchSuggestRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        //Tools::log($request,'wangyang.log');
//        $customer = $this->_initCustomer($request);
//        $wholesaler_id = $request->getWholesalerId();
//        $suggest = ElasticSearch::suggest($customer, $request->getKeyword(),$wholesaler_id);

        $suggest = \common\models\SearchSuggest::find()->select('word')
            ->where(['like', 'word', $request->getKeyword() . '%', false])
            ->limit(20)
            ->column();
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter(['suggest' => $suggest]));
        return $response;

    }

    public static function request()
    {
        return new searchSuggestRequest();
    }

    public static function response()
    {
        return new searchSuggestResponse();
    }
}