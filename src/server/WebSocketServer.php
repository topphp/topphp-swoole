<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 19:17
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\server;

use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\WebSocket\Frame as SwooleFrame;
use Swoole\WebSocket\Server as SwooleWebSocketServer;
use topphp\swoole\contract\SwooleWebSocketServerInterface;
use topphp\swoole\SwooleEvent;

class WebSocketServer extends SwooleWebSocketServer implements SwooleWebSocketServerInterface
{
    public $events = [
        SwooleEvent::ON_OPEN,
        SwooleEvent::ON_MESSAGE,
        SwooleEvent::ON_HAND_SHAKE,
        SwooleEvent::ON_CLOSE
    ];

    public function onOpen(SwooleWebSocketServer $server, SwooleHttpRequest $request): void
    {
        // TODO: Implement onOpen() method.
    }

    public function onHandShake(SwooleHttpRequest $request, SwooleHttpResponse $response): void
    {
        // TODO: Implement onOpen() method.
    }

    public function onMessage(SwooleWebSocketServer $server, SwooleFrame $frame): void
    {
        // TODO: Implement onMessage() method.
    }


    public function onClose(SwooleWebSocketServer $server, int $fd): void
    {
        // TODO: Implement onClose() method.
    }
}
