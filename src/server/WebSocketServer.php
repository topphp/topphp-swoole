<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 19:17
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\WebSocket\Frame as SwooleFrame;
use Swoole\WebSocket\Server as SwooleWebSocketServer;
use Topphp\TopphpSwoole\contract\SwooleWebSocketServerInterface;
use Topphp\TopphpSwoole\SwooleEvent;

class WebSocketServer extends SwooleWebSocketServer implements SwooleWebSocketServerInterface
{
    public static function getEvents(): array
    {
        return [
            SwooleEvent::ON_OPEN,
            SwooleEvent::ON_MESSAGE,
            SwooleEvent::ON_HAND_SHAKE,
        ];
    }

    public static function onOpen(SwooleWebSocketServer $server, SwooleHttpRequest $request): void
    {
        echo "$request->fd\n";
    }

    public static function onHandShake(SwooleHttpRequest $request, SwooleHttpResponse $response): void
    {
        // TODO: Implement onOpen() method.
    }

    public static function onMessage(SwooleWebSocketServer $server, SwooleFrame $frame): void
    {
        // TODO: Implement onMessage() method.
    }
}
