<?php

declare(strict_types=1);

namespace Topphp\Test;

use Datto\JsonRpc\Client;
use Datto\JsonRpc\Responses\ErrorResponse;
use Datto\JsonRpc\Responses\ResultResponse;
use Datto\JsonRpc\Server;
use Topphp\TopphpSwoole\SwooleApp;
use Topphp\TopphpTesting\TestCase;

class ExampleTest extends TestCase
{
    public $res;

    public function testRpcServerReturnData()
    {
        $app       = new SwooleApp();
        $server    = new Server($app);
        $this->res = $server->reply('{"jsonrpc":"2.0","method":"echoPhrase1","params":["hello rpc"],"id":1}');
        $this->assertNotEmpty($this->res);
    }

    public function testRpcClient()
    {
        $c = new Client();
        $c->query(9, 'echoPhrase1', ['hahaha']);
        try {
            $app       = new SwooleApp();
            $server    = new Server($app);
            $reply     = $server->reply($c->encode());
            $responses = $c->decode($reply);
            foreach ($responses as $response) {
                if ($response instanceof ResultResponse) {
                    $result = [
                        'id'    => $response->getId(),
                        'value' => $response->getValue()
                    ];
                    var_dump($result);
                } elseif ($response instanceof ErrorResponse) {
                    $error = [
                        'id'      => $response->getId(),
                        'message' => $response->getMessage(),
                        'code'    => $response->getCode(),
                        'data'    => $response->getData()
                    ];
                    var_dump($error);
                }
            }
        } catch (\ErrorException $e) {
            return $e->getMessage();
        }
        $this->assertTrue(true);
        return true;
    }
}
