<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/12 23:39
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use Swoole\Server as SwooleServer;
use think\facade\App;
use Topphp\TopphpSwoole\server\jsonrpc\Evaluator;
use Topphp\TopphpSwoole\server\jsonrpc\exceptions\MethodException;
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
            $data = Packer::unpack($data);
            [$serverName, $method] = explode('@', $data['method']);
            /** @var SwooleServer\Port $port */
            foreach ($server->ports as $port) {
                try {
                    // 判断该实例是否存在
                    /** @var SwooleServer\Port $rpcServer */
                    $rpcServer = App::getInstance()->get($serverName . ':' . $port->port);
                } catch (\Exception $e) {
                    continue;
                }
                if ($rpcServer && ($rpcServer->port === $port->port)) {
                    $rpcService = App::getInstance()->get($serverName);
                    if (!$rpcService) {
                        throw new MethodException();
                    }
                    $data['method'] = $method;
                    /** @var Evaluator $rpcService */
                    $rpcServer = new Server($rpcService);
                    $reply     = $rpcServer->reply(Packer::pack($data));
                    $server->send($fd, $reply);
                } else {
                    throw new MethodException();
                }
            }
        } catch (\Exception $e) {
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
