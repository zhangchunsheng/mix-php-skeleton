<?php

namespace App\Console\Commands;

use App\Console\Workers\CoroutinePoolWorker;
use Mix\Concurrent\CoroutinePool\Dispatcher;
use Swoole\Coroutine\Channel;

/**
 * Class CoroutinePoolCommand
 * @package App\Console\Commands
 * @author liu,jian <coder.keda@gmail.com>
 */
class CoroutinePoolCommand
{

    /**
     * 主函数
     * @throws \PhpDocReader\AnnotationException
     * @throws \ReflectionException
     */
    public function main()
    {
        $maxWorkers = 20;
        $maxQueue   = 10;
        $jobQueue   = new Channel($maxQueue);
        $dispatch   = new Dispatcher($jobQueue, $maxWorkers);
        $dispatch->start(CoroutinePoolWorker::class);
        // 投放任务
        for ($i = 0; $i < 1000; $i++) {
            $jobQueue->push($i);
        }
        // 停止
        $dispatch->stop();
    }

}
