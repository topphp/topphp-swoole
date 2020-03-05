<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\services;

use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Topphp\TopphpSwoole\annotation\Rpc;
use Topphp\TopphpSwoole\command\SwooleServer;

class Service extends \think\Service
{
    /**
     * @author sleep
     */
    public function register()
    {
        AnnotationReader::addGlobalIgnoredName('mixin');
        AnnotationRegistry::registerLoader('class_exists');
        // 遍历 app 目录,扫描注解
        /** @var Finder $finder */
        $finder = $this->app->make(Finder::class);
        $finder->files()->in(app_path())->name(['*Service.php', '*.php']);
        $rpcService = [];
        if (!$finder->hasResults()) {
            return;
        }
        foreach ($finder as $file) {
            if (!$file->getRelativePath()) {
                continue;
            }
            $class = '/app/' . $file->getRelativePath() . '/' . $file->getFilenameWithoutExtension();
            $class = str_replace('/', '\\', $class);

            /** @var ReflectionClass $ref */
            $ref = $this->app->make(ReflectionClass::class, [$class]);
            // 整理 rpc-server 到数组中
            /** @var AnnotationReader $reader */
            $reader = $this->app->make(AnnotationReader::class);
            /** @var Rpc $rpcAnnotation */
            $rpcAnnotation = $reader->getClassAnnotation($ref, Rpc::class);
            if ($rpcAnnotation) {
                //todo 判断当前是否 $rpcAnnotation->name 不在配置文件中

                $this->app->bind($rpcAnnotation->id, $ref->getName());
//                $rpcService[$rpcAnnotation->name] = $ref->getName();
            }
        }
    }

    public function boot()
    {
        $this->commands([SwooleServer::class]);
    }
}
