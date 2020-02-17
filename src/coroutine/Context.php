<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/17 02:42
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\coroutine;

use Closure;
use Swoole\Coroutine;

/**
 * 自己实现的协程上下文管理器
 * Class Context
 * @package Topphp\TopphpSwoole\coroutine
 */
class Context
{
    protected static $pool = [];

    public static function cid(): int
    {
        return Coroutine::getuid();
    }

    public static function get($key, int $cid = null, $default = null)
    {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            return Coroutine::getContext($cid)[$key] ?? $default;
        }
        return self::$pool[$key] ?? $default;
    }

    public static function set($key, $item, int $cid = null)
    {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            Coroutine::getContext($cid)[$key] = $item;
        } else {
            self::$pool[$key] = $item;
        }
        return $item;
    }

    public static function has($key, int $cid = null): bool
    {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            return isset(Coroutine::getContext($cid)[$key]);
        }
        return isset(self::$pool[$key]);
    }

    public static function delete($key, int $cid = null)
    {
        $cid = $cid ?? Coroutine::getuid();
        if ($cid > 0) {
            unset(Coroutine::getContext($cid)[$key]);
        } else {
            unset(self::$pool[$key]);
        }
    }

    public static function override($key, Closure $closure, int $cid = null)
    {
        $value = null;
        if (self::has($key, $cid)) {
            $value = self::get($key, $cid);
        }
        $value = $closure($value);
        self::set($key, $value, $cid);
        return $value;
    }
}
