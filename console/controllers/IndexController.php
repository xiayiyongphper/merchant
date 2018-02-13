<?php
namespace console\controllers;

use common\models\LeMerchantProductList;
use common\models\LeMerchantStore;
use common\models\Products;
use common\models\ProductView;
use Elasticsearch\ClientBuilder;
use service\components\Redis;
use service\components\Tools;
use service\message\merchant\wishlistRequest;
use service\resources\merchant\v1\addCollect;
use service\resources\merchant\v1\purchaseHistory;
use service\resources\merchant\v1\wishlist;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * Site controller
 */
class IndexController extends Controller
{

    protected $customerId = 35;
    protected $authToken = '123456789';
    protected $wholesaler_id = 1;

    public function actionIndex()
    {
        $file = \Yii::getAlias('@service') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'tips.json';
        echo $file . PHP_EOL;
        $content = file_get_contents($file);
        print_r(json_decode($content, true));
        //$this->purchaseHistory();
    }

    public function actionRedisKey()
    {
        $redis = Tools::getRedis();
        $shoppingCartKey = "shopping_cart_12879";
        $wholesalerIds = $redis->zRevRangeByScore($shoppingCartKey, "+inf", "-inf");
        foreach ($wholesalerIds as $wholesalerId){
            $shoppingCartWholesalerKey = "shopping_cart_wholesaler_12879" . "_" . $wholesalerId;
            echo $shoppingCartWholesalerKey;
            $products = $redis->hGetAll($shoppingCartWholesalerKey);
            print_r($products);
        }
    }


    public function actionMerchantCategory()
    {
        $a = '280,275,327,299,288,306,312,313,309,360,94,110,71,107,129,153,155,185,189,174,160,221,227,230,263,290,317,325,351,91,95,122,128,192,186,199,265,282,314,320,102,169,101,172,148,196,198,204,209,253,276,274,281,284,302,293,324,334,336,119,190,203,211,239,231,257,264,326,329,330,308,335,355,362,367,125,140,149,150,156,158,168,191,197,270,298,301,328,353,15,31,33,35,53,84,87,89,100,116,124,142,143,187,315,300,303,331,352,73,76,86,99,113,154,165,166,161,164,208,237,256,278,279';
        $b = '327,306,360,72,71,107,129,153,155,163,227,230,263,277,290,317,351,91,95,199,265,282,314,320,102,169,133,148,184,119,146,203,239,231,257,264,326,330,308,335,125,144,150,152,156,158,168,191,197,245,248,262,298,353,3,9,33,35,100,109,116,217,242,246,292,321,73,76,82,86,99,113,165,166,249,161,164,208,278,279,294,307';
        $c = '54,64,109,131,136,181,82,92,113,247,194';

        $merchant_one_ids = explode(',', $a);
        foreach ($merchant_one_ids as $merchant_one_id) {
            echo $merchant_one_id;
            $merchant_one = null;
            $merchant_one = LeMerchantStore::findOne(['entity_id' => $merchant_one_id]);
            if ($merchant_one) {
                echo '.';
                if ($merchant_one->store_category) {
                    $merchant_one->store_category = $merchant_one->store_category . '1|';
                } else {
                    $merchant_one->store_category = '|1|';
                }
                echo $merchant_one->store_category;
                $merchant_one->save();
                print_r($merchant_one->errors);
            } else {
                echo '无:' . $merchant_one_id;
            }
        }
        echo PHP_EOL;
        $merchant_two_ids = explode(',', $b);
        foreach ($merchant_two_ids as $merchant_two_id) {
            echo $merchant_two_id;
            $merchant_two = null;
            $merchant_two = LeMerchantStore::findOne(['entity_id' => $merchant_two_id]);
            if ($merchant_two) {
                echo '.';
                if ($merchant_two->store_category) {
                    $merchant_two->store_category = $merchant_two->store_category . '2|';
                } else {
                    $merchant_two->store_category = '|2|';
                }
                echo $merchant_two->store_category;
                $merchant_two->save();
                print_r($merchant_two->errors);
            } else {
                echo '无:' . $merchant_two_id;
            }
        }
        echo PHP_EOL;
        $merchant_three_ids = explode(',', $c);
        foreach ($merchant_three_ids as $merchant_three_id) {
            echo $merchant_three_id;
            $merchant_three = null;
            $merchant_three = LeMerchantStore::findOne(['entity_id' => $merchant_three_id]);
            if ($merchant_three) {
                echo '.';
                if ($merchant_three->store_category) {
                    $merchant_three->store_category = $merchant_three->store_category . '3|';
                } else {
                    $merchant_three->store_category = '|3|';
                }
                echo $merchant_three->store_category;
                $merchant_three->save();
                print_r($merchant_three->errors);
            } else {
                echo '无:' . $merchant_three_id;
            }
        }

    }


    protected function purchaseHistory()
    {
        $request = new wishlistRequest();
        $data = [
            'customer_id' => $this->customerId,
            'auth_token' => $this->authToken,
            'pagination' => [
                'page' => 1,
            ]
        ];
        $request->setFrom($data);
        $w = new purchaseHistory();
        $data = $w->run($request->serializeToString());
        print_r($data->toArray());
    }

    protected function getWishlist()
    {
        $request = new wishlistRequest();
        $data = [
            'customer_id' => $this->customerId,
            'auth_token' => $this->authToken,
            'pagination' => [
                'page' => 3,
            ]
        ];
        $request->setFrom($data);
        $w = new wishlist();
        $data = $w->run($request->serializeToString());
        print_r($data);
    }

    protected function addCollect()
    {
        $request = new wishlistRequest();
        $data = [
            'customer_id' => $this->customerId,
            'auth_token' => $this->authToken,
            'products' => [
                ['product_id' => 1, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 2, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 3, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 4, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 5, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 6, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 7, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 8, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 9, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 10, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 11, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 12, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 13, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 14, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 15, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 16, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 17, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 18, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 19, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 20, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 21, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 22, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 23, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 24, 'wholesaler_id' => $this->wholesaler_id],
                ['product_id' => 25, 'wholesaler_id' => $this->wholesaler_id],
            ]
        ];
        $request->setFrom($data);
        $w = new addCollect();
        $w->run($request->serializeToString());
    }

    public function actionTest()
    {
        $array = [
            ['id' => '123', 'data' => 'abc'],
            ['id' => '345', 'data' => 'def'],
        ];
        $result = ArrayHelper::getColumn($array, 'id');
        print_r($result);
    }

    public function actionCate()
    {
        $categories = Redis::getPMSCategories();
        print_r($categories);
    }

    public function actionBarcodeToProductId()
    {
        $items = LeMerchantProductList::find()->orderBy('identifier desc')->all();
        $file = 'service/runtime/logs/products.log';
        /** @var LeMerchantProductList $item */
        foreach ($items as $item) {
            $wholesaler_id = $item->wholesaler_id;
            /** @var LeMerchantStore $wholesaler */
            $wholesaler = LeMerchantStore::find()->where(['entity_id' => $wholesaler_id])->one();
            if ($wholesaler) {
                $city = $wholesaler->city;
                /** @var Products $productModel */
                $barcodes = array_filter(explode(';', $item->barcode));
                $productModel = new Products($city);
                foreach ($barcodes as $barcode) {
                    $productCount = $productModel::find()->where(['barcode' => $barcode, 'wholesaler_id' => $wholesaler_id])->count();
                    $str = $item->identifier . '##' . $city . ':' . $wholesaler_id . '#' . $barcode . '=======>';
                    if ($productCount == 1) {
                        /** @var Products $product */
                        $product = $productModel::find()->where(['barcode' => $barcode, 'wholesaler_id' => $wholesaler_id])->one();
                        if ($product) {
                            file_put_contents($file, $str . $product->entity_id, FILE_APPEND);
                            file_put_contents($file, PHP_EOL, FILE_APPEND);
                            print_r($str . $product->entity_id);
                            echo PHP_EOL;
                        } else {
                            file_put_contents($file, $str . '无此商品', FILE_APPEND);
                            file_put_contents($file, PHP_EOL, FILE_APPEND);
                            print_r($str . '无此商品');
                            echo PHP_EOL;
                        }
                    } else if ($productCount == 0) {
                        file_put_contents($file, $str . '无此商品', FILE_APPEND);
                        file_put_contents($file, PHP_EOL, FILE_APPEND);
                        print_r($str . '无此商品');
                        echo PHP_EOL;
                    } else {
                        file_put_contents($file, $str . '对应多个商品', FILE_APPEND);
                        file_put_contents($file, PHP_EOL, FILE_APPEND);
                        print_r($str . '对应多个商品');
                        echo PHP_EOL;
                    }
                }
            }
        }
    }


}
