<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/13 14:04
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\balancer;

use RuntimeException;
use Topphp\TopphpPool\rpc\Node;

class RandomBalancer extends AbstractBalancer
{
    public function select(array ...$arguments): Node
    {
        if (empty($this->nodes)) {
            throw new RuntimeException('没有节点可供选择');
        }
        $key = array_rand($this->nodes);
        return $this->nodes[$key];
    }
}
