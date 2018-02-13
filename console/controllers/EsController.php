<?php

namespace console\controllers;

use common\models\AvailableCity;
use common\models\Products;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use service\components\sales\quote\TotalAbstract;
use service\components\search\ElasticSearchExt;
use service\components\Tools;
use service\models\ProductHelper;
use service\tasks\updateEsProduct;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * Site controller
 */
class EsController extends Controller
{
    public $hosts = ['172.16.30.101:9200'];
    public $index = 'products';
    public $keyword = '脆骨香肠';

    public $time = [];

    protected $properties_mapping = [
        'entity_id' => [
            'type' => 'integer',
        ],
        'lsin' => [
            'type' => 'string',
            "index" => "not_analyzed"
        ],
        'rebates' => [
            'type' => 'float',
        ],
        'commission' => [
            'type' => 'float',
        ],
        'brand_suggest' => [
            "type" => "string",
            "index" => "not_analyzed"
        ],
        'name_suggest' => [
            "type" => "string",
            "index" => "not_analyzed"
        ],
        'specification_num_unit' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'label1' => [
            'type' => 'integer',
        ],
        'first_category_name' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'second_category_name' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'third_category_name' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'status' => [
            'type' => 'integer',
        ],
        'sales_type' => [
            'type' => 'integer',
        ],
        'wholesaler_weight' => [
            'type' => 'integer',
        ],
        'brand_weight' => [
            'type' => 'integer',
        ],
        'name' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'promotion_text' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'barcode' => [
            'type' => 'string',
            "index" => "not_analyzed"
        ],
        'wholesaler_id' => [
            'type' => 'integer',
        ],
        'first_category_id' => [
            'type' => 'integer'
        ],
        'second_category_id' => [
            'type' => 'integer'
        ],
        'third_category_id' => [
            'type' => 'integer'
        ],
        'brand' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'brand_agg' => [
            'type' => 'string',
            "index" => "not_analyzed"
        ],
        'package_num' => [
            'type' => 'integer',
        ],
        'package_spe' => [
            'type' => 'string',
        ],
        'state' => [
            'type' => 'integer',
        ],
        'sort_weights' => [
            'type' => 'integer',
        ],
        'sold_qty' => [
            'type' => 'integer',
        ],
        'price' => [
            'type' => 'float',
        ],
        'special_price' => [
            'type' => 'float',
        ],
        'rule_id' => [
            'type' => 'integer',
        ],
        'special_from_date' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'special_to_date' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'promotion_text_from' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'promotion_text_to' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],

        'real_sold_qty' => [
            'type' => 'integer',
        ],
        'qty' => [
            'type' => 'integer',
        ],
        'minimum_order' => [
            'type' => 'integer',
        ],

        'gallery' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'export' => [
            'type' => 'integer',
        ],
        'origin' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'package' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'specification' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'shelf_life' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'description' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],

        'production_date' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'restrict_daily' => [
            'type' => 'integer',
        ],
        'subsidies_lelai' => [
            'type' => 'float',
        ],
        'subsidies_wholesaler' => [
            'type' => 'float',
        ],

        'promotion_title_from' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'promotion_title_to' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'promotion_title' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'sales_attribute_name' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'sales_attribute_value' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'specification_num' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'specification_unit' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'type' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'type2' => [
            'type' => 'string',
            "index" => 'not_analyzed',
        ],
        'fake_sold_qty' => [
            'type' => 'integer',
        ],
        'special_rebates_from' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'special_rebates_to' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'special_rebates_lelai_from' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'special_rebates_lelai_to' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'special_rebates_lelai' => [
            'type' => 'float',
        ],
        'special_rebates' => [
            'type' => 'float',
        ],
        'is_calculate_lelai_rebates' => [
            'type' => 'integer',
        ],
        'rebates_lelai' => [
            'type' => 'float',
        ],
        'search_text' => [
            'type' => 'string',
            "analyzer" => "ik_max_word",
            "search_analyzer" => "ik_smart",
        ],
        'shelf_from_date' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis",
        ],
        'shelf_to_date' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis",
        ],
    ];

    public function actionImport($city_code)
    {
        //es设置
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        if (empty($city_code)) {
            echo 'city null';
            return;
        }


        $productModel = new Products($city_code);
        $max_id = $productModel->find()->max('entity_id');
        for ($i = 0; $i <= $max_id; $i += 1000) {

            $product_ids = $productModel->find()->where(['between', 'entity_id', $i, $i + 1000])->column();
            if (count($product_ids) > 0) {
                $params = [];
                foreach ($product_ids as $product_id) {
                    $product = Products::formatProductToES($product_id, $city_code);
                    $params['body'][] = [
                        'index' => [
                            '_index' => $this->index,
                            '_type' => $city_code,
                            '_id' => $product_id
                        ]
                    ];
                    $params['body'][] = $product;
                }

                $client->bulk($params);
                echo $i;
                echo PHP_EOL;
            }
        }
    }

    public function actionImportAllProduct()
    {
        $this->actionDeleteIndex();
        $city_all = AvailableCity::find()->all();
        /** @var AvailableCity $city */
        foreach ($city_all as $city) {
            $city_code = $city->city_code;
            try {
                $this->actionCreateIndex($city_code);
                $this->actionImport($city_code);
            } catch (\Exception $e) {
                echo $e->getMessage();
            } catch (\Error $e) {
                echo $e->getMessage();
            }
        }
    }

    public function actionDeleteIndex()
    {
        //es设置
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        if ($client->indices()->exists(['index' => $this->index])) {
            $client->indices()->delete(['index' => $this->index]);
        }
    }

    public function actionCreateIndex($city)
    {
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        if (!$client->indices()->exists(['index' => $this->index])) {
            $properties_mapping = $this->properties_mapping;
            $properties_mapping['suggest'] = [
                'type' => 'completion'
            ];
            $params = [
                'index' => $this->index,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 3,
                        'number_of_replicas' => 1,
                    ],
                    'mappings' => [
                        $city => [
                            '_source' => [
                                'enabled' => true
                            ],
                            'properties' => $properties_mapping
                        ]
                    ]
                ]
            ];

            $result = $client->indices()->create($params);
            print_r($result);
        }
    }

    public function actionAnalyzeKeywords()
    {
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        //分词
        $searchWords = $client->suggest([
            'index' => 'products',
            'type' => '441800',
            'analyzer' => [
                'analyzer' => 'ik_max_word',
                'text' => $this->keyword
            ],
        ]);
        print_r($searchWords);
    }

    public function actionSearchByEs()
    {
        $search = new ElasticSearchExt();

    }

    public function actionAbc()
    {
        $this->def();
        Tools::log(111111, 'elastic.log');
    }

    private function def()
    {
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        print_r($client);
    }

    private function search()
    {
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        try {
            $rds = new \Redis();
            $rds->connect('127.0.0.1', 6379);
            for ($i = 0; $i < 1; $i++) {
                //索引名称
                $params['index'] = 'products';
                //查询范围
                $params['type'] = '441800';
                //分页
                $params['body']['size'] = 20;
                $params['body']['from'] = 1;
                //查询
                $params['body']['query'] = [
                    'bool' => [
                        'should' => [
                            "multi_match" => [
                                "query" => 'aaaaaaa',
                                "fields" => [
                                    'name',
                                ]
                            ],
                        ],
                        'filter' => [
                            [
                                'term' => [
                                    'status' => 2
                                ]
                            ],
                            [
                                'term' => [
                                    'state' => 1
                                ]
                            ],
                        ]
                    ]
                ];

                $params['body']['sort'] = [
                    [
                        '_score' => 'desc'
                    ],
                    [
                        '_script' => [
                            'type' => 'number',
                            'script' => [
                                'inline' => '
                                        special_from_date = doc[\'special_from_date\'].value;
                                        special_to_date = doc[\'special_to_date\'].value;
                                        special_price = doc[\'special_price\'].value;
                                        rule_id = doc[\'rule_id\'].value;
                                        wholesaler_weight = doc[\'wholesaler_weight\'].value;
                                        score = 0;
                                        if(special_from_date < date && special_to_date > date) {
                                            special_price_score = special_price > 0 ? 1 :0;
                                            score = score + special_price_score;
                                        }
                                        rule_score = rule_id > 0 ? 1 :0;
                                        score = score + rule_score;
                                        wholesaler_sort_score = wholesaler_weight > 1000 ? 1 : 0;
                                        score = score + wholesaler_sort_score;
                                        return score;
                                    ',
                                'params' => [
                                    'date' => floor(microtime(true) * 1000)
                                ],
                                'lang' => 'groovy'
                            ],
                            'order' => 'desc'
                        ]
                    ],
                ];

                //查询 聚合
                $params['body']['aggs'] = [
                    "wholesaler_ids" => [
                        "terms" => [
                            "field" => "wholesaler_id",
                            "size" => 100
                        ],

                    ],
                    "first_category_ids" => [
                        "terms" => [
                            "field" => "first_category_id",
                            "size" => 100
                        ],
                    ],
                ];

//                $start = microtime(true);
                $result = $client->search($params);
                Tools::log($client, 'elastic.log');
//                var_dump($client);
                Tools::log(111111, 'elastic.log');
//                print_r($result);
//                $products = $this->getProductSource($result);
//                $wholesaler_ids = $this->getWholesalerIds($result);
//                print_r($wholesaler_ids);
//                print_r((new ProductHelper())->initWithProductArray($products, '441800')->getData());
//                $end = microtime(true);
//                $rds->sAdd('time_search_test', $end - $start);
            }
        } catch (\Exception $e) {
            print_r('exception:');
            print_r($e->getMessage());
        } catch (\Error $error) {
            print_r('error:');
            print_r($error->getCode());
        }
    }

    public function getProductSource($result)
    {
        $hits = $result['hits']['hits'];
        $products = [];
        foreach ($hits as $hit) {
            $products[] = $hit['_source'];
        }
        return $products;
    }

    public function getWholesalerIds($result)
    {
        $buckets = $result['aggregations']['all_interests']['buckets'];
        $wholesaler_ids = [];
        foreach ($buckets as $bucket) {
            array_push($wholesaler_ids, $bucket['key']);
        }
        return $wholesaler_ids;
    }

    public function actionSearchByCoreseek()
    {
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        try {
            $rds = new \Redis();
            $rds->connect('127.0.0.1', 6379);
            for ($i = 0; $i < 100; $i++) {
                $start = microtime(true);
                /** 搜索引擎 Client */
                $sphinx = new \SphinxClient();
                $sphinx->setServer('172.16.30.101', 9322);
                //设置匹配模式
                $sphinx->setMatchMode(SPH_MATCH_ANY);   //查询方式  扩展查询语法
                $sphinx->setLimits(0, 20);
                $res = $sphinx->query($this->keyword, 'product_441800_main');
                $end = microtime(true);
                $rds->sAdd('time_search_test', $end - $start);
            }

        } catch (\Exception $e) {
            print_r('exception:');
            print_r($e->getMessage());
        } catch (\Error $error) {
            print_r('error:');
            print_r($error->getCode());
        }

    }

    public function actionSearchBrand()
    {
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    public function actionSearchTest($type)
    {
        $count = 100;
        for ($i = 0; $i < $count; $i++) {
            $pid = pcntl_fork();

            if (!$pid) {
                if ($type == 'coreseek') {
                    echo 'c';
                    $this->actionSearchByCoreseek();
                } else {
                    echo 'e';
                    $this->actionSearchByEs();
                }

                exit($i);
            }
        }

        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
//            echo "Child $status completed\n";

        }

        $rds = new \Redis();
        $rds->connect('127.0.0.1', 6379);
        $result = $rds->sMembers('time_search_test');
        sort($result);
        echo PHP_EOL;
        echo '最小时间' . reset($result);
        echo PHP_EOL;
        echo '最大时间' . end($result);
        echo PHP_EOL;
        echo '90%:' . $result[$count * 90];
        echo PHP_EOL;
        echo '平均时间' . (array_sum($result) / ($count * 100));
        echo PHP_EOL;
        $rds->del('time_search_test');
    }

    /**
     * Author Jason Y.Wang
     * 更新所有商品信息
     */
    public function actionUpdateAllProduct()
    {
        $task = new updateEsProduct();
        $task->run();
    }

    public function actionProductUpdateTest()
    {
        echo 11;
        $product = Products::formatProductToES(11254, 441800);
        print_r($product);
        echo 11;
        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        echo 11;
        $result = $client->update([
            'id' => 11254,
            'index' => 'products',
            'type' => 441800,
            'body' => [
                'doc' => $product
            ]
        ]);
        echo 11;
        print_r($result);
    }

    public function actionProductDeleteTest()
    {

        $hosts = \Yii::$app->params['es_cluster']['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        echo 11;
        $result = $client->delete([
            'id' => 29353,
            'index' => 'products',
            'type' => 441800,
        ]);
        echo 22;
        print_r($result);
    }


}
