<?php

use Topphp\TopphpSwoole\coroutine\Parallel;

/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/17 02:27
 * Author: sleep <sleep@kaituocn.com>
 */

/**
 * @param $callback
 * @param array $args
 * @return mixed|null
 * @author sleep
 */
function waitCallback($callback, array $args = [])
{
    $result = null;
    if ($callback instanceof \Closure) {
        $result = $callback(...$args);
    } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
        $result = $callback(...$args);
    } elseif (is_array($callback)) {
        [$object, $method] = $callback;
        $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
    } else {
        $result = call_user_func_array($callback, $args);
    }
    return $result;
}

/**
 * @param array $callables
 * @return array
 * @author sleep
 */
function parallels(array $callables)
{
    $parallel = new Parallel();
    foreach ($callables as $key => $callable) {
        $parallel->add($callable, $key);
    }
    return $parallel->wait();
}
