<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/10 14:58
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\pool;

use Topphp\TopphpPool\BasePool;
use Topphp\TopphpPool\contract\ConnectionInterface;
use Topphp\TopphpPool\exception\ConnectionException;

class RpcPool extends BasePool
{
    protected $name;

    public function __construct(string $name, array $config = [])
    {
        $this->name   = $name;
        $this->config = array_merge($this->config, $config);
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ConnectionInterface
     * @throws ConnectionException
     * @author sleep
     */
    protected function createConnection(): ConnectionInterface
    {
        return new RpcConnection($this, $this->config);
    }
}
