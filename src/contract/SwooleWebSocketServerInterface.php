<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 00:47
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\contract;

use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\WebSocket\Frame as SwooleFrame;
use Swoole\WebSocket\Server as SwooleWebSocketServer;

interface SwooleWebSocketServerInterface
{
    public function onOpen(SwooleWebSocketServer $server, SwooleHttpRequest $request): void;

    public function onMessage(SwooleWebSocketServer $server, SwooleFrame $frame): void;

    public function onClose(SwooleWebSocketServer $server, int $fd): void;
}
