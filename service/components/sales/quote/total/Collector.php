<?php
namespace service\components\sales\quote\total;

use service\components\sales\quote\TotalAbstract;

/**
 * Address Total Collector model
 *
 * @category    Mage
 * @package     Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collector
{
    /**
     * Path to sort order values of checkout totals
     */
    const XML_PATH_SALES_TOTALS_SORT = 'sales/totals_sort';

    /**
     * Total models array ordered for right display sequence
     *
     * @var array
     */
    protected $_retrievers = array();

    /**
     * Corresponding store object
     *
     * @var Mage_Core_Model_Store
     */
    protected $_store;

    /**
     * Configuration path where to collect registered totals
     *
     * @var string
     */
    protected $_totalsConfigNode = 'global/sales/quote/totals';

    /**
     * Cache key for collectors
     *
     * @var string
     */
    protected $_collectorsCacheKey = 'sorted_quote_collectors';

    protected $_collectors = array();
    /**
     * Models configuration
     *
     * @var array
     */
    protected $_modelsConfig = array();

    /**
     * Prepared models
     *
     * @var array
     */
    protected $_models = array();

    /**
     * Init corresponding total models
     *
     * @param array $options
     */
    public function __construct()
    {
        $this->_initModels()
            ->_initCollectors();
    }

    /**
     * Get total models array ordered for right calculation logic
     *
     * @return array
     */
    public function getCollectors()
    {
        return $this->_collectors;
    }

    /**
     * Get total models array ordered for right display sequence
     *
     * @return array
     */
    public function getRetrievers()
    {
        return $this->_retrievers;
    }

    /**
     * @param $class
     * @param $totalCode
     * @param $totalConfig
     * @return object
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function _initModelInstance($class, $totalCode, $totalConfig)
    {
        //$model = Mage::getModel($class);
        $model = \Yii::createObject($class);
        if (!$model instanceof TotalAbstract) {
            throw new \Exception('The quote total model should be extended from TotalAbstract.');
        }

        $model->setCode($totalCode);
        $this->_modelsConfig[$totalCode] = $this->_prepareConfigArray($totalCode, $totalConfig);
        $this->_modelsConfig[$totalCode] = $model->processConfigArray($this->_modelsConfig[$totalCode]);
        return $model;
    }

    /**
     * Initialize collectors array.
     * Collectors array is array of total models ordered based on configuration settings
     *
     * @return  $this
     */
    protected function _initCollectors()
    {
        $sortedCodes = $this->_getSortedCollectorCodes();
        foreach ($sortedCodes as $code) {
            $this->_collectors[$code] = $this->_models[$code];
        }

        return $this;
    }

    /**
     * Aggregate before/after information from all items and sort totals based on this data
     *
     * @return array
     */
    protected function _getSortedCollectorCodes()
    {
        $configArray = $this->_modelsConfig;
        // invoke simple sorting if the first element contains the "sort_order" key
        reset($configArray);
        $element = current($configArray);
        if (isset($element['sort_order'])) {
            uasort($configArray, array($this, '_compareSortOrder'));
        } else {
            foreach ($configArray as $code => $data) {
                foreach ($data['before'] as $beforeCode) {
                    if (!isset($configArray[$beforeCode])) {
                        continue;
                    }
                    $configArray[$code]['before'] = array_unique(array_merge(
                        $configArray[$code]['before'], $configArray[$beforeCode]['before']
                    ));
                    $configArray[$beforeCode]['after'] = array_merge(
                        $configArray[$beforeCode]['after'], array($code), $data['after']
                    );
                    $configArray[$beforeCode]['after'] = array_unique($configArray[$beforeCode]['after']);
                }
                foreach ($data['after'] as $afterCode) {
                    if (!isset($configArray[$afterCode])) {
                        continue;
                    }
                    $configArray[$code]['after'] = array_unique(array_merge(
                        $configArray[$code]['after'], $configArray[$afterCode]['after']
                    ));
                    $configArray[$afterCode]['before'] = array_merge(
                        $configArray[$afterCode]['before'], array($code), $data['before']
                    );
                    $configArray[$afterCode]['before'] = array_unique($configArray[$afterCode]['before']);
                }
            }
            uasort($configArray, array($this, '_compareTotals'));
        }
        $sortedCollectors = array_keys($configArray);
        return $sortedCollectors;
    }

    /**
     * Prepare configuration array for total model
     *
     * @param   string $code
     * @param   array $totalConfig
     * @return  array
     */
    protected function _prepareConfigArray($code, $totalConfig)
    {
        if (isset($totalConfig['before'])) {
            $totalConfig['before'] = explode(',', $totalConfig['before']);
        } else {
            $totalConfig['before'] = array();
        }
        if (isset($totalConfig['after'])) {
            $totalConfig['after'] = explode(',', $totalConfig['after']);
        } else {
            $totalConfig['after'] = array();
        }
        $totalConfig['_code'] = $code;
        return $totalConfig;
    }

    /**
     * Initialize total models configuration and objects
     * @return $this
     */
    protected function _initModels()
    {
        $totalsConfig = \Yii::$app->params['totals'];
        foreach ($totalsConfig as $totalCode => $totalConfig) {
            $class = $totalConfig['class'];
            if (!empty($class)) {
                $this->_models[$totalCode] = $this->_initModelInstance($class, $totalCode, $totalConfig);
            }
        }
        return $this;
    }

    /**
     * Callback that uses after/before for comparison
     *
     * @param   array $a
     * @param   array $b
     * @return  int
     */
    protected function _compareTotals($a, $b)
    {
        $aCode = $a['_code'];
        $bCode = $b['_code'];
        if (in_array($aCode, $b['after']) || in_array($bCode, $a['before'])) {
            $res = -1;
        } elseif (in_array($bCode, $a['after']) || in_array($aCode, $b['before'])) {
            $res = 1;
        } else {
            $res = 0;
        }
        return $res;
    }

    /**
     * Callback that uses sort_order for comparison
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _compareSortOrder($a, $b)
    {
        if (!isset($a['sort_order']) || !isset($b['sort_order'])) {
            return 0;
        }
        if ($a['sort_order'] > $b['sort_order']) {
            $res = 1;
        } elseif ($a['sort_order'] < $b['sort_order']) {
            $res = -1;
        } else {
            $res = 0;
        }
        return $res;
    }
}
