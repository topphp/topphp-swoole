<?php

declare(strict_types=1);

namespace Topphp\Test;

use Datto\JsonRpc\Server;
use PHPUnit\Framework\TestCase;
use Topphp\TopphpSwoole\SwooleApp;

class ExampleTest extends TestCase
{
    public function testRpcServerReturnData()
    {
        $app    = new SwooleApp();
        $server = new Server($app);
        $re     = $server->reply('{"jsonrpc":"2.0","method":"echoPhrase1","params":["hello rpc"],"id":1}');
        var_dump($re);
        $this->assertNotEmpty($re);
    }
}
