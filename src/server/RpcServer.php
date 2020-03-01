<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/12 23:39
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use RuntimeException;
use Swoole\Server as SwooleServer;
use think\facade\App;
use Topphp\TopphpSwoole\contract\SwooleServerInterface;
use Topphp\TopphpSwoole\server\jsonrpc\Client;
use Topphp\TopphpSwoole\server\jsonrpc\Packer;
use Topphp\TopphpSwoole\server\jsonrpc\Server;
use Topphp\TopphpSwoole\SwooleApp;

class RpcServer extends TcpServer implements SwooleServerInterface
{
    public static function onConnect(SwooleServer $server, int $fd): void
    {
        App::getInstance()->event->trigger(TopServerEvent::ON_RPC_CONNECT, ['server' => $server, 'fd' => $fd]);
    }

    public static function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void
    {
        App::getInstance()->event->trigger(TopServerEvent::ON_RPC_RECEIVE, [
            'server'    => $server,
            'fd'        => $fd,
            'reactorId' => $reactorId,
            'data'      => $data
        ]);
        self::buildRpcRequest($server, $fd, $reactorId, $data);
    }

    private static function buildRpcRequest(SwooleServer $server, int $fd, int $reactorId, string $data)
    {
        try {
            $data = Packer::unpack($data);
            [$class] = explode('@', $data['id']);
            $app       = App::getInstance()->getService($class);
            $rpcServer = App::getInstance()->make(Server::class, [$app]);
            $reply     = $rpcServer->reply(Packer::pack($data));
            $server->send($fd, $reply);
        } catch (\Exception $e) {
            // todo 这块处理不好,以后优化
            $rpcServer = App::getInstance()->make(Server::class);
            $reply     = $rpcServer->reply($data);
            $server->send($fd, $reply);
        }
    }
}
