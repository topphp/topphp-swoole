<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/13 15:02
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\balancer;

use InvalidArgumentException;
use Topphp\TopphpSwoole\contract\BalancerInterface;

class BalancerAdapter
{
    private $balancerClassList = [
        RandomBalancer::class,
        RandomWithWeightBalancer::class,
        RoundBalancer::class
    ];

    /**
     * @var BalancerInterface[]
     */
    private $instances = [];

    public function getInstance(string $serviceName, string $balancerClass): BalancerInterface
    {
        if (isset($this->instances[$serviceName])) {
            return $this->instances[$serviceName];
        }
        $class                         = $this->getClass($balancerClass);
        $instance                      = new $class;
        $this->instances[$serviceName] = $instance;
        return $instance;
    }

    public function getClass(string $balancerClass): string
    {
        if (!$this->hasClass($balancerClass)) {
            throw new InvalidArgumentException("the balancer#{$balancerClass} is not exists");
        }
        return $balancerClass;
    }

    public function hasClass(string $balancerClass): bool
    {
        return in_array($balancerClass, $this->balancerClassList);
    }
}
