<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/16 23:17
 * Author: sleep <sleep@kaituocn.com>
 */

namespace Topphp\Test\coroutine;

use PHPUnit\Framework\TestCase;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Topphp\TopphpSwoole\coroutine\WaitGroup;

class WaitGroupTest extends TestCase
{
    public function testDone()
    {
        $wg     = new WaitGroup();
        $result = [];
        // 添加2个协程队列
        $wg->add(2);
        //启动第一个协程
        Coroutine::create(function () use ($wg, &$result) {
            //启动一个协程客户端client，请求淘宝首页
            $cli = new Client('www.taobao.com', 443, true);
            $cli->setHeaders([
                'Host'            => "www.taobao.com",
                "User-Agent"      => 'Chrome/49.0.2587.3',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Encoding' => 'gzip',
            ]);
            $cli->get('/');

            $result['a'] = 'a';
//                $result['taobao'] = $cli->getBody();
            $cli->close();
            $wg->done();
        });

        //启动第二个协程
        Coroutine::create(function () use ($wg, &$result) {
            //启动一个协程客户端client，请求百度首页
            $cli = new Client('www.baidu.com', 443, true);
            $cli->setHeaders([
                'Host'            => "www.baidu.com",
                "User-Agent"      => 'Chrome/49.0.2587.3',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Encoding' => 'gzip',
            ]);
            $cli->get('/');
            $result['b'] = 'b';
//                $result['baidu'] = $cli->getBody();
            $cli->close();
            $wg->done();
        });

        //挂起当前协程，等待所有任务完成后恢复
        $wg->wait();
        //这里 $result 包含了 2 个任务执行结果
        var_dump($result);
        $this->assertTrue(true);
    }
}
