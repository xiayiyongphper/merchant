<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 25/1/2016
 * Time: 11:19 AM
 */
namespace service\resources\merchant\v1;

use common\models\Products;
use service\components\Tools;
use service\message\merchant\getCategoryStoresRequest;
use service\message\merchant\getCategoryStoresResponse;
use service\resources\MerchantResourceAbstract;
use framework\db\ActiveRecord;


class getCategoryStores extends MerchantResourceAbstract
{
	public function run($data)
	{
		/** @var getCategoryStoresRequest $request */
		$request = $this->request();
		$request->parseFromString($data);
		$response = $this->response();

		$customer = $this->_initCustomer($request);

		$areaId = $customer->getAreaId();
		$categoryId = $request->getCategoryId();
		$categoryLevel = $request->getCategoryLevel();
		switch ($categoryLevel){
			case 1:
				$condition = [
					'first_category_id' => $categoryId,
				];
				break;
			case 2:
				$condition = [
					'second_category_id' => $categoryId,
				];
				break;
			case 3:
				$condition = [
					'third_category_id' => $categoryId,
				];
				break;
			default:
				$condition = [
					'first_category_id' => $categoryId,
				];
				break;
		}

		/** @var ActiveRecord $productModel */
		$productModel = new Products($customer->getCity());
        $wholesalerIds = $productModel->find()
			->select(['wholesaler_id'])
            ->where($condition)
            ->andWhere(['status' => Products::STATUS_ENABLED, 'state' => Products::STATE_APPROVED])
            ->groupBy(['wholesaler_id']);

        //Tools::log($wholesalerIds->createCommand()->getRawSql(),'wangyang.log');
        $wholesalerIds = $wholesalerIds->column();

		$merchants = [];
		if(count($wholesalerIds)){
			$wholesalerAreaIds = $this->getWholesalerIdsByAreaId($areaId);
			//得到这个区域内有该分类的店铺列表
			$merchantList = array_intersect($wholesalerIds,$wholesalerAreaIds);

			$merchants = self::getStoreDetailBrief($merchantList,$areaId,'sort desc');
		}
		$data = [
			'wholesaler_list' => $merchants,
		];
		$response->setFrom(Tools::pb_array_filter($data));
		return $response;
	}

	public static function request()
	{
		return new getCategoryStoresRequest();
	}

	public static function response()
	{
		return new getCategoryStoresResponse();
	}
}