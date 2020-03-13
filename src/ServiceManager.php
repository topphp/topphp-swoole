<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/8 01:28
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole;

use Topphp\TopphpPool\rpc\Node;

class ServiceManager
{
    /**
     * @var Node[][]
     */
    private $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    /**
     * @return Node[][]
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
