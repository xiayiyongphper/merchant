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
use service\components\Redis;
use service\components\Tools;
use service\message\common\CategoryNode;
use service\message\merchant\getAreaCategoryRequest;
use service\resources\MerchantResourceAbstract;
use framework\db\ActiveRecord;


class getAreaCategory extends MerchantResourceAbstract
{
	public function run($data)
	{
		/** @var getAreaCategoryRequest $request */
		$request = $this->request();
		$request->parseFromString($data);
        //Tools::log($request,'wangyang.log');
		$customer = $this->_initCustomer($request);

		// 组装查询条件
		$condition = [];
		// 商家id
		if ($request->getWholesalerId()) {
			$condition['wholesaler_id'] = $request->getWholesalerId();
		} else {
			// 否则就查该区域的商家id
			$condition['wholesaler_id'] = $this->getWholesalerIdsByAreaId($customer->getAreaId());
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
		$productList = $productModel->find()
			->select(['third_category_id', 'second_category_id', 'first_category_id'])
			->where($condition)
			->groupBy(['third_category_id', 'second_category_id', 'first_category_id'])
			->asArray()
			->all();

		$pmsCategories = Tools::getCategoryByProducts($productList);
		//分类排序
		//$_categories = [];
        $ids = array_reverse($this->ids);
		foreach ($ids as $id){
			foreach ($pmsCategories['child_category'] as $key => $pmsCate){
				if($pmsCate['id'] == $id){
				    $tmp = $pmsCate;
                    unset($pmsCategories['child_category'][$key]);
                    //先group，在order  分类品牌
                    $productBrands = $productModel->find()->leftJoin(['brand' => Brand::tableName()],'brand.name = brand')
                        ->select('brand.entity_id as brand_id,brand as name,brand.icon')
                        ->where($condition)->andWhere(['first_category_id' => $id])
                        ->andWhere(['is not','brand.name',null])
                        ->groupBy('brand')->orderBy('brand.sort desc')
                        ->limit(3);
//                    if($id == 80){
//                        Tools::log($productBrands->createCommand()->getRawSql(),'wangyang.log');
//                    }
                     $productBrands = $productBrands->asArray()->all();
                    //Tools::log($productBrands,'wangyang.log');
                    $tmp['brands'] = $productBrands;
					array_unshift($pmsCategories['child_category'],$tmp);
				}
			}
		}

		$pmsCategory = [
			'id'=>1,
			'parent_id'=>0,
			'name'=>'Root',
			'path'=>'1',
			'level'=>'0',
			'child_category'=>$pmsCategories['child_category'],
		];

		//Tools::log($pmsCategory,'wangyang.log');
		
		$response = $this->response();

		//$pmsCategory = [
		//	'id'=>1,
		//	'parent_id'=>0,
		//	'name'=>'Root',
		//	'path'=>'1',
		//];

		if (count($pmsCategory)) {
			$response->setFrom(Tools::pb_array_filter($pmsCategory));

		} else {
			throw new \Exception('未找到品牌', 4601);
		}

		return $response;
	}

	public static function request()
	{
		return new getAreaCategoryRequest();
	}

	public static function response()
	{
		return new CategoryNode();
	}
}