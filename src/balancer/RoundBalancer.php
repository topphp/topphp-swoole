<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/13 14:08
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\balancer;

use RuntimeException;
use Topphp\TopphpPool\rpc\Node;

class RoundBalancer extends AbstractBalancer
{
    private $currentIndex = 0;

    public function select(array ...$arguments): Node
    {
        $count = count($this->nodes);
        if ($count <= 0) {
            throw new RuntimeException('Nodes missing.');
        }
        $item = $this->nodes[$this->currentIndex % $count];
        ++$this->currentIndex;
        return $item;
    }
}
