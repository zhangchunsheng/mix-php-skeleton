<?php

namespace Mix\Pool;

/**
 * Trait ObjectTrait
 * @package Mix\Pool
 * @author liu,jian <coder.keda@gmail.com>
 */
trait ObjectTrait
{

    /**
     * @var AbstractObjectPool
     */
    public $pool;

    /**
     * 丢弃连接
     * @return bool
     */
    public function __discard()
    {
        if (isset($this->pool)) {
            return $this->pool->discard($this);
        }
        return false;
    }

    /**
     * 归还连接
     * @return bool
     */
    public function __return()
    {
        if (isset($this->pool)) {
            return $this->pool->return($this);
        }
        return false;
    }

}
