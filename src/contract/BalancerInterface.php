<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/13 13:51
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\contract;

use Topphp\TopphpPool\rpc\Node;

interface BalancerInterface
{
    public function select(array ...$arguments): Node;

    /**
     * @param Node[] $nodes
     * @return $this
     */
    public function setNodes(array $nodes);

    /**
     * @return Node[] $nodes
     */
    public function getNodes(): array;

    /**
     * Remove a node from the node list.
     * @param Node $node
     * @return bool
     */
    public function removeNode(Node $node): bool;

    public function refresh(callable $callback, int $tickMs = 5000);
}
