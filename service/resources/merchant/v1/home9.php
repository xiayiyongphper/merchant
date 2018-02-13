<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/31
 * Time: 18:31
 */

namespace service\resources\merchant\v1;

use common\models\LeBanner;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use framework\components\Date;
use framework\components\mq\Merchant;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\core\HomeRequest;
use service\message\core\HomeResponse3;
use service\resources\MerchantResourceAbstract;
use yii\base\Exception;
use service\models\homepage\homeConfig;

class home9 extends MerchantResourceAbstract
{
    protected $_customer;
    protected $_areaId;
    protected $_cityId;
    protected $_wholesalerIds;
    protected $_customerBelongGroup = [];

    /**
     * @param \ProtocolBuffers\Message $data
     * @return HomeResponse3
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var HomeRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        //接口验证用户
        $customerResponse = $this->_initCustomer($request);
        $this->_customer = $customerResponse;
        $this->_areaId = $customerResponse->getAreaId();
        $this->_cityId = $customerResponse->getCity();
        //区域内店铺IDs
        $this->_wholesalerIds = $this->getWholesalerIdsByAreaId($this->_areaId);

        $response = $this->response();
        //无供应商时
        if (count($this->_wholesalerIds) == 0) {
            return $response;
        }

        $key = 'merchant_home_page_v9_' . $this->_areaId;
        $config = new homeConfig($this->_customer,$this->getAppVersion());
        $redis = ToolsAbstract::getRedis();
        if(false && $redis->exists($key)){
            $configData = unserialize($redis->get($key));
            $configData['topic_blocks'] = $config->getModuleData(homeConfig::MODULE_TOPIC);
            $configData['seckill_blocks'] = $config->getModuleData(homeConfig::MODULE_SECKILL);
        }else{
            $configData = $config->toArray();
            $configData['second_fixed_banner'] = $this->getHomeSecondBanner();
            $configData['camber_banner'] = $this->getHomeCamberBanner();
            $redis->set($key, serialize($configData), 3600);//1小时缓存过期
        }

        $configData['top_fixed_banner'] = $this->getHomeBanner();

        $response->setFrom(Tools::pb_array_filter($configData));

        // 推送到MQ
        Merchant::publishEnterHomePageEvent(['customer_id' => $this->_customer->getCustomerId()]);

        return $response;
    }

    /**
     * Author Jason Y. wang
     * app中top_banner下方的banner
     */
    protected function getHomeSecondBanner()
    {
        $date = new Date();
        $now = $date->gmtDate();
        // 返回的
        $banner = array();
        // 加上店铺banner逻辑
        $banners = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_home_second_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ]
        )->joinWith('areabanner')
            ->andWhere(['le_area_banner.area_id' => $this->_areaId])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->orderBy('sort desc');
        //Tools::log($banners->createCommand()->getRawSql(),'wangyang.log');
        $banners = $banners->asArray()->all();
        if (count($banners) > 0) {
            foreach ($banners as $item) {
                //传2.6专用图片
                if ($item['image']) {
                    $height = Tools::getImageHeightByUrl($item['image']);
                    if ($height) {
                        $addImg['height'] = $height;
                    }
                    $addImg['href'] = $item['url'];
                    $addImg['src'] = $item['image'];

                    array_unshift($banner, $addImg);
                }
            }
        }

        return $banner;
    }

    /**
     * Author Jason Y. wang
     * app中top_banner下方的banner
     */
    protected function getHomeCamberBanner()
    {
        $date = new Date();
        $now = $date->gmtDate();
        // 加上店铺banner逻辑
        /** @var LeBanner $banner */
        $banner = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_home_camber_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ]
        )->joinWith('areabanner')
            ->andWhere(['le_area_banner.area_id' => $this->_areaId])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->one();

        if ($banner) {
            $image = $banner->image;
        } else {
            $image = 'http://assets.lelai.com/images/files/merchant/20170119/source/0_20170119055632file.png?width=640&height=20';
        }

        $addImg = [
            'src' => $image,
        ];

        return $addImg;
    }

    /**
     * Author Jason Y. wang
     * APP首页BANNER
     */
    protected function getHomeBanner()
    {
        $date = new Date();
        $now = $date->date();
        // 返回的
        $banner = array();
        // 加上店铺banner逻辑
        $banners = LeBanner::find()->where(
            [
                'le_banner.position' => 'app_home_banner',
                'le_banner.status' => 1,
                'le_banner.type_code' => 'app',
            ]
        )->joinWith('areabanner')
            ->andWhere(['le_area_banner.area_id' => $this->_areaId])
            ->andWhere(['<=', 'start_date', $now])
            ->andWhere(['>=', 'end_date', $now])
            ->orderBy('sort desc');
        $banners = $banners->asArray()->all();
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
                //传2.6专用图片
                if ($item['image_big']) {
                    $addImg = [];
                    $height = Tools::getImageHeightByUrl($item['image_big']);
                    if ($height) {
                        $addImg['height'] = $height;
                    }
                    //根据版本选用链接
                    //Tools::log($item,'xxx.log');
                    if(isset($item['url'])){
                        if(!empty($item['url_backup']) && !empty($item['compare_type']) && !empty($item['version'])){
                            if(Tools::compareVersion($this->getAppVersion(),$item['version'],$item['compare_type'])){
                                $item['url'] = $item['url_backup'];
                            }
                        }
                    }else{
                        $item['url'] = '';
                    }

                    $addImg['href'] = $item['url'];
                    $addImg['src'] = $item['image_big'];
//                    $addImg = [
//                        'href' => $item['url'],
//                        'src' => $item['image_big'],
//                    ];
                    array_unshift($banner, $addImg);
                }
            }
        }

        if (empty($banner)) {
            $height = Tools::getImageHeightByUrl(self::$homeBannerDefault);
            $item = [
                'href' => '',
                'src' => self::$homeBannerDefault,
            ];

            if($height){
                $item['height'] = $height;
            }

            $banner = [$item];
        }

        return $banner;
    }

    public static function request()
    {
        return new HomeRequest();
    }

    public static function response()
    {
        return new HomeResponse3();
    }

}
