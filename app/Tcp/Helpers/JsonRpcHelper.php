<?php

namespace App\Tcp\Helpers;

use App\Tcp\Commands\StartCommand;
use Mix\Helper\JsonHelper;

/**
 * Class JsonRpcHelper
 * @package App\Tcp\Helpers
 * @author liu,jian <coder.keda@gmail.com>
 */
class JsonRpcHelper
{

    /**
     * Error
     * @param int $code
     * @param string $message
     * @param null $id
     * @return string
     */
    public static function error(int $code, string $message, $id = null)
    {
        $data = [
            'jsonrpc' => '2.0',
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
            'id'      => $id,
        ];
        return JsonHelper::encode($data, JSON_UNESCAPED_UNICODE). StartCommand::EOF;
    }

    /**
     * Result
     * @param $result
     * @param null $id
     * @return string
     */
    public static function result($result, $id = null)
    {
        $data = [
            'jsonrpc' => '2.0',
            'error'   => null,
            'result'  => $result,
            'id'      => $id,
        ];
        return JsonHelper::encode($data, JSON_UNESCAPED_UNICODE). StartCommand::EOF;
    }

    /**
     * Notification
     * @param string $method
     * @param $result
     * @return string
     */
    public static function notification(string $method, array $params)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method'  => $method,
            'params'  => $params,
            'id'      => null,
        ];
        return JsonHelper::encode($data, JSON_UNESCAPED_UNICODE). StartCommand::EOF;
    }

}
