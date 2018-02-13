<?php

namespace service\models;

use framework\message\Message;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\core\HomeRequest;
use service\message\customer\CartItemsRequest;
use service\message\customer\LoginRequest;
use service\message\customer\RemoveCartItemsRequest;
use service\message\customer\TestReportRequest;
use service\message\merchant\bestsellerRequest;
use service\message\merchant\ButtomTabRedDotRequest;
use service\message\merchant\ClassPageHomeRequest;
use service\message\merchant\customThematicActivityRequest;
use service\message\merchant\customThematicProductRequest;
use service\message\merchant\getAreaBrandRequest;
use service\message\merchant\getAreaCategoryRequest;
use service\message\merchant\getCategoryStoresRequest;
use service\message\merchant\getNewOrderTriggerMsgRequest;
use service\message\merchant\getProductBriefRequest;
use service\message\merchant\getProductRequest;
use service\message\merchant\getStoreCategoryRequest;
use service\message\merchant\getStoreDetailRequest;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\message\merchant\getStoresByCategoryIdRequest;
use service\message\merchant\MerchantAuthenticationRequest;
use service\message\merchant\pieceTogetherOrderAreaRequest;
use service\message\merchant\reduceQtyRequest;
use service\message\merchant\reorderRequest;
use service\message\merchant\searchProductRequest;
use service\message\merchant\searchSuggestRequest;
use service\message\merchant\SearchTraceRecord;
use service\message\merchant\SecKillActivityRequest;
use service\message\merchant\SecKillActProductsRequest;
use service\message\merchant\thematicActivityRequest;
use service\message\merchant\TopicHomeRequest;
use service\message\sales\OrderCollectionRequest;
use service\models\client\ClientAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 12:01
 */
class SOAClient extends ClientAbstract
{
    public $responseClass = null;
    public $model = 'merchant';
    public $method = null;

    //protected $_customer = 35;
    //protected $_authToken = 'KBovpuxTtPUbhq28';
//    protected $_customer = 1088;
//    protected $_authToken = 'w2qym9wIoUJzYihO';
    protected $_customer = 12879;
    protected $_authToken = 'bNMZTsOWnaGpa51H';
//    protected $_customer = 1057;
//    protected $_authToken = 'EaWSkuJ6oOIu4AH6';


    public function onConnect($client)
    {
        echo "[Client]: Connected to server." . PHP_EOL;
        $argv = $_SERVER['argv'];
        if (count($argv) == 2) {
            $method = $argv[1];
            if (method_exists($this, $method)) {
                $this->$method();
            } else {
                $class = new \ReflectionClass('service\models\SOAClient');
                $methods = $class->getMethods();
                echo 'Callable methods:' . PHP_EOL;
                foreach ($methods as $index => $method) {
                    echo $index . ':' . $method->getName() . PHP_EOL;
                }
                echo sprintf('Total:%s', count($methods)) . ' method(s)' . PHP_EOL;
            }
        } else {
//            $this->get_custom_thematic_activity();
//            $this->get_custom_thematic_product();
            //$this->merchant_getProduct();
//            $this->merchant_home6();
            //$this->merchant_home7();
            //$this->merchant_home8();
            //$this->get_custom_thematic_activity();
//            $this->getAreaStores();
//            $this->merchant_getStoresByAreaIdsV2();
            //$this->merchant_getStoreCategory();
            //$this->merchant_getStoresByCategoryId();
            //$this->get_product_topic();
            //$this->getMostFavourableProduct();
//            $this->searchSuggest();
            $this->merchant_searchProduct();
//            $this->getCategory();
//            $this->getPieceTogetherOrderArea();
//            $this->test();
//            $this->searchSuggest();
            //$this->merchant_getStoreDetail();
            //$this->get_topic_home();
            //$this->bestSeller();
//            $this->merchant_home9();
            //$this->getRecommend();
//            $this->get_class_page_home();
//            $this->getAggregationProducts();
//            $this->getAreaBrand2();
//            $this->getAggregationProductsArea();
        }
    }

    public function test()
    {
        $this->responseClass = true;
        $request = new TestReportRequest();
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.test');
        $data = Message::pack($header, $request);
        $this->send($data);
    }

    public function searchSuggest()
    {
        $this->responseClass = 'service\message\merchant\searchSuggestResponse';
        $request = new searchSuggestRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->setKeyword('农夫');
        $request->setWholesalerId(1);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.searchSuggest');
        $data = Message::pack($header, $request);
        $this->send($data);
    }


    public function getAggregationProductsArea()
    {
        $this->responseClass = 'service\message\merchant\thematicActivityResponse';
        $request = new getProductRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->appendProductIds(4);
        $request->setWholesalerId(3);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setAppVersion('2.8.9');
        $header->setRoute('merchant.getAggregationProductsArea');
        $data = Message::pack($header, $request);
        $this->send($data);
    }

    public function getAreaBrand2()
    {
        $this->responseClass = 'service\message\merchant\getAreaBrandResponse';
        $request = new getAreaBrandRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->setCategoryId(85);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setAppVersion('2.8.9');
        $header->setRoute('merchant.getAreaBrand2');
        $data = Message::pack($header, $request);
        $this->send($data);
    }

    public function getPieceTogetherOrderArea()
    {
        $this->responseClass = 'service\message\merchant\pieceTogetherOrderAreaResponse';
        $request = new pieceTogetherOrderAreaRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->setWholesalerId(9);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setAppVersion('2.8.9');
        $header->setRoute('merchant.getPieceTogetherOrderArea');
        $data = Message::pack($header, $request);
        $this->send($data);
    }

    public function getCategory()
    {
        $this->responseClass = 'service\message\merchant\categoryResponse';
        $request = new getAreaCategoryRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setAppVersion('2.8.9');
        $header->setRoute('merchant.getAreaCategory2');
        $data = Message::pack($header, $request);
        $this->send($data);
    }


    public function getAggregationProducts()
    {
        $this->responseClass = 'service\message\merchant\getProductResponse';
        $request = new getProductRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->appendProductIds(3882);
        $request->appendProductIds(9505);
        $request->setWholesalerId(0);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setAppVersion('2.8.9');
        $header->setRoute('merchant.getAggregationProducts');
        $data = Message::pack($header, $request);
        $this->send($data);
    }


    public function get_class_page_home()
    {
        $this->responseClass = 'service\message\merchant\ClassPageHomeResponse';
        $homeReq = new ClassPageHomeRequest();
        $homeReq->setClassPageId(1);
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.classPageHome');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function getRecommend()
    {
        $this->responseClass = 'service\message\merchant\RecommendResponse';
        $request = new HomeRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setAppVersion('2.8.9');
        $header->setRoute('merchant.getRecommend');
        $data = Message::pack($header, $request);
        $this->send($data);
    }

    public function bestSeller()
    {
        $this->responseClass = 'service\message\merchant\bestsellerResponse';
        $request = new bestsellerRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->setWholesalerId(5);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.bestseller');
        $data = Message::pack($header, $request);
        $this->send($data);
    }


    public function get_custom_thematic_product()
    {
        $this->responseClass = 'service\message\merchant\customThematicProductResponse';
        $thematicProduct = new customThematicProductRequest();
        $thematicProduct->setCustomerId($this->_customer);
        $thematicProduct->setAuthToken($this->_authToken);
        $thematicProduct->setCustomThematicSubId(1);
        $thematicProduct->setCustomThematicId(1);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getCustomThematicProduct');
        $data = Message::pack($header, $thematicProduct);
        //Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function get_custom_thematic_activity()
    {
        $this->responseClass = 'service\message\merchant\customThematicActivityResponse';
        $thematicActivity = new customThematicActivityRequest();
        $thematicActivity->setCustomerId($this->_customer);
        $thematicActivity->setAuthToken($this->_authToken);
        $thematicActivity->setThematicId(3);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getCustomThematicActivity');
        $data = Message::pack($header, $thematicActivity);
        //Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function get_topic_home()
    {
        $this->responseClass = 'service\message\merchant\TopicHomeResponse';
        $homeReq = new TopicHomeRequest();
        $homeReq->setTopicId(1);
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.topicHome');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function onReceive($client, $data)
    {
        //echo "[Client]: Receive:". $data . PHP_EOL;
        echo "[Client]: Received." . PHP_EOL;
        $message = new Message();
        $message->unpackResponse($data);
        //print_r($message);
        //echo "[Client]: Receive:".get_class($message->getHeader()).PHP_EOL;
        //print_r($message->getHeader()->toArray());
        if ($message->getHeader()->getCode() > 0) {
            //echo '[Client]:程序执行异常：' . PHP_EOL;
            echo sprintf('程序执行异常：%s', $message->getHeader()->getMsg()) . PHP_EOL;
        } else {
            $responseClass = $this->responseClass;
            if ($responseClass != 'true') {
                if (PHP_MAJOR_VERSION >= 7) {
                    $response = new $responseClass();
                    $response->parseFromString($message->getPackageBody());
                } else {
                    $response = $responseClass::parseFromString($message->getPackageBody());
                }

                echo "[Client]: " . get_class($response) . PHP_EOL;
//                echo json_encode($response->toArray());
                print_r($response);
            } else {
                echo '[Client]:body为空' . PHP_EOL;
            }
        }
        //$this->close();
    }


    public function getMostFavourableProduct()
    {
        $this->responseClass = 'service\message\merchant\searchProductResponse';
        $request = new searchProductRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $request->setWholesalerId(5);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getMostFavourableProduct');
        $data = Message::pack($header, $request);
        //Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function get_product_topic()
    {
        $this->responseClass = 'service\message\merchant\ProductTopicResponse';
        $thematicActivity = new thematicActivityRequest();
        $thematicActivity->setCustomerId($this->_customer);
        $thematicActivity->setAuthToken($this->_authToken);
        $thematicActivity->setIdentifier('天天特价');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getProductTopic');
        $data = Message::pack($header, $thematicActivity);
        //Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function merchant_getStoreDetail()
    {
        $this->responseClass = 'service\message\common\Store';
        $request = new getStoreDetailRequest();
        $request->setWholesalerId(92);
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setCustomerId(35949);
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getStoreDetail1');
        $this->send(Message::pack($header, $request));
    }

    //店铺类目列表
    public function merchant_getStoreCategory()
    {
        $this->responseClass = 'service\message\merchant\getStoreCategoryResponse';
        $request = new getStoreCategoryRequest();
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getStoreCategory');
        $this->send(Message::pack($header, $request));
    }

    //供货商列表
    public function merchant_getStoresByCategoryId()
    {
        $this->responseClass = 'service\message\merchant\getStoresByCategoryIdResponse';
        $request = new getStoresByCategoryIdRequest();
        $request->setFlag(1);
        $request->appendAreaIds(31);
        $request->setCategoryId(2);
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        //
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getStoresByCategoryId');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getStoresByAreaIdsV2()
    {
        $this->responseClass = 'service\message\merchant\getStoresByAreaIdsResponse';
        $request = new getStoresByAreaIdsRequest();
        $request->appendAreaIds(31);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getStoresByAreaIdsV2');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getStoresByAreaIds()
    {
        $this->responseClass = 'service\message\merchant\getStoresByAreaIdsResponse';
        $request = new getStoresByAreaIdsRequest();
        $request->appendAreaIds(5);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getStoresByAreaIds');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getProduct()
    {
        $this->responseClass = 'service\message\merchant\getProductResponse';
        $request = new getProductRequest();
        $request->setWholesalerId(2);
        $request->appendProductIds(154);
        //$request->appendProductIds(122);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getProduct');
        //Tools::log(Message::pack($header, $request), 'test.log');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getProductBrief()
    {
        $this->responseClass = 'service\message\merchant\getProductBriefResponse';
        $request = new getProductBriefRequest();
//        $request->setCity('440300');
        $request->setCity('441800');
        //$items = range(1, 5000);
        //foreach ($items as $item) {
        //    $request->appendProductIds($item);
        //}
//        $request->appendProductIds(439);
//        $request->appendProductIds(440);
//        $request->appendProductIds(441);
        $request->appendProductIds(4866);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getProductBrief');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getAreaBrand()
    {
        $this->responseClass = 'service\message\merchant\getAreaBrandResponse';
        $request = new getAreaBrandRequest();
        $request->setWholesalerId(1);
        $request->setCategoryId(103);
        $request->setCategoryLevel(1);
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getAreaBrand');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getAreaCategory()
    {
        $this->responseClass = 'service\message\common\CategoryNode';
        $request = new getAreaCategoryRequest();
        //$request->setWholesalerId(1);
        $request->setCustomerId(1091);
        $request->setAuthToken('jSXGDG6SqYvPUuwO');
        //$request->setWholesalerId(1);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getAreaCategory');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_getCategoryStores()
    {
        $this->responseClass = 'service\message\merchant\getCategoryStoresResponse';
        $request = new getCategoryStoresRequest();
        //$request->setWholesalerId(1);
        $request->setAreaId(39);
        $request->setCustomerId(2650);
        $request->setAuthToken('TVIgAzmSL8rqh8Kv');
        $request->setCategoryId(80);
        $request->setCategoryLevel(0);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getCategoryStores');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_reduceQty()
    {
        $this->responseClass = 'service\message\merchant\reduceQtyResponse';
        $request = new reduceQtyRequest();
        $request->setFrom([
            'customer_id' => 72,
            'auth_token' => 'FOwLs6prG2g8JTYz',
            'products' => [
                [
                    'wholesaler_id' => 1,
                    'product_id' => 1,
                    'num' => 1,
                ],
                [
                    'wholesaler_id' => 1,
                    'product_id' => 2,
                    'num' => 2,
                ],
                [
                    'wholesaler_id' => 3,
                    'product_id' => 1,
                    'num' => 4,
                ],
            ],
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.reduceQty');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_updateItems()
    {
        $this->responseClass = 'service\message\common\UniversalResponse';
        $request = new reduceQtyRequest();
        $request->setFrom([
//            'customer_id' => $this->_customer,
//            'auth_token' => $this->_authToken,
            'customer_id' => 1089,
            'auth_token' => 'DzYs2PCHxfiSdBsb',
            'products' => [
                [
                    'wholesaler_id' => 3,
                    'product_id' => 114,
                    'num' => 2,
                ],
                [
                    'wholesaler_id' => 33,
                    'product_id' => 4688,
                    'num' => 5,
                ],
                [
                    'wholesaler_id' => 33,
                    'product_id' => 2147483953,
                    'num' => 2,
                    'type' => 1
                ],
//                [
//                    'wholesaler_id' => 33,
//                    'product_id' => 2147483707,
//                    'num' => 2,
//                    'type' => 1
//                ],
            ],
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.updateItems');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_cartItems3()
    {
        $this->responseClass = 'service\message\customer\CartItemsResponse2';
        $request = new CartItemsRequest();
        $request->setFrom([
            'customer_id' => $this->_customer,
            'auth_token' => $this->_authToken,
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.cartItems3');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_removeCartItems()
    {
        $this->responseClass = 'service\message\common\UniversalResponse';
        $request = new RemoveCartItemsRequest();
        $request->setFrom([
            'customer_id' => $this->_customer,
            'auth_token' => $this->_authToken,
//            'customer_id' => 1089,
//            'auth_token' => 'DFjrSfLPSYzsHfoE',
            'products' => [
                [
                    'wholesaler_id' => 3,
                    'product_id' => 114,
                    'num' => 2,
                ],
                [
                    'wholesaler_id' => 33,
                    'product_id' => 2147483650,
                    'type' => 1
                ],
            ],
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.removeCartItems');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_GetSecKillProduct()
    {
        $this->responseClass = 'service\message\merchant\getProductResponse';
        $request = new getProductRequest();
        $request->setFrom([
            'customer_id' => $this->_customer,
            'auth_token' => $this->_authToken,
            'wholesaler_id' => 33,
            'product_ids' => [
                2147483650
            ],

        ]);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.getProduct');
        $this->send(Message::pack($header, $request));
    }

    public function getButtomTabRedDot()
    {
        $this->responseClass = 'service\message\merchant\ButtomTabRedDotResponse';
        $req = new ButtomTabRedDotRequest();
        $req->setCustomerId($this->_customer);
        $req->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getButtomTabRedDot');
        $data = Message::pack($header, $req);
        $this->send($data);
    }

    public function merchant_home()
    {
        $this->responseClass = 'service\message\core\HomeResponse';
        $homeReq = new HomeRequest();
        $homeReq->setCustomerId(2650);
        $homeReq->setAuthToken('TVIgAzmSL8rqh8Kv');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home');
        $data = Message::pack($header, $homeReq);
        Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function merchant_home2()
    {
        $this->responseClass = 'service\message\core\HomeResponse';
        $homeReq = new HomeRequest();
        $homeReq->setCustomerId(1069);
        $homeReq->setAuthToken('MIsQWiTcmaKsu6iv');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home2');
        $data = Message::pack($header, $homeReq);
        //Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function merchant_home3()
    {
        $this->responseClass = 'service\message\core\HomeResponse';
        $homeReq = new HomeRequest();
        $homeReq->setAppVer("2.2.0");
        $homeReq->setCustomerId(1057);
        $homeReq->setAuthToken('nvgUjDzOTcSXXbVp');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home3');
        $data = Message::pack($header, $homeReq);
        Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function merchant_home5()
    {
        $this->responseClass = 'service\message\core\HomeResponse2';
        $homeReq = new HomeRequest();
        $homeReq->setAppVer("2.2.0");
        $homeReq->setCustomerId(1068);
        $homeReq->setAuthToken('4nBzrP1MRQ1B3CTQ');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home5');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_home6()
    {
        $this->responseClass = 'service\message\core\HomeResponse2';
        $homeReq = new HomeRequest();
        $homeReq->setAppVer("2.2.0");
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home6');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_home7()
    {
        $this->responseClass = 'service\message\core\HomeResponse2';
        $homeReq = new HomeRequest();
        $homeReq->setAppVer("2.2.0");
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home7');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_home8()
    {
        $this->responseClass = 'service\message\core\HomeResponse2';
        $homeReq = new HomeRequest();
        $homeReq->setAppVer("2.2.9");
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.home8');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_home9()
    {
        $this->responseClass = 'service\message\core\HomeResponse3';
        $homeReq = new HomeRequest();
        $homeReq->setAppVer("2.9.1");
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setAppVersion('2.8.9');
        $header->setVersion(1);
        $header->setRoute('merchant.home9');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_getNewOrderTriggerMsg()
    {
        $this->responseClass = 'service\message\merchant\getNewOrderTriggerMsgResponse';
        $req = new getNewOrderTriggerMsgRequest();
        $req->setCustomerId($this->_customer);
        $req->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setAppVersion('2.8.9');
        $header->setVersion(1);
        $header->setRoute('merchant.getNewOrderTriggerMsg');
        $data = Message::pack($header, $req);
        $this->send($data);
    }

    public function merchant_saveSearchTraceRecord()
    {
        $this->responseClass = 'service\message\common\UniversalResponse';
        $req = new SearchTraceRecord();
        $req->setCustomerId($this->_customer);
        $req->setAuthToken($this->_authToken);
        $req->setSearchId(time());
        $req->setType(2);
        $req->setInfo('哇哈哈纯净水');

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.saveSearchTraceRecord');
        $data = Message::pack($header, $req);
        $this->send($data);
    }

    public function merchant_GetSecKillActivity()
    {
        $this->responseClass = 'service\message\merchant\SecKillActivityResponse';
        $homeReq = new SecKillActivityRequest();
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.GetSecKillActivity');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_GetSecKillActivityProducts()
    {
        $this->responseClass = 'service\message\merchant\SecKillActProductsResponse';
        $homeReq = new SecKillActProductsRequest();
        $homeReq->setCustomerId($this->_customer);
        $homeReq->setAuthToken($this->_authToken);
        $homeReq->setActId(2);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.GetSecKillActivityProducts');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function getAreaStores()
    {
        $this->responseClass = 'service\message\common\StoreBlock';
        $homeReq = new getStoresByAreaIdsRequest();
        $homeReq->setCustomerId(31843);
        $homeReq->setAuthToken('BZk3lGaIn1u7Ftu8');
        $homeReq->appendAreaIds(58);

        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getAreaStores');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function merchant_login()
    {
        $this->responseClass = 'service\message\common\Merchant';
        $homeReq = new LoginRequest();
        $homeReq->setUsername('文城');
        $homeReq->setPassword(md5('123456'));
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.login');
        $data = Message::pack($header, $homeReq);
        $this->send($data);
    }

    public function getTopic()
    {
        $this->responseClass = 'service\message\merchant\thematicActivityResponse';
        $thematicActivity = new thematicActivityRequest();
        $thematicActivity->setCustomerId($this->_customer);
        $thematicActivity->setAuthToken($this->_authToken);
        $thematicActivity->setIdentifier('featured_product_list');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setVersion(1);
        $header->setRoute('merchant.getTopic');
        $data = Message::pack($header, $thematicActivity);
        Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function merchant_searchProduct()
    {
        $this->responseClass = 'service\message\merchant\searchProductResponse';
        $request = new searchProductRequest();
        $request->setCustomerId($this->_customer);
        $request->setAuthToken($this->_authToken);
        //$request->setCategoryId(88);
        //$request->setCategoryLevel(3);
//        $request->setBrand('农夫山泉');
        $request->setKeyword('康师傅');
//        $request->appendProductSalesTypeIds(1);
        $request->setPage(1);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.searchProduct2');
        $header->setAppVersion(3.0);
        $this->send(Message::pack($header, $request));
    }

    public function merchant_merchantAuthentication()
    {
        $this->responseClass = 'service\message\common\Merchant';
        $request = new MerchantAuthenticationRequest();
        $request->setWholesalerId(1);
        $request->setAuthToken('Ge9SC7jRwLBvoH4f');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.merchantAuthentication');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_orderCollection()
    {
        $this->responseClass = 'service\message\sales\OrderCollectionResponse';
        $request = new OrderCollectionRequest();
        $request->setWholesalerId(8);
        $request->setAuthToken('GrAtJG5O3dWM9HYi');
        $request->setState('shipping');
        //$request->setKeyword('佰');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.orderCollection');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_orderDetail()
    {
        $this->responseClass = 'service\message\common\Order';
        $request = new sales\OrderDetailRequest();
        $request->setWholesalerId(3);
        $request->setAuthToken('oG6MUWx9b1D9plyB');
        $request->setOrderId(500);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.orderDetail');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_orderConfirm()
    {
        $this->responseClass = 'service\message\common\Order';
        $request = new common\OrderAction();
        $request->setWholesalerId(1);
        $request->setAuthToken('Ge9SC7jRwLBvoH4f');
        $request->setOrderId(191);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.orderConfirm');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_orderDecline()
    {
        $this->responseClass = 'service\message\common\Order';
        $request = new common\OrderAction();
        $request->setWholesalerId(2);
        $request->setAuthToken('nWY3qKTsFu8HTnqA');
        $request->setOrderId(297);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.orderDecline');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_orderRejectCancel()
    {
        $this->responseClass = 'service\message\common\Order';
        $request = new common\OrderAction();
        $request->setWholesalerId(2);
        $request->setAuthToken('nWY3qKTsFu8HTnqA');
        $request->setOrderId(212);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.orderRejectCancel');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_postDeviceToken()
    {
        $this->responseClass = 'true';
        $request = new customer\PostDeviceTokenRequest();
        $request->setWholesalerId(2);
        $request->setAuthToken('nWY3qKTsFu8HTnqA');
        $request->setSystem('1');
        $request->setToken('test');
        $request->setChannel('300001');
        $request->setTypequeue('123');
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.postDeviceToken');
        $this->send(Message::pack($header, $request));
    }

    public function merchant_reorder()
    {
        $this->responseClass = 'service\message\merchant\reorderResponse';
        $request = new reorderRequest();
        $request->setAuthToken('NVePBknrAhCPKG1M');
        $request->setCustomerId(1102);
        $request->setOrderId(235030);
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('merchant.reorder');
        $this->send(Message::pack($header, $request));
    }

}
