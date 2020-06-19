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
use Topphp\TopphpSwoole\server\mqtt\MQTTInterface;

class MQTTServer extends TcpServer implements MQTTInterface
{
    private static $config;

    public static function onReceive(SwooleServer $server, int $fd, int $reactorId, string $data): void
    {
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
                case MQTT::PUBLISH: // 发布消息
                case MQTT::SUBSCRIBE: // 订阅
                case MQTT::UNSUBSCRIBE: // 取消订阅
                    App::getInstance()->event->trigger(TopServerEvent::ON_MQTT_RECEIVE, [
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
    }

    public static function onMqConnect($server, int $fd, $fromId, $data)
    {
        if ($data['protocol_name'] != "MQTT") {
            // 如果协议名不正确服务端可以断开客户端的连接，也可以按照某些其它规范继续处理CONNECT报文
            $server->close($fd);
            return false;
        }
    }

    public static function onMqPingreq($server, int $fd, $fromId, $data): bool
    {
        // TODO: Implement onMqPingreq() method.
    }

    public static function onMqDisconnect($server, int $fd, $fromId, $data): bool
    {
        // TODO: Implement onMqDisconnect() method.
    }

    public static function onMqPublish($server, int $fd, $fromId, $data)
    {
        // TODO: Implement onMqPublish() method.
    }

    public static function onMqSubscribe($server, int $fd, $fromId, $data)
    {
        // TODO: Implement onMqSubscribe() method.
    }

    public static function onMqUnsubscribe($server, int $fd, $fromId, $data)
    {
        // TODO: Implement onMqUnsubscribe() method.
    }
}
