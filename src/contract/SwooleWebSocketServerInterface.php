<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 00:47
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\contract;

use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\WebSocket\Frame as SwooleFrame;
use Swoole\WebSocket\Server as SwooleWebSocketServer;

interface SwooleWebSocketServerInterface
{
    public static function getEvents(): array;

    public static function onOpen(SwooleWebSocketServer $server, SwooleHttpRequest $request): void;

    public static function onHandShake(SwooleHttpRequest $request, SwooleHttpResponse $response): void;

    public static function onMessage(SwooleWebSocketServer $server, SwooleFrame $frame): void;
}
