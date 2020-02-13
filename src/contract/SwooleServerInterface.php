<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 00:47
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\contract;

use Swoole\Server as SwooleServer;

interface SwooleServerInterface
{
    public static function getEvents(): array;

    public static function onConnect(SwooleServer $server, int $fd): void;

    public static function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void;
}
