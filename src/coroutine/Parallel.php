<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/16 23:40
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\coroutine;

use Swoole\Coroutine;
use Throwable;
use Swoole\Coroutine\Channel;
use Topphp\TopphpSwoole\exception\ParallelExecutionException;

class Parallel
{
    /**
     * @var callable[]
     */
    private $callbacks = [];

    /**
     * @var null|Channel
     */
    private $concurrentChannel;

    /**
     * @param int $concurrent if $concurrent is equal to 0, that means unlimit
     */
    public function __construct(int $concurrent = 0)
    {
        if ($concurrent > 0) {
            $this->concurrentChannel = new Channel($concurrent);
        }
    }

    public function add(callable $callable, $key = null)
    {
        if (is_string($key)) {
            $this->callbacks[$key] = $callable;
        } else {
            $this->callbacks[] = $callable;
        }
    }

    public function wait(bool $throw = true): array
    {
        $result = $throwables = [];
        $wg     = new WaitGroup();
        $wg->add(count($this->callbacks));
        foreach ($this->callbacks as $key => $callback) {
            $this->concurrentChannel && $this->concurrentChannel->push(true);
            Coroutine::create(function () use ($callback, $key, $wg, &$result, &$throwables) {
                try {
                    $result[$key] = waitCallback($callback);
                } catch (Throwable $throwable) {
                    $throwables[$key] = $throwable;
                } finally {
                    $this->concurrentChannel && $this->concurrentChannel->pop();
                    $wg->done();
                }
            });
        }
        $wg->wait();
        if ($throw && ($throwableCount = count($throwables)) > 0) {
            $message = 'Detecting ' .
                $throwableCount .
                ' throwable occurred during parallel execution:' .
                PHP_EOL .
                $this->formatThrowables($throwables);

            $executionException = new ParallelExecutionException($message);
            $executionException->setResults($result);
            $executionException->setThrowables($throwables);
            throw $executionException;
        }
        return $result;
    }

    public function clear(): void
    {
        $this->callbacks = [];
    }

    /**
     * Format throwables into a nice list.
     *
     * @param Throwable[] $throwables
     * @return string
     */
    private function formatThrowables(array $throwables): string
    {
        $output = '';
        foreach ($throwables as $key => $value) {
            $output .= sprintf(
                '(%s) %s: %s' . PHP_EOL . '%s' .
                PHP_EOL,
                $key,
                get_class($value),
                $value->getMessage(),
                $value->getTraceAsString()
            );
        }
        return $output;
    }
}
