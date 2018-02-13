<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use service\components\Tools;
use service\message\core\HomeRequest;
use service\message\merchant\RecommendResponse;
use service\models\homepage\homeConfig;
use service\resources\MerchantResourceAbstract;
use yii\db\Expression;

class getRecommend extends MerchantResourceAbstract
{
    public function run($data)
    {
        /** @var HomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $customer = $this->_initCustomer($request);

        $params = [
            'page_size' => 20
        ];
        $config = new homeConfig($customer,$this->getAppVersion(),true);
        $recommend_products = $config->getModuleData(homeConfig::MODULE_HOT_RECOMMEND,$params);
        $topic_blocks = $config->getModuleData(homeConfig::MODULE_TOPIC);

        $result = [];
        if(!empty($topic_blocks)){
            $result['topic_block'] = $topic_blocks[0];
        }
        if(!empty($recommend_products)){
            $result['product_block'] = [];
            if(isset($recommend_products['product_list'])){
                $result['product_block']['products'] = $recommend_products['product_list'];
            }
            if(isset($recommend_products['title_img'])){
                $result['product_block']['product_block_title_img'] = $recommend_products['title_img'];
            }
        }

//        Tools::log($result,'wangyang.log');
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new HomeRequest();
    }

    public static function response()
    {
        return new RecommendResponse();
    }
}