<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/16 23:49
 * Author: sleep <sleep@kaituocn.com>
 */

namespace Topphp\Test\coroutine;

use Swoole\Coroutine\Scheduler;
use Swoole\Coroutine;
use Topphp\TopphpSwoole\coroutine\Parallel;
use Topphp\TopphpSwoole\exception\ParallelExecutionException;
use Topphp\TopphpTesting\TestCase;

class ParallelTest extends TestCase
{
    public function testAdd()
    {
        $parallel = new Parallel();
        $parallel->add(function () {
            sleep(2);
            return Coroutine::getCid();
        });
        $parallel->add(function () {
            sleep(2);
            return Coroutine::getCid();
        });
        try {
            // $results 结果为 [1, 2]
            $results = $parallel->wait();
            var_dump($results);
        } catch (ParallelExecutionException $e) {
            // 获取协程中的返回值。
            var_dump($e->getResults());
            // 获取协程中出现的异常。
            var_dump($e->getThrowables());
        }
        $this->assertEquals($results, [2, 3]);
    }

    public function testEasy()
    {
        $result = parallels([
            function () {
                sleep(3);
                return Coroutine::getCid();
            },
            function () {
                sleep(3);
                return Coroutine::getCid();
            }
        ]);
        var_dump($result);
        $this->assertIsArray($result);
    }
}
