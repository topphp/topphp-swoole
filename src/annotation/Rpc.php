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
     * 全局唯一标识,服务注册时用到
     * @Required
     */
    public $serviceName;

    /**
     * 服务名
     * 与config/topphpServer.php里的 servers.name 对应
     * @Required
     */
    public $serverName;

    /**
     * 服务协议,目前仅支持 jsonrpc
     * @Required
     * @Enum({"jsonrpc","http-jsonrpc"})
     */
    public $protocol;

    /**
     * @Enum({"consul"})
     */
    public $publish;
}
