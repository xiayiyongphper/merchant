<?php
namespace service\resources\merchant\v1;

use service\message\merchant\tipsRequest;
use service\message\merchant\tipsResponse;
use service\resources\MerchantResourceAbstract;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-27
 * Time: 下午6:30
 */

/**
 * Class tips
 * @package service\resources\merchant\v1
 */
class tips extends MerchantResourceAbstract
{

    public function run($data)
    {
        /** @var tipsRequest $request */
        $request = new tipsRequest();
		$request->parseFromString($data);
        $response = self::response();
        $identifier = $request->getIdentifier();
        $format = $request->getFormat();
        $file = \Yii::getAlias('@service') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tips.json';
        $tipsArray = json_decode(file_get_contents($file), true);
        if (isset($tipsArray[$identifier]) && isset($tipsArray[$identifier][$format])) {
            $tips = $tipsArray[$identifier][$format];
            if (isset($tips['title']))
                $response->setTitle($tips['title']);
            if (isset($tips['content']))
                $response->setContent($tips['content']);
        }
        return $response;
    }

    public static function request()
    {
        return new tipsRequest();
    }

    public static function response()
    {
        return new tipsResponse();
    }
}