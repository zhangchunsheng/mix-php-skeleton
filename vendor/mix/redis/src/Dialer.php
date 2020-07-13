<?php

namespace Mix\Redis;

use Mix\Bean\BeanInjector;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Class Dialer
 * @package Mix\Database
 */
class Dialer
{

    /**
     * 全局超时
     * @var float
     */
    public $timeout = 5.0;

    /**
     * 重连间隔
     * @var int
     */
    public $retryInterval = 0;

    /**
     * 读取超时
     * phpredis >= 3.1.3
     * @var float
     */
    public $readTimeout = -1;

    /**
     * 最大连接数
     * @var int
     */
    public $maxActive = 10;

    /**
     * 最多可空闲连接数
     * @var int
     */
    public $maxIdle = 5;

    /**
     * 事件调度器
     * @var EventDispatcherInterface
     */
    public $dispatcher;

    /**
     * AbstractConnection constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        BeanInjector::inject($this, $config);
    }

    /**
     * Dial
     * @param string $host
     * @param int $port
     * @param string $password
     * @param int $database
     * @return Redis
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function dial(string $host, int $port = 6379, string $password = '', int $database = 0): Redis
    {
        $redis             = new Redis(
            $host,
            $port,
            $password,
            $database,
            $this->timeout,
            $this->retryInterval,
            $this->readTimeout
        );
        $redis->maxActive  = $this->maxActive;
        $redis->maxIdle    = $this->maxIdle;
        $redis->dispatcher = $this->dispatcher;
        return $redis;
    }

}
