<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/6/19 18:51
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use Swoole\Server as SwooleServer;
use think\facade\App;
use Topphp\TopphpSwoole\server\mqtt\MQTT;

class MQTTServer extends TcpServer
{
    private static $config;

    public static function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void
    {
        App::getInstance()->event->trigger(TopServerEvent::ON_TCP_RECEIVE, [
            'server'    => $server,
            'fd'        => $fd,
            'reactorId' => $reactorId,
            'data'      => $data
        ]);
        try {
            $data = MQTT::decode($data);
            if (is_array($data) && isset($data['cmd'])) {
                switch ($data['cmd']) {
                    case MQTT::PINGREQ: // 心跳请求
                        App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_PINGREQ, [
                            'server'    => $server,
                            'fd'        => $fd,
                            'reactorId' => $reactorId,
                            'data'      => $data
                        ]);
                        // 返回心跳响应
                        $server->send($fd, MQTT::getAck(['cmd' => 13]));
                        break;
                    case MQTT::CONNECT: // 连接
                        if ($data['protocol_name'] != "MQTT") {
                            // 如果协议名不正确服务端可以断开客户端的连接，也可以按照某些其它规范继续处理CONNECT报文
                            $server->close($fd);
                        }
                        App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_CONNECT, [
                            'server'    => $server,
                            'fd'        => $fd,
                            'reactorId' => $reactorId,
                            'data'      => $data
                        ]);
                        break;
                    case MQTT::DISCONNECT: // 客户端断开连接
                        App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_DISCONNECT, [
                            'server'    => $server,
                            'fd'        => $fd,
                            'reactorId' => $reactorId,
                            'data'      => $data
                        ]);
                        if ($server->exist($fd)) {
                            $server->close($fd);
                        }
                        break;
                    case MQTT::PUBLISH: // 发布消息
                        App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_PUBLISH, [
                            'server'    => $server,
                            'fd'        => $fd,
                            'reactorId' => $reactorId,
                            'data'      => $data
                        ]);
                        break;
                    case MQTT::SUBSCRIBE: // 订阅
                        App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_SUBSCRIBE, [
                            'server'    => $server,
                            'fd'        => $fd,
                            'reactorId' => $reactorId,
                            'data'      => $data
                        ]);
                        break;
                    case MQTT::UNSUBSCRIBE: // 取消订阅
                        App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_UNSUBSCRIBE, [
                            'server'    => $server,
                            'fd'        => $fd,
                            'reactorId' => $reactorId,
                            'data'      => $data
                        ]);
                        break;
                }
            } else {
                $server->close($fd);
            }
        } catch (\Exception $e) {
            $server->close($fd);
        }
    }
}
