<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\services;

use Topphp\TopphpSwoole\command\SwooleServer;

class Service extends \think\Service
{
    public function boot()
    {
        $this->commands([SwooleServer::class]);
    }
}
