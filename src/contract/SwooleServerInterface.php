<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 00:47
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\contract;

use Swoole\Server as SwooleServer;

interface SwooleServerInterface
{
    public function onConnect(SwooleServer $server, int $fd): void;

    public function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void;

    public function onTask(SwooleServer $server, $taskId, $fromId, $data): void;

    public function onClose(SwooleServer $server, int $fd, int $reactorId): void;
}
