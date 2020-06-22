<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package ticket-check-server
 * @date 2020/6/19 18:48
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server\mqtt;

interface MQTTInterface
{
    // 1
    public function onMqConnect($server, int $fd, $fromId, $data);

    // 12
    public function onMqPingreq($server, int $fd, $fromId, $data): bool;

    // 14
    public function onMqDisconnect($server, int $fd, $fromId, $data): bool;

    // 3
    public function onMqPublish($server, int $fd, $fromId, $data);

    // 8
    public function onMqSubscribe($server, int $fd, $fromId, $data);

    // 10
    public function onMqUnsubscribe($server, int $fd, $fromId, $data);
}
