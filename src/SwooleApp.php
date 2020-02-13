<?php

declare(strict_types=1);

namespace Topphp\TopphpSwoole;

use think\facade\App;
use Datto\JsonRpc\Evaluator;
use Datto\JsonRpc\Exceptions\MethodException;

class SwooleApp extends App implements Evaluator
{
    /**
     * Create a new Skeleton Instance
     */
    public function __construct()
    {
        // constructor body
    }

    /**
     * Friendly welcome
     *
     * @param string $phrase Phrase to return
     *
     * @return string Returns the phrase passed in
     */
    public function echoPhrase(string $phrase): string
    {
        return $phrase;
    }

    /**
     * todo rpc-server
     * @inheritDoc
     * @throws MethodException
     */
    public function evaluate($method, $arguments)
    {
        $methods = get_class_methods(SwooleApp::class);
        var_dump($methods);
        if (in_array($method, $methods)) {
            return $this->$method(...$arguments);
        }
        throw new MethodException();
    }
}
