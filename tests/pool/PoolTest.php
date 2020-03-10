<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/10 15:17
 * @author sleep <sleep@kaituocn.com>
 */

namespace Topphp\Test\pool;

use Swoole\Coroutine\Client;
use Topphp\TopphpSwoole\pool\RpcConnection;
use Topphp\TopphpSwoole\pool\RpcPool;
use Topphp\TopphpTesting\HttpTestCase;

class PoolTest extends HttpTestCase
{
    public function testIndex()
    {
        /** @var RpcPool $p */
        $p = $this->app->make(RpcPool::class, [
            [
                'connect_timeout' => 666,
                'node'            => [
                    'host' => '192.168.31.108',
                    'port' => 9503
                ]
            ]
        ]);

        /** @var Client $c */
        $c = $p->getInstance()->getConnection();
        var_dump($c->connected);
        $c->send('{"jsonrpc":"2.0","method":"cinema-server@cinemaService@test1","params":[2,2],"id":"test1_5e67998364d91"}');

        var_dump($c->recv());
    }
}
