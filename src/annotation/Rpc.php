<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/1 01:19
 * @author sleep <sleep@kaituocn.com>
 */

namespace Topphp\TopphpSwoole\annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Rpc
{
    /**
     * 服务名
     * @Required
     */
    public $name;

    /**
     * 和 config/topphpServer.php里的 servers.name 对应
     * @Required
     */
    public $server;
    /**
     * @Required
     * @Enum({"jsonrpc","http-jsonrpc"})
     */
    public $protocol;

    /**
     * @Enum({"consul"})
     */
    public $publish;
}
