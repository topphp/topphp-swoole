<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/2/29 19:01
 * @author sleep <sleep@kaituocn.com>
 */

namespace Topphp\TopphpSwoole\server;

/**
 * 监听事件名称枚举类
 * Class TopServerEvent
 * @package Topphp\TopphpSwoole\server
 */
class TopServerEvent
{
    // 全局
    const ON_TASK           = 'topphp.BaseServer.onTask';
    const ON_FINISH         = 'topphp.BaseServer.onFinish';
    const ON_CLOSE          = 'topphp.BaseServer.onClose';
    const ON_PIPE_MESSAGE   = 'topphp.BaseServer.onPipeMessage';
    const MAIN_WORKER_START = 'topphp.BaseServer.mainWorkerStart';
    const ON_WORKER_START   = 'topphp.BaseServer.onWorkerStart';
    const ON_WORKER_STOP    = 'topphp.BaseServer.onWorkerStop';
    const ON_WORKER_ERROR   = 'topphp.BaseServer.onWorkerError';
    const ON_MANAGER_STOP   = 'topphp.BaseServer.onManagerStop';
    // HTTP
    const ON_REQUEST = 'topphp.HttpServer.onRequest';
    // RPC
    const ON_RPC_CONNECT = 'topphp.RpcServer.onConnect';
    const ON_RPC_RECEIVE = 'topphp.RpcServer.onReceive';
    // TCP
    const ON_TCP_CONNECT = 'topphp.TcpServer.onConnect';
    const ON_TCP_RECEIVE = 'topphp.TcpServer.onReceive';
    // WEBSOCKET
    const ON_OPEN      = 'topphp.WebSocketServer.onOpen';
    const ON_HANDSHAKE = 'topphp.WebSocketServer.onHandShake';
    const ON_MESSAGE   = 'topphp.WebSocketServer.onMessage';
}
