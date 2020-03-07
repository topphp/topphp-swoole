<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/8 01:28
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole;

class ServiceManager
{
    private $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

}
