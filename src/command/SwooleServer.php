<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:05
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\command;

use Swoole\Runtime;
use Swoole\Server;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use topphp\swoole\server\HttpServer;
use topphp\swoole\server\TcpServer;
use topphp\swoole\server\WebSocketServer;
use topphp\swoole\ServerConfig;
use topphp\swoole\SwooleApp;
use topphp\swoole\SwooleEvent;

class SwooleServer extends Command
{
    /** @var ServerConfig[] $config */
    private $config;

    protected function initialize(Input $input, Output $output)
    {
//        $server  = new WebSocketServer('127.0.0.1', 9501);
//        $server2 = new HttpServer('127.0.0.1', 9502);
//        $server->addlistener('127.0.0.1', 9502, SWOOLE_SOCK_TCP);
//        $server->on(SwooleEvent::ON_MESSAGE, function (Server $server, $fd, $reactor_id, $data) {
//            echo $data . PHP_EOL;
//            $server->send($fd, "Swoole: {$data}");
//        });
//        $server->on(SwooleEvent::ON_REQUEST, function (Server $server, $fd, $reactor_id, $data) {
//            echo $data . PHP_EOL;
//            $server->send($fd, "Swoole: {$data}");
//        });
//        $server->set(['open_http_protocol' => true]);
//        $server->start();

        $this->app->bind(SwooleApp::class, $this->initSwooleServer());
        $this->setSwooleServerListeners();
    }

    protected function configure()
    {
        $this->setName("server")->setDescription("开启swoole服务");
    }

    public function handle()
    {
        Runtime::enableCoroutine(true, defined('SWOOLE_HOOK_FLAGS') ? SWOOLE_HOOK_FLAGS : SWOOLE_HOOK_ALL);
        $this->output->writeln("swoole server is start");
    }

    private function initSwooleServer()
    {
        $servers     = $this->app->config->get('topphpServer.servers');
        $ServerArray = array_column($servers, 'type');
        $servers     = $this->sortServers($servers);
        $mode        = $this->app->config->get('topphpServer.mode', SWOOLE_PROCESS);
        $options     = $this->app->config->get('topphpServer.options');
        foreach ($servers as $server) {
            $serverClass = $server->getType();
            $serverTmp   = null;
//            switch ($serverClass) {
//                case TcpServer::class:
//                    /** @var Server $serverTmp */
//                    $serverTmp = $this->app->make((string)$serverClass, [
//                        $server->getHost(),
//                        $server->getPort(),
//                        $mode,
//                        $server->getSockType()
//                    ], true);
//                    break;
//                case HttpServer::class:
//                    if (!in_array(TcpServer::class, $ServerArray) && !in_array(WebSocketServer::class, $ServerArray)) {
//                        /** @var Server $serverTmp */
//                        $serverTmp = $this->app->make((string)$serverClass, [
//                            $server->getHost(),
//                            $server->getPort(),
//                            $mode,
//                            $server->getSockType()
//                        ], true);
//                    } else {
//                        var_dump($serverClass);
//                        $slaveServer = $serverTmp->addlistener($server->getHost(), $server->getPort(),
//                            $server->getSockType());
//                    }
//                    var_dump($serverTmp);
//                    break;
//                case WebSocketServer::class:
//                    var_dump($serverTmp);
//                    if (!in_array(TcpServer::class, $ServerArray)) {
//                        /** @var HttpServer $serverTmp */
//                        $serverTmp = $this->app->make((string)$serverClass, [
//                            $server->getHost(),
//                            $server->getPort(),
//                            $mode,
//                            $server->getSockType()
//                        ], true);
//                    } else {
//                        $slaveServer = $serverTmp->addlistener($server->getHost(), $server->getPort(),
//                            $server->getSockType());
//                    }
//                    break;
//                default:
//                    break;
//            }
//            if (!empty($server->getOptions())) {
//                // 如果配置文件中 某个服务内部options配置不为空,与外层options合并
//                $option = array_merge($server->getOptions(), $options);
//                $serverTmp->set($option);
//            } else {
//                // 否则直接使用外层options配置
//                $serverTmp->set($options);
//            }
//            $this->app->bind($server->getName(), $serverTmp);
            $this->config[$server->getName()] = $server;
        }
    }

    /**
     * 给服务排序,让tcp服务排第一个
     * @param ServerConfig[] $servers
     * @return ServerConfig[]
     * @author sleep
     */
    private function sortServers($servers)
    {
        $sortServer = [];
        foreach ($servers as $key => $server) {
            /** @var ServerConfig[] $cfg */
            $cfg[$key] = $this->app->make(ServerConfig::class, [$server], true);
            switch ($cfg[$key]->getType()) {
                case TcpServer::class:
                    array_unshift($sortServer, $cfg[$key]);
                    break;
                case HttpServer::class:
                case WebSocketServer::class:
                    array_push($sortServer, $cfg[$key]);
                    break;
                default:
                    array_push($sortServer, $cfg[$key]);
                    break;
            }
        }
        return $sortServer;
    }

    /**
     * 遍历服务和事件获取监听
     * @author sleep
     */
    private function setSwooleServerListeners()
    {
        //遍历服务和事件获取监听
        foreach ($this->config as $config) {
            $serverClass = $config->getType();
            $ref         = new \ReflectionClass($serverClass);
            dump($ref->getProperty('events'));
            $event    = '';
            $callback = '';

//            $this->getServer()->on($event, $callback);
        }
//        foreach ($this->events as $event) {
//            $listener = Str::camel("on_$event");
//            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
//                $this->triggerEvent($event, func_get_args());
//            };
//
//            $this->getServer()->on($event, $callback);
//        }
    }

    /**
     * @param string $name
     * @return Server
     */
    private function getServer(string $name): Server
    {
        /**@var Server $server */
        $server = $this->app->get($name);
        return $server;
    }

}
