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
use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ResultResponse;

class RpcConsumerService
{
    /** @var Client $this */
    protected $client;

    public function request($requestId, $serverName, $serviceId, $method, $arguments)
    {

        $this->client = App::getInstance()->make(Client::class);
        $methodName   = $serverName . '@' . $serviceId . '@' . $method;
        // todo 现在是单请求形式, 以后要改成多请求组成一个数组形式 query可以多次
        $this->client->query($requestId, $methodName, $arguments);

        $encode = $this->client->encode();
        $client = App::getInstance()->make(\Swoole\Coroutine\Client::class, [SWOOLE_SOCK_TCP]);

        // todo 随机获取服务连接信息, 根据serverName查询服务地址.
        $client->connect('0.0.0.0', 9502);
        $client->send($encode);
        $request = $client->recv();
        try {
            $responses = $this->client->decode($request);
            $result    = [];

            foreach ($responses as $response) {
                if ($response instanceof ResultResponse) {
                    // todo 现在是单请求形式, 以后要改成多请求组成一个数组形式 改为 $result[]
                    $result = [
                        'id'    => $response->getId(),
                        'value' => $response->getValue()
                    ];
                } elseif ($response instanceof ErrorResponse) {
                    // todo 现在是单请求形式, 以后要改成多请求组成一个数组形式 改为 $result[]
                    $result = [
                        'id'      => $response->getId(),
                        'message' => $response->getMessage(),
                        'data'    => $response->getData(),
                        'code'    => $response->getCode(),
                    ];
                }
            }
            return $result;
        } catch (\ErrorException $e) {
            return $e->getMessage();
        }
    }

}
