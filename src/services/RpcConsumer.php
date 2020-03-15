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
use InvalidArgumentException;
use ReflectionClass;
use Swoole\Coroutine;
use think\facade\App;
use Topphp\TopphpConsul\consul\Health;
use Topphp\TopphpPool\rpc\Node;
use Topphp\TopphpPool\rpc\RpcConfig;
use Topphp\TopphpPool\rpc\RpcPool;
use Topphp\TopphpSwoole\annotation\Rpc;
use Topphp\TopphpSwoole\balancer\BalancerAdapter;
use Topphp\TopphpSwoole\balancer\RandomBalancer;
use Topphp\TopphpSwoole\contract\BalancerInterface;
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
     * @var Node 服务器节点
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
     * @var BalancerAdapter
     */
    protected $balancerAdapter;

    protected $balancerMode = RandomBalancer::class;

    /**
     * @param $class
     * @return mixed
     * @author sleep
     */
    public static function make($class)
    {
        try {
            $reader                        = App::make(AnnotationReader::class);
            $reflectionClass               = new ReflectionClass($class);
            self::$annotation              = $reader->getClassAnnotation($reflectionClass, Rpc::class);
            self::$annotation->serviceName = $class;
            if (!self::$instance) {
                self::$instance = new static;
            }
            // 获取服务的规则:随机、顺序。。。
            self::$instance->balancerAdapter = App::make(BalancerAdapter::class);
            // 服务发现 - 随机获取服务连接信息,获取健康节点.
            $nodeConfig           = self::$instance->getNodeConfig();
            self::$instance->node = self::$instance->getCurrentConsumerNode(self::$annotation, $nodeConfig);
            if (!self::$instance->node) {
                throw new ErrorException("is not have this server: " . self::$annotation->serverName);
            }
            self::$instance->rpcConfig = self::$instance->initRpcConfig($nodeConfig);
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

    private function initRpcConfig($config)
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
     * @param array $nodeConfig
     * @return Node
     * @author sleep
     */
    private function getCurrentConsumerNode(Rpc $service, array $nodeConfig): Node
    {
        $nodes           = [];
        $refreshCallback = null;
        // 获取consul节点信息.不走本地
        if ($service->publish && $service->publish === 'consul') {
            $nodes           = $this->getConsulNodes($service->serviceName);
            $refreshCallback = function () use ($service) {
                return $this->getConsulNodes($service->serviceName);
            };
        } elseif (isset($nodeConfig['nodes'])) {
            // 本地获取nodes参数
            foreach ($nodeConfig['nodes'] ?? [] as $item) {
                if (isset($item['host'], $item['port'])) {
                    if (!is_int($item['port'])) {
                        throw new InvalidArgumentException('Invalid node config, the port mast be integer.');
                    }
                    $nodes[] = new Node($item['host'], $item['port'], $item['weight']);
                }
            }
        } else {
            throw new InvalidArgumentException('Config or nodes missing.');
        }
        // 服务治理
        if (class_exists($nodeConfig['balancer'])) {
            $this->balancerMode = $nodeConfig['balancer'];
        } else {
            $this->balancerMode = RandomBalancer::class;
        }
        $balancer = $this->createBalancer($nodes, $refreshCallback);
        if (!$balancer->getNodes()) {
            throw new InvalidArgumentException('nodes missing.');
        }
        return $balancer->select();
    }

    /**
     * 获取当前服务consul健康节点
     * @param string $serviceName
     * @return Node[]
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
                $weight  = $service['Weights']['Passing'];
                $nodes[] = new Node($address, $port, $weight);
            }
        }
        return $nodes;
    }

    protected function createBalancer(array $nodes, callable $refresh = null): BalancerInterface
    {
        $loadBalancer = $this->balancerAdapter
            ->getInstance(self::$annotation->serviceName, $this->balancerMode)
            ->setNodes($nodes);
        $refresh && $loadBalancer->refresh($refresh);
        return $loadBalancer;
    }

    private function getNodeConfig(): array
    {
        $clients = config('topphpServer.clients');
        $config  = [];
        foreach ($clients as $client) {
            if (isset($client['name']) && $client['name'] === self::$annotation->serverName) {
                $config = $client;
                break;
            }
        }
        return $config;
    }
}
