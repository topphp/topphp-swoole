<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/9 16:12
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\breaker;

class State
{
    const CLOSE = 0;

    const HALF_OPEN = 1;

    const OPEN = 2;

    protected $state;

    public function __construct()
    {
        $this->state = self::CLOSE;
    }

    public function open()
    {
        $this->state = self::OPEN;
    }

    public function close()
    {
        $this->state = self::CLOSE;
    }

    public function halfOpen()
    {
        $this->state = self::HALF_OPEN;
    }

    public function isOpen(): bool
    {
        return $this->state === self::OPEN;
    }

    public function isClose(): bool
    {
        return $this->state === self::CLOSE;
    }

    public function isHalfOpen(): bool
    {
        return $this->state === self::HALF_OPEN;
    }
}
