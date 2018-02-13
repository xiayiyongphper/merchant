<?php
/**
 *
 */

namespace service\resources\merchant\v1;

use common\models\CustomThematicActivity;
use common\models\CustomThematicActivitySubProduct;
use common\models\Products;
use framework\data\Pagination;
use service\components\Tools;
use service\message\merchant\customThematicProductRequest;
use service\message\merchant\customThematicProductResponse;
use service\models\ProductHelper;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class getCustomThematicProduct
 * @package service\resources\merchant\v1
 */
class getCustomThematicProduct extends MerchantResourceAbstract
{

    const PAGE_SIZE = 20;

    /**
     * 获取板块商品页面  2.9版本新增
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var customThematicProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);

        $thematic_id = $request->getCustomThematicId();
        $thematic_sub_id = $request->getCustomThematicSubId();
        $city = $customer->getCity();

        //分页设置
        $pageNation = $request->getPagination();
        //区域内店铺IDs
        $wholesalerIds = $this->getWholesalerIdsByAreaId($customer->getAreaId());
        if ($pageNation) {
            $page_num = $pageNation->getPage() ?: 1;
            $page_size = $pageNation->getPageSize() ?: self::PAGE_SIZE;
        } else {
            $page_num = 1;
            $page_size = self::PAGE_SIZE;
        }

        /** @var CustomThematicActivity $thematic_activity */
        $thematic_activity = CustomThematicActivity::find()->where(['entity_id' => $thematic_id])->one();
        if(!$thematic_activity){
            Exception::topicNotExist();
        }
        $type = $thematic_activity->type;
        $condition = [];
        switch ($type) {
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_ONE: //不使用tab分组
                $condition = ['thematic_id' => $thematic_id, 'wholesaler_id' => $wholesalerIds];
                break;
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_TWO: //按供应商分组
                $condition = ['thematic_id' => $thematic_id, 'wholesaler_id' => $thematic_sub_id];
                break;
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_THREE: //按分类分组
                $condition = ['thematic_id' => $thematic_id, 'p.first_category_id' => $thematic_sub_id, 'wholesaler_id' => $wholesalerIds];
                break;
            case CustomThematicActivity::CUSTOM_THEMATIC_TYPE_FOUR: //自定义分组
                $condition = ['sub_id' => $thematic_sub_id, 'wholesaler_id' => $wholesalerIds];
                break;
            default:
                Exception::systemNotSupport();
                break;
        }

        $customThematicProducts = CustomThematicActivitySubProduct::find()->alias('t')
            ->select('p.*')
            ->leftJoin('lelai_booking_product_a.products_city_' . $city . ' as p', 't.product_id = p.entity_id')
            ->where($condition)
            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
            ->groupBy('entity_id')->orderBy('t.entity_id asc');

        //分页
        $total_count = $customThematicProducts->count();
        $pages = new Pagination(['totalCount' => $total_count]);
        $pages->setCurPage($page_num);
        $pages->setPageSize($page_size);
        //商品
        $product_array = $customThematicProducts->offset(($page_num - 1) * $page_size)->limit($page_size)->asArray()->all();

        $products = (new ProductHelper())->initWithProductArray($product_array,$city)
            ->getTags()
            ->getData();

        $result['pagination'] = [
            'total_count' => $pages->getTotalCount(),
            'page' => $pages->getCurPage(),
            'last_page' => $pages->getLastPageNumber(),
            'page_size' => $pages->getPageSize(),
        ];

        $result['products'] = $products;
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($result));

        return $response;
    }

    public static function request()
    {
        return new customThematicProductRequest();
    }

    public static function response()
    {
        return new customThematicProductResponse();
    }

}