<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\merchant\v1;

use service\components\Redis;
use service\components\Tools;
use service\message\common\CategoryNode;
use service\message\core\getCategoryRequest;
use service\resources\Exception;
use service\resources\MerchantResourceAbstract;

/**
 * Class getFirstCategoryFromPms
 * @package service\resources\merchant\v1
 * 司机端在使用
 */
class getFirstCategoryFromPms extends MerchantResourceAbstract
{
	public function run($data)
	{
	    $category_ids = [80,103,31,127,2,269,213,161,413];
		//分类展示顺序
		$categories = Redis::getCategories($category_ids);

		if(count($categories) == 0){
			Exception::resourceNotFound();
		}

		// 加icon过滤,只要1级分类
		$res = array();
        //Tools::log($categories,'wangyang.log');
		foreach ($categories as $category){
			unset($category['child_category']);
            array_push($res,$category);
		}
        //Tools::log($res,'wangyang.log');
		$response = $this->response();

		if (count($res)) {
			$pmsCategory = [
				'id'=>1,
				'parent_id'=>0,
				'name'=>'Root',
				'path'=>'1',
				'level'=>'0',
				'child_category'=>$res,
			];
			$response->setFrom(Tools::pb_array_filter($pmsCategory));
		} else {
			throw new \Exception('未找到分类', 4601);
		}

		return $response;
	}

	public static function request()
	{
		return new getCategoryRequest();
	}

	/**
	 * @return CategoryNode
	 */
	public static function response()
	{
		return new CategoryNode();
	}
}