<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 19:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use Swoole\Server as SwooleServer;
use think\facade\App;
use Topphp\TopphpSwoole\contract\SwooleServerInterface;
use Topphp\TopphpSwoole\SwooleEvent;

class TcpServer extends SwooleServer implements SwooleServerInterface
{
    public static function getEvents(): array
    {
        return [
            SwooleEvent::ON_CONNECT,
            SwooleEvent::ON_RECEIVE,
        ];
    }

    public static function onConnect(SwooleServer $server, int $fd): void
    {
        App::getInstance()->event->trigger(TopServerEvent::ON_TCP_CONNECT, [
            'server' => $server,
            'fd'     => $fd
        ]);
    }

    public static function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void
    {
        App::getInstance()->event->trigger(TopServerEvent::ON_TCP_RECEIVE, [
            'server'    => $server,
            'fd'        => $fd,
            'reactorId' => $reactorId,
            'data'      => $data
        ]);
    }
}
