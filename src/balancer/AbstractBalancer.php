<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/13 13:53
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\balancer;

use Swoole\Timer;
use Topphp\TopphpPool\rpc\Node;
use Topphp\TopphpSwoole\contract\BalancerInterface;

abstract class AbstractBalancer implements BalancerInterface
{
    /**
     * @var Node[]
     */
    protected $nodes = [];

    public function __construct(array $nodes = [])
    {
        $this->setNodes($nodes);
    }

    /**
     * @inheritDoc
     */
    public function setNodes(array $nodes = [])
    {
        $this->nodes = $nodes;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @inheritDoc
     */
    public function removeNode(Node $node): bool
    {
        foreach ($this->nodes as $key => $activeNode) {
            if ($activeNode === $node) {
                unset($this->nodes[$key]);
                return true;
            }
        }
        return false;
    }

    public function refresh(callable $callback, int $tickMs = 5000)
    {
        Timer::tick($tickMs, function () use ($callback) {
            $nodes = waitCallback($callback);
            is_array($nodes) && $this->setNodes($nodes);
        });
    }
}
