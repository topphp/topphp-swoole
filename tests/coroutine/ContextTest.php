<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/17 02:43
 * Author: sleep <sleep@kaituocn.com>
 */

namespace Topphp\Test\coroutine;

use Swoole\Coroutine;
use Topphp\TopphpSwoole\coroutine\Context;
use Topphp\TopphpTesting\TestCase;

class ContextTest extends TestCase
{
    public function testContext()
    {
        Coroutine::create(function () {
            Context::set('info', [1, 2, 3], Coroutine::getuid());
            $info = Context::get('info', Coroutine::getuid());  // get context of this coroutine
            var_dump($info);
            $this->assertEquals($info, [1, 2, 3], '1 succss');
            Coroutine::defer(function () {
                Context::delete('info', Coroutine::getuid());       // delete
                // get context of this coroutine
                $info = Context::get(
                    'info',
                    Coroutine::getuid()
                );
                var_dump($info);
                $this->assertNull($info, '2 success');
            });
        });
    }
}
