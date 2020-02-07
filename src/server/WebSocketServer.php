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
    public static function getEvents(): array
    {
        return [
            SwooleEvent::ON_START,
            SwooleEvent::ON_OPEN,
            SwooleEvent::ON_MESSAGE,
            SwooleEvent::ON_HAND_SHAKE,
            SwooleEvent::ON_CLOSE,
            SwooleEvent::ON_TASK
        ];
    }

    public static function onStart(WebSocketServer $server): void
    {
        echo "websocket server is started: {$server->host}:{$server->port}\n";
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

    public static function onClose(SwooleWebSocketServer $server, int $fd): void
    {
        // TODO: Implement onClose() method.
    }

    public static function onTask(SwooleWebSocketServer $server, $taskId, $fromId, $data): void
    {
        echo "New AsyncTask[id=$taskId]\n";
        $server->finish("$data -> OK");
    }
}
