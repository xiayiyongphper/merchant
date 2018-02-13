<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use common\models\LeBanner;
use common\models\Products;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\Tools;
use service\message\merchant\searchProductRequest;
use service\message\merchant\searchProductResponse;
use service\models\ProductHelper;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;
use service\models\homepage\homeConfig;
use service\models\homepage\storeHomeConfig;
use service\models\homepage\topicConfig;
use service\models\homepage\classPageConfig;
use service\models\homepage\config;


class getMostFavourableProduct extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var searchProductRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $customer = $this->_initCustomer($request);
        $wholesaler_id = $request->getWholesalerId();
        $topic_id = $request->getTopicId();
        $class_page_id = $request->getClassPageId();

        //分页设置
        $page = $request->getPage() ?: 1;
        $pageSize = $request->getPageSize() ?: 30;

        $params = [
            'page' => $page,
            'page_size' => $pageSize
        ];
        //ToolsAbstract::log($home_page_type,'xxx.log');

        if($topic_id > 0){
            $config = new topicConfig($customer,$this->getAppVersion(),$topic_id);
        }elseif($wholesaler_id > 0){
            $config = new storeHomeConfig($customer,$this->getAppVersion(),$wholesaler_id);
        }elseif ($class_page_id > 0){
            $config = new classPageConfig($customer,$this->getAppVersion(),$class_page_id);
        }else{
            $config = new homeConfig($customer,$this->getAppVersion());
        }
        $result = $config->getModuleData(config::MODULE_HOT_RECOMMEND,$params);

//        //子查询 过滤没有标签的商品
//        $productModel_one = new Products($customer->getCity());
//        $where = 'label1&'.self::PRODUCT_FAVOURABLE_TAG.'=1';
//        $condition = new Expression($where);
//        $finalPrice = "IF(special_price > 0 and special_from_date < '$now' and special_to_date>'$now',special_price,price) as final_price";
//        $select = new Expression($finalPrice);
//        $productModel_one = $productModel_one->find()->select(['entity_id','barcode','package_num',$select])->where($condition)
//            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED]);
//        if($wholesaler_id > 0){
//            $productModel_one = $productModel_one->andWhere(['wholesaler_id' => $wholesaler_id]);
//            $title_img_identifier = 'most_favourable_product_wholesaler_title_img';
//        }else{
//            $wholesaler_ids = self::getWholesalerIdsByAreaId($customer->getAreaId());
//            $productModel_one = $productModel_one->andWhere(['wholesaler_id' => $wholesaler_ids]);
//            $title_img_identifier = 'most_favourable_product_home_title_img';
//        }
//
//        //子查询，按条码和打包数量分组，获取每组价格最低的entity_id
//        $productModel_two = new Products($customer->getCity());
//        $select_entity_id = new Expression("cast(SUBSTRING_INDEX(group_concat(entity_id order by `final_price` asc),',',1) as signed) as entity_id");
//        $productModel_two = $productModel_two->find()->select([$select_entity_id])->from(['one' => $productModel_one])->groupBy(['barcode','package_num']);
//
//        //按entity_id集合查出最后的结果
//        $productModel_three = new Products($customer->getCity());
//        $productModel_three = $productModel_three->find()->select(['*',$select])->where(['in','entity_id',$productModel_two])->orderBy('most_favorable_sort asc');
//
//        $pages = new Pagination(['totalCount' => $productModel_three->count()]);
//        $pages->setCurPage($page);
//        $pages->setPageSize($pageSize);
//        $pagination = $pages;
//
//        $productArray = $productModel_three->offset(($page-1)*$pageSize)->limit($pageSize);
//        Tools::log($productArray->createCommand()->getRawSql(),'hl.log');
//        $productArray = $productArray->asArray()->all();
//
//        $products = (new ProductHelper())->initWithProductArray($productArray,$customer->getCity())
//            ->getTags()->getData();
//
//        $result['product_list'] = $products;
//
//        // 加上店铺banner逻辑
//        $banner = LeBanner::find()->where(
//            [
//                'le_banner.position' => $title_img_identifier,
//            ]
//        )->joinWith('areabanner')->asArray()->one();
//        if($banner){
//            $title_img = [
//                'src' => $banner['image'],
//            ];
//            $result['title_img'] = $title_img;
//        }
//
//        if($pagination){
//            $result['pages'] =  [
//                'total_count' => $pagination->getTotalCount(),
//                'page'        => $pagination->getCurPage(),
//                'last_page'   => $pagination->getLastPageNumber(),
//                'page_size'   => $pagination->getPageSize(),
//            ];
//        }

//        Tools::log($result,'wangyang.log');
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new searchProductRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}