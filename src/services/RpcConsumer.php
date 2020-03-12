<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/1 01:19
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\services;

use Exception;
use ErrorException;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use Swoole\Coroutine;
use think\facade\App;
use Topphp\TopphpConsul\consul\Health;
use Topphp\TopphpLog\Log;
use Topphp\TopphpPool\rpc\RpcConfig;
use Topphp\TopphpPool\rpc\RpcPool;
use Topphp\TopphpSwoole\annotation\Rpc;
use Topphp\TopphpSwoole\coroutine\Context;
use Topphp\TopphpSwoole\server\jsonrpc\Client;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ResultResponse;

class RpcConsumer
{
    /** @var Rpc */
    private static $annotation;

    /**
     * @var static
     */
    private static $instance;

    /**
     * @var array 服务器节点
     */
    private $node;

    /**
     * @var Health
     */
    private $health;

    /**
     * @var RpcConfig
     */
    protected $rpcConfig;

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
            self::$annotation = $reader->getClassAnnotation($reflectionClass, Rpc::class);
            if (!self::$instance) {
                self::$instance = new static;
            }
            // 服务发现 - 随机获取服务连接信息,获取健康节点.
            self::$instance->node = self::$instance->getCurrentConsumerNode(self::$annotation);
            if (!self::$instance->node) {
                throw new ErrorException("is not have this server: " . self::$annotation->serverName);
            }
            return self::$instance;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function __call($method, $arguments)
    {
        $fullName = self::$annotation->serverName . '@' .
            self::$annotation->serviceName . '@' .
            $method;
        return $this->__request(
            uniqid($method . '_'),
            $fullName,
            $arguments
        );
    }

    protected function getConnection($encode)
    {
        $class = spl_object_hash($this) . '.Connection';
        if (Context::has($class)) {
            return Context::get($class);
        }
        /** @var RpcPool $pool */
        $pool   = App::make(RpcPool::class, [
            $this->rpcConfig,
            $this->rpcConfig->getMaxConnections()
        ]);
        $client = $pool->get();
        $client->send($encode);
        Coroutine::defer(function () use ($pool, $client) {
            $pool->put($client);
        });
        return Context::set($class, $client);
    }

    private function __request($requestId, $methodName, $arguments)
    {
        /** @var Client $rpcClient */
        $rpcClient = App::make(Client::class);
        // todo 现在是单请求形式, 以后要改成多请求组成一个数组形式 query可以多次
        $rpcClient->query($requestId, $methodName, $arguments);
        $encode = $rpcClient->encode();
        try {
            $client = $this->getConnection($encode);
            $recv   = $client->recv($this->rpcConfig->getWaitTimeout());
            if ($recv === '') {
                $client->close();
                throw new ErrorException($client->errMsg);
            }
            if ($recv === false) {
                throw new \RuntimeException($client->errMsg, $client->errCode);
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

    private function initConfig($config)
    {
        /** @var RpcConfig $rpcConfig */
        $rpcConfig = App::make(RpcConfig::class, []);
        if (isset($config) && !empty($config)) {
            $rpcConfig
                ->setNode($this->node)
                ->setOptions($config['options'])
                ->setMinConnections($config['pool']['min_connections'])
                ->setMaxConnections($config['pool']['max_connections'])
                ->setConnectTimeout($config['pool']['connect_timeout'])
                ->setMaxIdleTime($config['pool']['max_idle_time'])
                ->setWaitTimeout($config['pool']['wait_timeout']);
        }
        return $rpcConfig;
    }

    /**
     * 获取当前消费节点
     * @param Rpc $service
     * @return array|bool
     * @throws Exception
     * @author sleep
     */
    private function getCurrentConsumerNode(Rpc $service)
    {
        $clients = App::getInstance()->config->get('topphpServer.clients');
        foreach ($clients as $client) {
            if ($client['name'] === $service->serverName) {
                // 获取consul节点信息.不走本地
                if ($service->publish && $service->publish === 'consul') {
                    $nodes = $this->getConsulNodes($service->serviceName);
                } else {
                    $nodes = $client['nodes'];
                }
                // todo 服务治理
                switch ($client['balancer']) {
                    case 'random':
                        // 随机模式
                        $currentIndex    = random_int(1, count($nodes));
                        $this->node      = [
                            'host' => $nodes[$currentIndex - 1]['host'],
                            'port' => $nodes[$currentIndex - 1]['port'],
                        ];
                        $this->rpcConfig = $this->initConfig($client);
                        return $this->node;
                        break;
                    default:
                        return [];
                        break;
                }
            }
        }
        return false;
    }

    /**
     * 获取当前服务consul健康节点
     * @param string $serviceName
     * @return array
     * @author sleep
     */
    private function getConsulNodes(string $serviceName): array
    {
        $this->health = App::make(Health::class);
        $services     = $this->health->service($serviceName)->json();
        $nodes        = [];
        foreach ($services as $node) {
            $passing = true;
            $service = $node['Service'] ?? [];
            $checks  = $node['Checks'] ?? [];
            foreach ($checks as $check) {
                $status = $check['Status'] ?? false;
                if ($status !== 'passing') {
                    $passing = false;
                }
            }
            if ($passing) {
                $address = $service['Address'] ?? '';
                $port    = (int)$service['Port'] ?? 0;
                // TODO Get and set the weight property.
                $weight  = 0;
                $nodes[] = [
                    'host'   => $address,
                    'port'   => $port,
                    'weight' => $weight
                ];
            }
        }
        return $nodes;
    }
}
