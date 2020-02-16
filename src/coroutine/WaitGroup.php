<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/16 23:14
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\coroutine;

use Swoole\Coroutine\Channel;

class WaitGroup extends \Swoole\Coroutine\WaitGroup
{
//    private $count = 0;
//    private $chan;
//
//    /**
//     * @desc 初始化一个channel
//     */
//    public function __construct()
//    {
//        $this->chan = new Channel();
//    }
//
//    public function add(int $count = 1): void
//    {
//        $this->count = $count;
//    }
//
//    public function done(): void
//    {
//        $this->chan->push(true);
//    }
//
//    public function wait(): void
//    {
//        while ($this->count--) {
//            $this->chan->pop();
//        }
//    }
}
