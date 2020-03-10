<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @package topphp-swoole
 * @date 2020/3/9 14:47
 * @author sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 *
 * Class Breaker
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class Breaker
{
    /**
     * @var int 超过该时间后失败计数 failCount+1
     */
    public $timeout = 10;

    /**
     * @var int 失败多少次后进行降级
     */
    public $failCount = 10;
    /**
     * @var int 成功多少次后进行放行
     */
    public $successCount = 10;

    /**
     * @var int 熔断器中断请求多少秒后会进入半打开状态,放部分流量过去重试
     */
    public $sleep;
}
