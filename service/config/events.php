<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 15:45
 */
return [
    'events' => [
		'product_save_after' => [
			'updateProductCache' => [
				'class' => 'service\models\merchant\Observer',
				'method' => 'updateProductCache',
			],
		],
		'product_delete' => [
			'updateProductCache' => [
				'class' => 'service\models\merchant\Observer',
				'method' => 'updateProductCache',
			],
		],
        'merchant_store_save_after' => [
            'updateMerchantStoreCache' => [
                'class' => 'service\models\merchant\Observer',
                'method' => 'updateMerchantStoreCache',
            ],
        ],
		//// 新订单,发推送给商家app
		//'order_new' => [
         //   'pushToMerchant' => [
         //       'class' => 'service\models\merchant\Observer',
         //       'method' => 'orderNew',
         //   ],
		//],
		//// 用户申请取消订单,发推送给商家app
		//'order_apply_cancel' => [
         //   'pushToMerchant' => [
         //       'class' => 'service\models\merchant\Observer',
         //       'method' => 'orderApplyCancel',
         //   ],
		//],
		//// 用户拒收订单
		//'order_reject' => [
         //   'pushToMerchant' => [
         //       'class' => 'service\models\merchant\Observer',
         //       'method' => 'orderReject',
         //   ],
		//],
		//// 用户确认签收
		//'order_pending_comment' => [
         //   'pushToMerchant' => [
         //       'class' => 'service\models\merchant\Observer',
         //       'method' => 'orderComplete',
         //   ],
		//],
		//// 超市拒单(目前merchant系统内响应此事件，只会是slim推过来的，所以只用处理退回库存的逻辑)
		//'order_decline' => [
		//	'orderRevertQty' => [
		//		'class' => 'service\models\merchant\Observer',
		//		'method' => 'orderRevertQty',
		//	],
		//],
    ],
];