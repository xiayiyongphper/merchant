<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/9/6
 * Time: 11:39
 */

namespace service\resources\merchant\v1;


use common\models\SearchTraceRecord;
use service\resources\MerchantResourceAbstract;

/**
 * Class saveSearchTraceRecord
 * @package service\resources\merchant\v1
 */
class saveSearchTraceRecord extends MerchantResourceAbstract
{
    /**
     * @param string $data
     * @throws \Exception
     * @return mixed
     */
    public function run($data)
    {
        /** @var \service\message\merchant\SearchTraceRecord $request */
        $request = $this->request();
        $request->parseFromString($data);

        if (empty($request->getSearchId()) || empty($request->getCustomerId())) {
            throw new \Exception('缺少必要参数');
        }

        $searchTraceRecordModel = new SearchTraceRecord();
        switch ($request->getType()) {
            case SearchTraceRecord::TYPE_BEFORE_SEARCH:
                break;
            case SearchTraceRecord::TYPE_SEARCH:
                $searchTraceRecordModel->keyword = $request->getInfo();
                break;
            case SearchTraceRecord::TYPE_SEARCH_RESULT:
                if (($arr = explode('|', $request->getInfo())) && count($arr) == 2) {
                    $searchTraceRecordModel->page = (int)$arr[0];
                    $searchTraceRecordModel->ids = $arr[1];
                }
                break;
            case SearchTraceRecord::TYPE_PRODUCT_DETAIL:
            case SearchTraceRecord::TYPE_CART:
                $searchTraceRecordModel->ids = $request->getInfo();
                break;
            default:
                throw new \Exception('错误的参数type');
        }

        $searchTraceRecordModel->customer_id = $request->getCustomerId();
        $searchTraceRecordModel->search_id = $request->getCustomerId() . '_' . $request->getSearchId();
        $searchTraceRecordModel->type = $request->getType();
        if (!$searchTraceRecordModel->save()) {
            throw new \Exception('上报失败！');
        }
        return;
    }

    /**
     * @return \service\message\merchant\SearchTraceRecord
     */
    public static function request()
    {
        return new \service\message\merchant\SearchTraceRecord();
    }

    /**
     * @throws \Exception
     */
    public static function response()
    {
        throw new \Exception('unsupport method response()');
    }
}