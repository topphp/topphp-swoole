<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 00:37
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\contract;

use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Server as SwooleServer;

interface SwooleHttpServerInterface
{
    public static function getEvents(): array;

    public static function onStart(SwooleHttpServer $server): void;

    public static function onRequest(SwooleHttpRequest $request, SwooleHttpResponse $response): void;

    public static function onTask(SwooleServer $server, $taskId, $fromId, $data): void;
}
