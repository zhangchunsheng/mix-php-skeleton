<?php

namespace App\Console\Commands;

use Mix\Concurrent\Sync\WaitGroup;

/**
 * Class WaitGroupCommand
 * @package App\Console\Commands
 * @author liu,jian <coder.keda@gmail.com>
 */
class WaitGroupCommand
{

    /**
     * 主函数
     */
    public function main()
    {
        $wg = WaitGroup::new();
        for ($i = 0; $i < 2; $i++) {
            $wg->add(1);
            xgo([$this, 'foo'], $wg);
        }
        $wg->wait();
        println('all done!');
    }

    /**
     * 查询数据
     * @param WaitGroup $wg
     */
    public function foo(WaitGroup $wg)
    {
        xdefer(function () use ($wg) {
            $wg->done();
        });
        println('work');
        //throw new \RuntimeException('ERROR');
    }

}
