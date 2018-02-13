<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use common\models\Brand;
use common\models\Products;
use service\components\Tools;
use service\message\merchant\getAreaBrandRequest;
use service\message\merchant\getAreaBrandResponse;
use service\resources\MerchantResourceAbstract;
use framework\db\ActiveRecord;


class getAreaBrand extends MerchantResourceAbstract
{
	public function run($data)
	{
		/** @var getAreaBrandRequest $request */
		$request = $this->request();
		$request->parseFromString($data);

		$customer = $this->_initCustomer($request);
//		$redis = Tools::getRedis();
//		$redisKey = sprintf('getAreaBrand_%s',$customer->getAreaId());
//		if($redis->exists($redisKey)){
//			$data = $redis->get($redisKey);
//			$response = $this->response()->parseFromString($data);
//			return $response;
//		}

		// 组装查询条件
		$condition = [];
		// 商家id
		if ($request->getWholesalerId()) {
			$condition['wholesaler_id'] = $request->getWholesalerId();
		} else {
			// 否则就查该区域的商家id
			$condition['wholesaler_id'] = $this->getWholesalerIdsByAreaId($customer->getAreaId());
		}

		// 分类
		$categoryId = $request->getCategoryId();
		$categoryLevel = $request->getCategoryLevel();
		if ($categoryId) {
			switch ($categoryLevel) {
				case 1:
					$condition['first_category_id'] = $categoryId;
					break;
				case 2:
					$condition['second_category_id'] = $categoryId;
					break;
				case 3:
					$condition['third_category_id'] = $categoryId;
					break;
				default :
					$condition['third_category_id'] = $categoryId;
					break;
			}
		}

		// 商品的必要条件
		$condition['state'] = 2;//通过审核
		$condition['status'] = 1;//上架
		$condition = ['and', $condition,
			['not', ['brand' => null]]// 品牌不为空
		];
		$condition = ['and', $condition,
			['not', ['brand' => '']]// 品牌不为空
		];

		/** @var ActiveRecord $productModel */
		$productModel = new Products($customer->getCity());
		//$productModel = new Products('440300');
//		$productList = $productModel->find()
//			->select('distinct(brand)')
//			->where($condition)
//			->all();

        $brands = $productModel->find()->leftJoin(['brand' => Brand::tableName()],'brand.name = brand')
            ->select('distinct(brand)')
            ->where($condition)
            ->orderBy('brand.sort desc')->column();

        $response = $this->response();
		if (count($brands)) {
			$response->setFrom(Tools::pb_array_filter([
				'brand_list' => $brands,
			]));

		} else {
			throw new \Exception('未找到品牌', 4601);
		}
//		if(count($response->getBrandList())>0){
//			$redis->set($redisKey,$response->serializeToString(),600);
//		}
		return $response;
	}

	public static function request()
	{
		return new getAreaBrandRequest();
	}

	public static function response()
	{
		return new getAreaBrandResponse();
	}
}