<?php
namespace service\models\merchant\observer;

use common\models\LeCustomersRelationship;
use common\models\LeCustomersRelationshipSync;
use framework\components\es\Console;
use service\components\Tools;
use service\events\ServiceEvent;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 17:23
 */
class Customer
{

	/**
	 * 内部函数获取data
	 * @param ServiceEvent|array $event
	 * @return array
	 */
	protected static function getEventData($event)
	{
		if (!is_array($event)) {
			$event_data = $event->getEventData();
		} else {
			$event_data = $event;
		}
		Console::get()->log(print_r($event_data, true), null, [__METHOD__]);
		return $event_data;
	}

	protected static function customerResponseToRelationship($customer){
		/*
		 * 字段映射表
		 * 左边是relationship表字段
		 * 右边是custome_response字段
		 */
		$mapping = [
			'bind_id' => ['entity_id','customer_id'],
			'storekeeper' => 'storekeeper',
			'phone' => 'phone',
			'store_name' => 'store_name',
			'consignee' => 'receiver_name',
			'consignee_phone' => 'receiver_phone',
			'province' => 'province',
			'city' => 'city',
			'district' => 'district',
			'address' => 'address',
		];

		// 转换
		$result = [];
		foreach ($mapping as $rs_key => $cr_key) {
			if(is_array($cr_key)){
				foreach ($cr_key as $key) {
					if(isset($customer[$key])){
						$result[$rs_key] = $customer[$key];
					}
				}
			}else{
				if(isset($customer[$cr_key])){
					$result[$rs_key] = $customer[$cr_key];
				}
			}

		}
		return $result;
	}


	/**
	 * 用户审核通过||修改保存信息时,同步到saas系统
	 * @param $event
	 */
	public static function customerInfoSyncToRelationship($event)
	{
		try {
			Console::get()->log(__METHOD__, null, [__METHOD__]);

			$customer = self::getEventData($event);
			Tools::log($customer, 'sync.log');
			// 统一到entity_id
			if(!isset($customer['entity_id']) && isset($customer['customer_id'])){
				$customer['entity_id'] = isset($customer['customer_id']);
			}

			/**
			 * 1.找没有绑定的超市,提示绑定
			 */
			$relation_collection = LeCustomersRelationship::find()->where([
				'phone'=>$customer['phone'],
				'client_type'=>[0, 1],
				'bind_id'=>0,
			])->all();

			//Tools::log($relation_collection, 'sync.log');


			// 遍历relation_collection,插入le_customers_relationship_sync表提示同步
			/** @var LeCustomersRelationship $relation_item */
			foreach ($relation_collection as $relation_item) {

				// 新数据
				$new_data = self::customerResponseToRelationship($customer);
				//Tools::log('new_data', 'sync.log');
				//Tools::log($new_data, 'sync.log');

				// 对比原relation变动了的数据
				$diff_data = [];
				foreach ($new_data as $key=>$value) {
					if($relation_item->hasAttribute($key) && $relation_item->getAttribute($key)!=$value){
						//Tools::log($key.":".$relation_item->getAttribute($key)."->".$value, 'sync.log');
						$diff_data[$key] = $value;
					}
				}
				// 如果diff_data仅有一个bind_id,说明其实没有数据要更新
				if(count($diff_data)==1 && isset($diff_data['bind_id'])){
					$diff_data = [];
				}
				//Tools::log('diff_data', 'sync.log');
				//Tools::log($diff_data, 'sync.log');

				// 新数据json字符串
				$new_data_json = json_encode($diff_data);

				// 看sync里有没有这个记录
				/** @var LeCustomersRelationshipSync $sync */
				$sync = LeCustomersRelationshipSync::find()->where([
					'relation_id' => $relation_item->entity_id,
				])->one();

				// 数据确有更新才处理
				if(count($diff_data)>0 && (!$sync || $sync->snapshot_data!=$new_data_json)){
					// 没有sync记录则新增
					if(!$sync){
						$sync = new LeCustomersRelationshipSync();
						$sync->created_at = date('Y-m-d H:i:s');
						$sync->relation_id = $relation_item->entity_id;
						$sync->merchant_id = $relation_item->merchant_id;
					}
					// 更新数据
					$sync->action_type = 0;
					$sync->snapshot_data = $new_data_json;
					$sync->data_updated_at = date('Y-m-d H:i:s');
					$sync->updated_at = date('Y-m-d H:i:s');
					// 保存出错写log
					if(!$sync->save()){
						Tools::log('sync', 'error.log');
					}
				}
				// 如果有sync记录,但是最新数据与relation里的一模一样,那就把这条sync删掉,不需要提示更新
				elseif ($sync && count($diff_data)==0){
					$sync->delete();
				}

			}


			/**
			 * 2.找绑定了的,自动同步信息
			 */
			$relation_collection = LeCustomersRelationship::find()->where([
				'client_type'=>1,
				'bind_id'=>$customer['entity_id'],
			])->all();
			//Tools::log($relation_collection, 'sync.log');


			// 遍历relation_collection,逐个更新
			/** @var LeCustomersRelationship $item */
			foreach ($relation_collection as $relation_item) {

				// 新用户数据
				$new_data = self::customerResponseToRelationship($customer);
				//Tools::log('new_data:', 'sync.log');
				//Tools::log($new_data, 'sync.log');

				// 逐个更新
				foreach ($new_data as $key=>$value) {
					if($relation_item->hasAttribute($key)){
						$relation_item->setAttribute($key, $value);
					}
				}
				$relation_item->updated_at = date('Y-m-d H:i:s');

				// 保存
				if(!$relation_item->save()){
					Tools::log("============Sync save error============", 'error.log');
					Tools::log("data:", 'error.log');
					Tools::log($relation_item->toArray(), 'error.log');
					Tools::log("errors:", 'error.log');
					Tools::log($relation_item->getErrors(), 'error.log');
					Tools::log("=======================================", 'error.log');
				}

			}



		} catch (\Exception $e) {

		}
	}
}
