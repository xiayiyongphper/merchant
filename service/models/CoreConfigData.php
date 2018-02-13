<?php

namespace service\models;

use Yii;

/**
 * This is the model class for table "core_config_data".
 *
 * @property integer $config_id
 * @property string $scope
 * @property integer $scope_id
 * @property string $path
 * @property string $value
 */
class CoreConfigData extends \common\models\CoreConfigData
{
    static public function getLeLaiRebates()
    {
        $config = static::findOne(['path' => 'merchant_subsidies/rebates/rebates']);
        if($config){
            return floatval($config['value']);
        }else{
            return floatval(0);
        }
    }

    static public function getConfigByPath($path)
    {
        $config = static::findOne(['path' => $path]);
        return $config;
    }
}
