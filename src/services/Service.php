<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Finder\Finder;
use Topphp\TopphpSwoole\annotation\Rpc;
use Topphp\TopphpSwoole\command\SwooleServer;
use Topphp\TopphpSwoole\server\jsonrpc\exceptions\MethodException;

class Service extends \think\Service
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @author sleep
     */
    public function register()
    {
        AnnotationReader::addGlobalIgnoredName('mixin');
        AnnotationRegistry::registerLoader('class_exists');

        // 遍历 app 目录,扫描注解
        /** @var Finder $finder */
        $finder = $this->app->make(Finder::class);
        $finder->files()->in(app_path());
        $rpcService = [];
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                if ($file->getRelativePath()) {
                    $class = '/app/' . $file->getRelativePath() . '/' . $file->getFilenameWithoutExtension();
                    $class = str_replace('/', '\\', $class);
                    $ref   = new \ReflectionClass($class);

                    // 整理 rpc-server 到数组中
                    /** @var AnnotationReader $reader */
                    $reader = $this->app->make(AnnotationReader::class);
                    /** @var Rpc $annotation */
                    $annotation = $reader->getClassAnnotation($ref, Rpc::class);
                    if ($annotation) {
                        $this->app->bind($annotation->server, $ref->getName());
                        $rpcService[$annotation->server] = $ref->getName();
                    }
                }
            }
        }
        // 绑定注解服务到容器中
        $this->app->bind(Rpc::class, function () use ($rpcService) {
            return $rpcService;
        });
    }

    public function boot()
    {
        $this->commands([SwooleServer::class]);
    }
}
