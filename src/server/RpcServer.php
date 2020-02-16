<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/12 23:39
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use Swoole\Server as SwooleServer;
use Topphp\TopphpSwoole\contract\SwooleServerInterface;

class RpcServer extends TcpServer implements SwooleServerInterface
{

    public static function onConnect(SwooleServer $server, int $fd): void
    {
        // TODO: Implement onConnect() method.
    }

    public static function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void
    {
        $request  = null;
        $response = null;
    }

//    private static function buildRequest(int $fd, int $fromId, string $data)
//    {
//    }
//
//    private static function buildResponse(int $fd, SwooleServer $server, Response $response)
//    {
//
//    }

//    public static function mySend(SwooleServer $server, int $fd, $response): void
//    {
//
//        $server->send($fd, (string)$response->getBody());
//    }
}
