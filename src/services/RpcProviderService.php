<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/3 21:18
 * @author sleep <sleep@kaituocn.com>
 */

declare(strict_types=1);

namespace Topphp\TopphpSwoole\services;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use think\Service;
use Topphp\TopphpSwoole\server\jsonrpc\Evaluator;
use Topphp\TopphpSwoole\server\jsonrpc\exceptions\MethodException;

/**
 * 用户服务继承该类
 * Class RpcProviderService
 * @package Topphp\TopphpSwoole\services
 */
class RpcProviderService extends Service implements Evaluator
{
    /**
     * @inheritDoc 通过反射仅获取public方法作为rpc调用
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public function evaluate($method, $arguments)
    {
        try {
            $ref               = new ReflectionClass(static::class);
            $reflectionMethods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
            $methods           = [];
            foreach ($reflectionMethods as $reflectionMethod) {
                if ($reflectionMethod->class === static::class) {
                    $methods[] = $reflectionMethod->getName();
                }
            }
            if (in_array($method, $methods)) {
                return $this->$method(...$arguments);
            }
            throw new MethodException();
        } catch (ReflectionException $e) {
            throw new ReflectionException($e->getMessage());
        }
    }
}
