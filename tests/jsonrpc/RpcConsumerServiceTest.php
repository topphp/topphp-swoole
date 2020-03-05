<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/4 16:29
 * @author sleep <sleep@kaituocn.com>
 */

namespace Topphp\Test\jsonrpc;

use Topphp\TopphpSwoole\services\RpcConsumerService;
use Topphp\TopphpTesting\HttpTestCase;

class RpcConsumerServiceTest extends HttpTestCase
{
    public function testConsumer()
    {
        $r   = new RpcConsumerService();
        $res = $r->request(
            time(),
            'film-server',
            'filmService',
            'test',
            [2, 2]
        );
        var_dump($res);
    }
}
