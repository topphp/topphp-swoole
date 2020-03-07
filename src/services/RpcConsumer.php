<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/1 01:19
 * @author sleep <sleep@kaituocn.com>
 */

namespace Topphp\TopphpSwoole\services;

use Doctrine\Common\Annotations\AnnotationReader;
use ErrorException;
use Exception;
use ReflectionClass;
use ReflectionException;
use think\facade\App;
use Topphp\TopphpSwoole\annotation\Rpc;
use Topphp\TopphpSwoole\server\jsonrpc\Client;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ResultResponse;

class RpcConsumer
{
    /** @var Rpc $annotation */
    private static $annotation;

    /**
     * @var static
     */
    private static $instance;

    /**
     * @param $class
     * @return mixed
     * @author sleep
     */
    public static function make($class)
    {
        try {
            $reader           = App::make(AnnotationReader::class);
            $reflectionClass  = new ReflectionClass($class);
            $rpcAnnotation    = $reader->getClassAnnotation($reflectionClass, Rpc::class);
            self::$annotation = $rpcAnnotation;
            if (!self::$instance) {
                self::$instance = new static;
            }
            return self::$instance;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function __call($name, $arguments)
    {
        return $this->__request(
            uniqid($name . '_'),
            self::$annotation->serverName,
            self::$annotation->serviceName,
            $name,
            $arguments
        );
    }

    protected function __request($requestId, $serverName, $serviceName, $method, $arguments)
    {
        /** @var Client $rpcClient */
        $rpcClient  = App::make(Client::class);
        $methodName = $serverName . '@' . $serviceName . '@' . $method;
        // todo 现在是单请求形式, 以后要改成多请求组成一个数组形式 query可以多次
        $rpcClient->query($requestId, $methodName, $arguments);
        $encode = $rpcClient->encode();
        try {
            //  随机获取服务连接信息, 根据serverName查询服务地址.
            $server = $this->getCurrentConsumerNode($serverName);
            if (!$server) {
                throw new ErrorException("is not have this server: {$serverName}");
            }
            $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
            $client->set($server['options']);
            $client->connect($server['host'], $server['port']);
            $client->send($encode);
            $recv = $client->recv();
            if (!$recv) {
                throw new ErrorException($client->errMsg);
            }
            $responses = $rpcClient->decode($recv);
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
        } catch (Exception $e) {
            return [
                'code'    => ErrorResponse::INTERNAL_ERROR,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前消费节点
     * @param $serverName
     * @return array|bool
     * @throws Exception
     * @author sleep
     */
    private function getCurrentConsumerNode($serverName)
    {
        $clients = App::getInstance()->config->get('topphpServer.clients');
        foreach ($clients as $client) {
            if ($client['name'] === $serverName) {
                // todo 获取consul节点信息.不走本地

                switch ($client['balancer']) {
                    case 'random':
                        // 随机
                        $nodes        = $client['nodes'];
                        $currentIndex = random_int(1, count($nodes));
                        return [
                            'host'    => $nodes[$currentIndex - 1]['host'],
                            'port'    => $nodes[$currentIndex - 1]['port'],
                            'options' => $client['options'],
                        ];
                        break;
                    default:
                        break;
                }
            }
        }
        return false;
    }
}
