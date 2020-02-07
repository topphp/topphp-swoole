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
use think\helper\Str;
use topphp\swoole\server\HttpServer;
use topphp\swoole\server\TcpServer;
use topphp\swoole\server\WebSocketServer;
use topphp\swoole\ServerConfig;
use topphp\swoole\SwooleApp;

class SwooleServer extends Command
{
    /** @var ServerConfig[] $config */
    private $config;

    /** @var HttpServer|WebSocketServer|Server $server */
    private $server;

    protected function configure()
    {
        $this->setName("server")->setDescription("开启swoole服务");
    }

    public function handle()
    {
        $this->app->bind(SwooleApp::class, $this->initSwooleServer());
    }

    private function initSwooleServer()
    {
//        Runtime::enableCoroutine(true, defined('SWOOLE_HOOK_FLAGS') ? SWOOLE_HOOK_FLAGS : SWOOLE_HOOK_ALL);
        $servers = $this->app->config->get('topphpServer.servers');
        $servers = $this->sortServers($servers);
        $mode    = $this->app->config->get('topphpServer.mode', SWOOLE_PROCESS);
        $options = $this->app->config->get('topphpServer.options');
        foreach ($servers as $server) {
            if (!$this->server instanceof Server) {
                $serverClass = $server->getType();
                $slaveServer = $this->server = $this->app->make((string)$serverClass, [
                    $server->getHost(),
                    $server->getPort(),
                    $mode,
                    $server->getSockType()
                ], true);
            } else {
                $slaveServer = $this->server->addlistener(
                    $server->getHost(),
                    $server->getPort(),
                    $server->getSockType()
                );
                if (!$slaveServer) {
                    throw new \RuntimeException("Failed to listen server
                    port [{$server->getHost()}:{$server->getPort()}]");
                }
            }
            if (!empty($server->getOptions())) {
                // 如果配置文件中 某个服务内部options配置不为空,与外层options合并
                $option = array_replace($server->getOptions(), $options);
                $slaveServer->set($option);
            } else {
                // 否则直接使用外层options配置
                $slaveServer->set($options);
            }
            // 添加监听事件
            $this->setSwooleServerListeners($slaveServer, $server->getType());
            $this->app->bind($server->getName(), $slaveServer);
        }
        $this->startServer();
    }

    /**
     * 遍历服务和事件获取监听
     * @param Server $server
     * @param string $class
     * @author sleep
     */
    private function setSwooleServerListeners($server, $class)
    {
        /**@var HttpServer|WebSocketServer|TcpServer $class */
        $events = $class::getEvents();
        foreach ($events as $event) {
            $listener = Str::camel("on_$event");
            $callback = [$class, $listener];
            $server->on($event, $callback);
        }
    }

    private function startServer()
    {
        $this->server->start();
    }

    /**
     * 给服务排序,让websocket排第一个
     * @param ServerConfig[] $servers
     * @return ServerConfig[]
     * @author sleep
     */
    private function sortServers($servers)
    {
        $sortServer           = [];
        $issetHttpServer      = false;
        $issetWebSocketServer = false;
        foreach ($servers as $key => $server) {
            /** @var ServerConfig[] $cfg */
            $cfg[$key] = $this->app->make(ServerConfig::class, [$server], true);
            switch ($cfg[$key]->getType()) {
                case HttpServer::class:
                    $issetHttpServer = true;
                    if ($issetWebSocketServer) {
                        $sortServer[] = $cfg[$key];
                    } else {
                        array_unshift($sortServer, $cfg[$key]);
                    }
                    break;
                case WebSocketServer::class:
                    $issetWebSocketServer = true;
                    array_unshift($sortServer, $cfg[$key]);
                    break;
                default:
                    $sortServer[] = $cfg[$key];
                    break;
            }
        }
        return $sortServer;
    }
}
