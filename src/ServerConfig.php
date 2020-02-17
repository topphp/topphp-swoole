<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 21:35
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole;

use Swoole\Server;
use Topphp\TopphpSwoole\server\HttpServer;

class ServerConfig
{
    /**
     * @var Server
     */
    private $type = HttpServer::class;

    /**
     * @var string
     */
    private $name = 'top-server';

    /**
     * @var string
     */
    private $host = '0.0.0.0';

    /**
     * @var int
     */
    private $port = 9898;

    /**
     * @var int
     */
    private $sockType = SWOOLE_SOCK_TCP;

    /**
     * @var array
     */
    private $options = [];

    public function __construct(array $server)
    {
        !isset($server['type']) ?: $this->setType($server['type']);
        !isset($server['name']) ?: $this->setName($server['name']);
        !isset($server['host']) ?: $this->setHost($server['host']);
        !isset($server['port']) ?: $this->setPort($server['port']);
        !isset($server['sock_type']) ?: $this->setSockType($server['sock_type']);
        !isset($server['options']) ?: $this->setOptions($server['options']);
    }

    /**
     * @return Server
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Server $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getSockType(): int
    {
        return $this->sockType;
    }

    /**
     * @param int $sockType
     */
    public function setSockType(int $sockType): void
    {
        $this->sockType = $sockType;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
