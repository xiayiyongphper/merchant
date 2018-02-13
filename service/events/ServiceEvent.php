<?php
namespace service\events;

use yii\base\Event;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 15:31
 */
class ServiceEvent extends Event
{
    /**
     * @var array
     */
    protected $_eventData;
    protected $_traceId;

    const PRODUCT_SAVE_AFTER = 'product_save_after';

    /**
     * @return array
     */
    public function getEventData()
    {
        return $this->_eventData;
    }

    /**
     * @param array $eventData
     */
    public function setEventData($eventData)
    {
        $this->_eventData = $eventData;
    }

    /**
     * @return mixed
     */
    public function getTraceId()
    {
        return $this->_traceId;
    }

    /**
     * @param mixed $traceId
     */
    public function setTraceId($traceId)
    {
        $this->_traceId = $traceId;
    }

}