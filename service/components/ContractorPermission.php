<?php
/**
 * Created by PhpStorm.
 * User: Jason Y. wang
 * Date: 16-12-28
 * Time: 下午5:43
 */

namespace service\components;


class ContractorPermission
{
    //业务员首页今日业绩
    const CONTRACTOR_MERCHANT_COLLECTION = 'store/contractor-merchant-collection';


    public static function contractorMerchantCollectionPermission($role_permission){
        if(is_array($role_permission)){
            if(in_array('*',$role_permission) || in_array(self::CONTRACTOR_MERCHANT_COLLECTION,$role_permission)){
                return true;
            }
        }
        return false;
    }

}