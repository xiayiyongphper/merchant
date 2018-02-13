<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/4/18
 * Time: 13:35
 */

namespace service\resources\merchant\v1;

use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\pieceTogetherOrderAreaRequest;
use service\message\merchant\pieceTogetherOrderAreaResponse;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getPieceTogetherOrderArea.
 * @package service\resources\merchant\v1
 */
class getPieceTogetherOrderArea extends MerchantResourceAbstract
{
    public $result = [];

    /**
     * 凑单专区
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var pieceTogetherOrderAreaRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $response = $this->response();

        if ($request->getWholesalerId() == 0) {
            Exception::systemNotFound();
        }

        //分页设置
        $this->getProducts($customer, $request);


        $this->result['thematic'] = [
            [
                'key' => 1,
                'value' => '0-10元'
            ],
            [
                'key' => 2,
                'value' => '10-30元'
            ],
            [
                'key' => 3,
                'value' => '30-50元'
            ],
            [
                'key' => 4,
                'value' => '50元以上'
            ],
        ];


        $response->setFrom(Tools::pb_array_filter($this->result));
        return $response;
    }

    /**
     * @param pieceTogetherOrderAreaRequest $request
     */
    public function getProducts($customer, $request)
    {
        $elasticSearch = new ElasticSearchExt($customer);
        $this->result = $elasticSearch->getPieceTogetherOrderAreaProducts($request);
    }

    public static function request()
    {
        return new pieceTogetherOrderAreaRequest();
    }

    public static function response()
    {
        return new pieceTogetherOrderAreaResponse();
    }

}