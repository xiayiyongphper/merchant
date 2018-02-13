<?php
namespace service\components;

use common\models\Log;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/27
 * Time: 12:24
 */
class Logger
{
    const SOURCE = ENV_SYS_NAME;
    const MESSAGE_TYPE_INFO = 'info';
    const MESSAGE_TYPE_DEBUG = 'debug';
    const MESSAGE_TYPE_EXCEPTION = 'exception';

    public static function log($traceId, $data, $type = self::MESSAGE_TYPE_INFO)
    {
        if (!$traceId) {
            return false;
        }

        if ($data instanceof \Exception) {
            $type = self::MESSAGE_TYPE_EXCEPTION;
            $text = $data->__toString();
        } elseif (is_object($data) || is_array($data)) {
            $text = print_r($data, true);
        } else {
            $text = $data;
        }
        if ($type != self::MESSAGE_TYPE_EXCEPTION) {
            return true;
        }
        $log = new Log();
        $log->trace_id = $traceId;
        $log->source = self::SOURCE;
        $log->content = $text;
        $log->type = $type;
        $log->save();
    }
}