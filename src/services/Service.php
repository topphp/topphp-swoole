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
use think\facade\App;
use Topphp\TopphpConsul\consul\Agent;
use Topphp\TopphpLog\Log;
use Topphp\TopphpSwoole\annotation\Rpc;
use Topphp\TopphpSwoole\command\SwooleServer;
use Topphp\TopphpSwoole\server\TopServerEvent;
use Topphp\TopphpSwoole\ServiceManager;

class Service extends \think\Service
{
    /** @var Agent */
    private $agent;
    /**
     * @var array
     */
    private $registeredService;

    public function boot()
    {
        $this->commands([SwooleServer::class]);
    }

    public function register()
    {
        $this->app->event->listen(TopServerEvent::MAIN_WORKER_START, function ($event) {
            AnnotationReader::addGlobalIgnoredName('mixin');
            AnnotationRegistry::registerLoader('class_exists');
            // 遍历 app 目录,扫描注解
            /** @var Finder $finder */
            $finder = $this->app->make(Finder::class);
            $finder->files()->in(app_path())->name(['*.php']);
            if (!$finder->hasResults()) {
                return;
            }
            foreach ($finder as $file) {
                if (!$file->getRelativePath()) {
                    continue;
                }
                $class = '/app/' . $file->getRelativePath() . '/' . $file->getFilenameWithoutExtension();
                $class = str_replace('/', '\\', $class);
                try {
                    $reflectionClass = new ReflectionClass($class);
                } catch (\ReflectionException $e) {
                    continue;
                }
                // 整理 rpc-server 到数组中
                /** @var AnnotationReader $reader */
                $reader = $this->app->make(AnnotationReader::class);
                /** @var Rpc $rpcAnnotation */
                $rpcAnnotation = $reader->getClassAnnotation($reflectionClass, Rpc::class);
                if (!$rpcAnnotation) {
                    continue;
                }
                $this->app->bind(
                    $rpcAnnotation->serverName . '::' . $rpcAnnotation->serviceName,
                    $reflectionClass->getName()
                );

                //  consul服务注册
                if ($rpcAnnotation->publish !== 'consul') {
                    continue;
                }
                $this->agent = App::make(Agent::class);
                /** @var ServiceManager $serviceList */
                $serviceList = $this->app->get(ServiceManager::class);
                foreach ($serviceList->getServices() as $name => $services) {
                    foreach ($services as $service) {
                        // 判断当前是否 $rpcAnnotation->name 在配置文件中
                        if ($rpcAnnotation->serverName === $name) {
                            if (in_array($service->getHost(), ['0.0.0.0', 'localhost'])) {
                                $service->setHost($this->getInternalIp());
                            }
                            $this->publishToConsul(
                                $rpcAnnotation->serviceName,
                                $service->getHost(),
                                $service->getPort(),
                                $rpcAnnotation->protocol
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * 服务注册
     * @param $serviceName
     * @param $address
     * @param $port
     * @param $protocol
     * @author sleep
     */
    private function publishToConsul($serviceName, $address, $port, $protocol)
    {
        if ($this->serviceIsRegistered($serviceName, $address, $port, $protocol)) {
            Log::debug("{$serviceName} {$address}:{$port} has been already registered to the consul.");
            return;
        }
        $lastId      = $this->getLastServiceId($serviceName);
        $id          = $this->generateId($lastId);
        $requestBody = [
            'Name'    => $serviceName,
            'ID'      => $id,
            'Address' => $address,
            'Port'    => $port,
            'Meta'    => [
                'Protocol' => $protocol,
            ],
        ];
        if ($protocol === 'jsonrpc') {
            /**
             * todo  做成可配参数 DeregisterCriticalServiceAfter,Interval
             * DeregisterCriticalServiceAfter 异常服务自动取消注册时间
             * Interval 健康检查时间间隔
             */
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => '10m',
                'TCP'                            => "{$address}:{$port}",
                'Interval'                       => '5s',
            ];
        }
        if ($protocol === 'http-jsonrpc') {
            // todo http-jsonrpc的健康检查
        }
        $response = $this->agent->registerService($requestBody);
        if ($response->getStatusCode() === 200) {
            $this->registeredService[$serviceName][$protocol][$address][$port] = true;
            Log::debug("{$id} {$address}:{$port} register to the consul successfully.");
        } else {
            throw new \RuntimeException($response->getBody());
        }
    }

    private function generateId(string $name)
    {
        $exploded = explode('-', $name);
        $length   = count($exploded);
        $end      = -1;
        if ($length > 1 && is_numeric($exploded[$length - 1])) {
            $end = $exploded[$length - 1];
            unset($exploded[$length - 1]);
        }
        $end = intval($end);
        ++$end;
        $exploded[] = $end;
        return implode('-', $exploded);
    }

    private function getLastServiceId(string $name)
    {
        $maxId       = -1;
        $lastService = $name;
        $services    = $this->agent->services()->json();
        foreach ($services ?? [] as $id => $service) {
            if (isset($service['Service']) && $service['Service'] === $name) {
                $exploded = explode('-', (string)$id);
                $length   = count($exploded);
                if ($length > 1 && is_numeric($exploded[$length - 1]) && $maxId < $exploded[$length - 1]) {
                    $maxId       = $exploded[$length - 1];
                    $lastService = $service;
                }
            }
        }
        return $lastService['ID'] ?? $name;
    }

    /**
     * 获取内网ip
     * @return string
     * @author sleep
     */
    private function getInternalIp(): string
    {
        $ips = swoole_get_local_ip();
        if (is_array($ips)) {
            return current($ips);
        }
        $ip = gethostbyname(gethostname());
        if (is_string($ip)) {
            return $ip;
        }
        throw new \RuntimeException('Can not get the internal IP.');
    }

    private function serviceIsRegistered($serviceName, $address, $port, $protocol)
    {
        if (isset($this->registeredService[$serviceName][$protocol][$address][$port])) {
            return true;
        }
        $response = $this->agent->services();
        if ($response->getStatusCode() !== 200) {
            Log::debug("{$serviceName}#{$address}:{$port}register to the consul failed.");
            return false;
        }
        $tag      = implode(',', [$serviceName, $address, $port, $protocol]);
        $services = $response->json();
        foreach ($services as $serviceId => $service) {
            if (!isset($service['Service'], $service['Address'], $service['Port'], $service['Meta']['Protocol'])) {
                continue;
            }
            $currentTag = implode(',', [
                $service['Service'],
                $service['Address'],
                $service['Port'],
                $service['Meta']['Protocol'],
            ]);

            if ($currentTag === $tag) {
                $this->registeredService[$serviceName][$protocol][$address][$port] = true;
                return true;
            }
        }
        return false;
    }
}
