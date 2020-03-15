<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/12 23:39
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use Exception;
use Swoole\Server as SwooleServer;
use think\facade\App;
use Topphp\TopphpSwoole\server\jsonrpc\Evaluator;
use Topphp\TopphpSwoole\server\jsonrpc\Packer;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;
use Topphp\TopphpSwoole\server\jsonrpc\Server;

class RpcServer extends TcpServer
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
            // todo 现在是单请求形式, 以后要改成多请求组成一个数组形式
            $data = Packer::unpack($data);
            [$serverName, $serviceName, $method] = explode('@', $data['method']);
            /** @var Evaluator $rpcService */
            $rpcService     = App::getInstance()->make($serviceName);
            $rpcServer      = App::getInstance()->make(Server::class, [$rpcService]);
            $data['method'] = $method;
            $reply          = $rpcServer->reply(Packer::pack($data));
            $server->send($fd, $reply);
        } catch (Exception $e) {
            $data = [
                'jsonrpc' => Server::VERSION,
                'id'      => $fd,
                'error'   => [
                    'code'    => ErrorResponse::INTERNAL_ERROR,
                    'message' => $e->getMessage()
                ]
            ];
            $server->send($fd, Packer::pack($data));
        }
    }
}
