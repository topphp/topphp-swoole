<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 19:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\server;

use Swoole\Server as SwooleServer;
use topphp\swoole\contract\SwooleServerInterface;

class TcpServer extends SwooleServer implements SwooleServerInterface
{
    public function onConnect(SwooleServer $server, int $fd): void
    {
        // TODO: Implement onConnect() method.
    }

    public function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void
    {
        // TODO: Implement onReceive() method.
    }

    public function onTask(SwooleServer $server, $taskId, $fromId, $data): void
    {
        // TODO: Implement onTask() method.
    }

    public function onClose(SwooleServer $server, int $fd, int $reactorId): void
    {
        // TODO: Implement onClose() method.
    }
}
