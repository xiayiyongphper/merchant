<?php
namespace common\models;

use Yii;
use framework\components\TStringFuncFactory;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/12/21
 * Time: 20:10
 */
class Connector
{
    const EMERG = 0;  // Emergency: system is unusable
    const ALERT = 1;  // Alert: action must be taken immediately
    const CRIT = 2;  // Critical: critical conditions
    const ERR = 3;  // Error: error conditions
    const WARN = 4;  // Warning: warning conditions
    const NOTICE = 5;  // Notice: normal but significant condition
    const INFO = 6;  // Informational: informational messages
    const DEBUG = 7;  // Debug: debug messages
    const LOG_TARGET_FILE = 'file';
    const LOG_TARGET_DB = 'db';
    protected static $host = '172.16.10.203';
    protected static $port = 9000;
    protected static $instance;
    protected $fp;
    protected $socket;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        if (!$this->socket) {
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // 创建一个Socket
            socket_connect($this->socket, self::$host, self::$port);    //  连接
        }
    }

    protected function pack($json)
    {
        return pack('N',  TStringFuncFactory::create()->strlen($json)) . $json;
    }

    public function send($data, $receive = false)
    {
        $result = socket_write($this->socket, $this->pack(json_encode($data)));
        if ($receive) {
            $length = socket_read($this->socket, 4);
            $length = unpack('N', $length);
            $result = socket_read($this->socket, $length[1]);
        }
        return $result;
    }

    public function __destruct()
    {
        socket_close($this->socket);
    }
}