<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/2/29 01:40
 * @author sleep <sleep@kaituocn.com>
 */

namespace Topphp\Test\jsonrpc;

use Topphp\TopphpSwoole\server\jsonrpc\Client;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ResultResponse;
use Topphp\TopphpSwoole\server\jsonrpc\Server;
use Topphp\TopphpSwoole\SwooleApp;
use Topphp\TopphpTesting\HttpTestCase;

class RpcTest extends HttpTestCase
{
    public function testRpcClient()
    {
        $c = new Client();
//        $c->query(1, 'echoPhrase1', ['hahaha']);
//        $c->query(2, 'echoPhrase');
        $c->query(3, 'echoPhrase', ['888']);

        try {
            $app    = new SwooleApp();
            $server = new Server($app);
            $encode = $c->encode();
            var_dump($encode);
            $res = $server->reply($encode);
            var_dump($res);
            $responses = $c->decode($res);
            $this->assertIsArray($responses);
            foreach ($responses as $response) {
                if ($response instanceof ResultResponse) {
                    $result = [
                        'id'    => $response->getId(),
                        'value' => $response->getValue()
                    ];
                    var_dump($result);
                } elseif ($response instanceof ErrorResponse) {
                    $result = [
                        'id'      => $response->getId(),
                        'message' => $response->getMessage(),
                        'data'    => $response->getData(),
                        'code'    => $response->getCode(),
                    ];
                    var_dump($result);
                }
            }
        } catch (\ErrorException $e) {
        } finally {
            $this->assertTrue(true);
        }
    }
}
