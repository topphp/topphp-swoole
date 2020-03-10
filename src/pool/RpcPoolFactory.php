<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/10 23:57
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\pool;

use think\facade\App;

class RpcPoolFactory
{
    /**
     * @var RpcPool[]
     */
    protected $pools = [];

    public function getPool(string $name, array $config)
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }
        $pool = App::make(RpcPool::class, [
            'name'   => $name,
            'config' => $config
        ]);
        return $this->pools[$name] = $pool;
    }
}
