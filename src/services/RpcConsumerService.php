<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/4 16:17
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\services;

use think\facade\App;
use Topphp\TopphpSwoole\server\jsonrpc\Client;

class RpcConsumerService
{
    protected $client;

    public function request($requestId, $serverName, $serviceId, $method, $arguments)
    {
        //todo 根据serverName查询服务地址.

        //todo 改成容器再试试
        $this->client = new Client();
        $methodName   = $serverName . '@' . $serviceId . '@' . $method;
        $this->client->query($requestId, $methodName, $arguments);
        $encode = $this->client->encode();
        $client = App::getInstance()->make(\Swoole\Coroutine\Client::class, [SWOOLE_SOCK_TCP]);
//        App::getInstance()->get($serverName . '@' . $serviceId);
        $client->connect('0.0.0.0', 9502);
        $client->send($encode);
        $request = $client->recv();
        $client->close();
        return $request;
    }

}
