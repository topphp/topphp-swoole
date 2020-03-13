<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/13 14:12
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\balancer;

use Topphp\TopphpPool\rpc\Node;

class RandomWithWeightBalancer extends AbstractBalancer
{
    public function select(array ...$arguments): Node
    {
        $totalWeight  = 0;
        $isSameWeight = true;
        $lastWeight   = null;
        $nodes        = $this->nodes ?? [];
        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }
            $weight      = $node->getWeight();
            $totalWeight += $weight;
            if ($lastWeight !== null && $isSameWeight && $weight !== $lastWeight) {
                $isSameWeight = false;
            }
            $lastWeight = $weight;
        }
        if ($totalWeight > 0 && !$isSameWeight) {
            $offset = mt_rand(0, $totalWeight - 1);
            foreach ($nodes as $node) {
                $offset -= $node->getWeight();
                if ($offset < 0) {
                    return $node;
                }
            }
        }
        return $nodes[array_rand($nodes)];
    }
}
