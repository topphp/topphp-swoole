<?php

declare(strict_types=1);

namespace topphp\Test;

use PHPUnit\Framework\TestCase;
use topphp\swoole\SwooleApp;

class ExampleTest extends TestCase
{
    /**
     * Test that true does in fact equal true
     */
    public function testTrueIsTrue()
    {
        $s = new SwooleApp();
        var_dump($s->echoPhrase("hello"));
        $this->assertTrue(true);
    }
}
