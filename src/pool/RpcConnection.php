<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/10 15:04
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\pool;

use RuntimeException;
use Swoole\Coroutine\Client as SwooleClient;
use Topphp\TopphpPool\BaseConnection;
use Topphp\TopphpPool\BasePool;
use Topphp\TopphpPool\exception\ConnectionException;

/**
 * @method bool|int send($data)
 * @method bool|string recv(float $timeout)
 * @property int $errCode
 */
class RpcConnection extends BaseConnection
{

    /**
     * @var SwooleClient
     */
    protected $connection;
    /**
     * @var array
     */
    protected $config = [
        'node'            => [],
        'connect_timeout' => 5.0,
        'options'         => [],
    ];

    /**
     * RpcConnection constructor.
     * @param BasePool $pool
     * @param array $config
     * @throws ConnectionException
     */
    public function __construct(BasePool $pool, array $config)
    {
        parent::__construct($pool);
        $this->config = array_replace($this->config, $config);
        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        $this->connection->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->connection->{$name};
    }

    /**
     * @return RpcConnection
     * @throws ConnectionException
     * @author sleep
     */
    protected function getActiveConnection()
    {
        if ($this->check()) {
            return $this;
        }
        if (!$this->reconnect()) {
            throw new ConnectionException("无法获取连接信息");
        }
        return $this;
    }

    /**
     * @return bool
     * @throws ConnectionException
     * @author sleep
     */
    public function reconnect(): bool
    {
        if (!$this->config['node'] || empty($this->config['node'])) {
            throw new ConnectionException('连接配置信息不存在');
        }
        $client = new SwooleClient(SWOOLE_SOCK_TCP);
        $client->set($this->config['options']);
        $result = $client->connect(
            $this->config['node']['host'],
            $this->config['node']['port'],
            $this->config['connect_timeout']
        );
        if ($result === false && ($client->errCode === 114 || $client->errCode === 115)) {
            $client->close();
            throw new RuntimeException('Connect to server failed.');
        }
        $this->connection  = $client;
        $this->lastUseTime = microtime(true);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        $this->lastUseTime = 0;
        $this->connection->close();
        return true;
    }
}
