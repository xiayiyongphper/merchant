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
use service\message\merchant\TopicHomeRequest;
use service\message\merchant\TopicHomeResponse;
use service\models\homepage\topicConfig;
use service\resources\MerchantResourceAbstract;
use yii\base\Exception;

class topicHome extends MerchantResourceAbstract
{
    protected $_areaId;
    protected $_customerId;
    protected $_topicId;
    protected $_customerBelongGroup = [];

    /**
     * @param \ProtocolBuffers\Message $data
     * @return TopicHomeResponse
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var TopicHomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        //接口验证用户
        $customer = $this->_initCustomer($request);
        $this->_areaId = $customer->getAreaId();
        $this->_customerId = $customer->getCustomerId();
        $this->_topicId = $request->getTopicId();
        $this->_customerBelongGroup = Tools::getCustomerBelongGroup($this->_customerId);

        /* @var Topic $topic */
        $topic = Topic::findOne(['entity_id' => $this->_topicId]);
        if(empty($topic)){
            throw new \Exception("要查看的专区不存在",1000);
        }

        $response = $this->response();
        $config = new topicConfig($customer,$this->getAppVersion(),$this->_topicId);
        $configData = $config->toArray();

        $configData['title'] = $topic->title;
        $configData['top_fixed_banner'] = $this->getBanners();

        $response->setFrom(Tools::pb_array_filter($configData));

        return $response;
    }

    /**
     * Author ryan
     * 轮播图BANNER
     */
    protected function getBanners()
    {
        $date = new Date();
        $now = $date->date();
        // 返回的
        $banner = array();
        // 加上店铺banner逻辑
        $selectObj = LeBanner::find()
            ->where([
                'le_banner.position' => 'topic_top',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ])
            ->andWhere(['like', 'topic_id', '|' . $this->_topicId . '|'])
            ->joinWith('areabanner')
            ->andWhere(['le_area_banner.area_id' => $this->_areaId])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->orderBy('sort desc');
        Tools::log($selectObj->createCommand()->getRawSql(),'sql.log');
        $banners = $selectObj->asArray()->all();
        if (count($banners) > 0) {
            foreach ($banners as $item) {
                //banner判断是否属于该用户分群
                $group_to_show = $item['group_to_show'];
                $group_to_show = array_filter(explode('|', $group_to_show));
                //没有限制分群时  $group_to_show为空
                if (!empty($group_to_show)) {
                    if (empty(array_intersect($group_to_show, $this->_customerBelongGroup))) {
                        continue;
                    }
                }

                if ($item['image_big']) {
                    $height = Tools::getImageHeightByUrl($item['image_big']);
                    if ($height) {
                        $addImg['height'] = $height;
                    }
                    $addImg = [
                        'href' => $item['url'],
                        'src' => $item['image_big'],
                    ];
                    array_unshift($banner, $addImg);
                }
            }
        }

        return $banner;
    }

    public static function request()
    {
        return new TopicHomeRequest();
    }

    public static function response()
    {
        return new TopicHomeResponse();
    }

}
