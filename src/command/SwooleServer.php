<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:05
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\command;

use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Runtime;
use Swoole\Server;
use think\console\Command;
use think\console\input\Argument;
use think\helper\Str;
use Topphp\TopphpSwoole\FileWatcher;
use Topphp\TopphpSwoole\server\BaseServer;
use Topphp\TopphpSwoole\server\HttpServer;
use Topphp\TopphpSwoole\server\TcpServer;
use Topphp\TopphpSwoole\server\WebSocketServer;
use Topphp\TopphpSwoole\ServerConfig;
use Topphp\TopphpSwoole\SwooleApp;

class SwooleServer extends Command
{
    /** @var ServerConfig[] $config */
    private $config;

    /** @var HttpServer|WebSocketServer|Server $server */
    private $server;

    protected function configure()
    {
        $this->setName("server")
            ->addArgument('action', Argument::OPTIONAL, 'start', 'start')
            ->setDescription("开启swoole服务");
    }

    public function handle()
    {
        $action = $this->input->getArgument('action');
        switch ($action) {
            case 'start':
                Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);//v4.4+版本使用此方法。
                $this->app->bind(SwooleApp::class, $this->initSwooleServer());
                break;
            default:
                if (in_array($action, [])) {
                    Coroutine::create(function () use ($action) {
                        $this->app->invokeMethod([$this, $action], [], true);
                    });
                }
                break;
        }
    }

    private function initSwooleServer()
    {
        $servers     = $this->app->config->get('topphpServer.servers');
        $servers     = $this->sortServers($servers);
        $mode        = $this->app->config->get('topphpServer.mode', SWOOLE_PROCESS);
        $options     = $this->app->config->get('topphpServer.options');
        $bindServers = [];
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
//            if (count($servers) === 1) {
//                $server->setOptions([
//                    'open_websocket_protocol' => false,
//                ]);
//            }
            $option = array_replace($server->getOptions(), $options);
            $slaveServer->set($option);
            // 添加监听事件
            $this->setSwooleServerListeners($slaveServer, $server->getType());
            // 把同名服务组成数组,方便客户端随机调用
            $bindServers[$server->getName()][] = $slaveServer;
        }
        $this->app->session->set('bindServers', $bindServers);

        // 添加基础监听
        $this->setDefaultSwooleServerListeners($this->server);
        if (env('APP_DEBUG')) {
            $this->hotUpdate();
        }
        $this->startServer();
    }

    /**
     * @param Server $server
     * @author sleep
     */
    private function setDefaultSwooleServerListeners($server)
    {
        $baseEvents = BaseServer::getEvents();
        foreach ($baseEvents as $baseEvent) {
            $listener = Str::camel("on_$baseEvent");
            $callback = [BaseServer::class, $listener];
            $server->on($baseEvent, $callback);
        }
    }

    /**
     * 遍历服务和事件获取监听
     * @param Server $server
     * @param Server|HttpServer|WebSocketServer|TcpServer $class
     * @author sleep
     */
    private function setSwooleServerListeners($server, $class)
    {
        $events = $class::getEvents();
        foreach ($events as $event) {
            $listener = Str::camel("on_$event");
            $callback = [$class, $listener];
            $server->on($event, $callback);
        }
    }

    /**
     * 热更新
     * @author sleep
     */
    private function hotUpdate()
    {
        $process = new Process(function () {
            $watcher = new FileWatcher([app_path()], [], ["*.php"]);
            $watcher->watch(function () {
                $date = date('Y-m-d H:i:s');
                echo "[{$date}] server is reload" . PHP_EOL;
                $this->server->reload();
            });
        }, false, 0);
        $this->server->addProcess($process);
    }

    private function startServer()
    {
//        Runtime::enableCoroutine(true, defined('SWOOLE_HOOK_FLAGS') ? SWOOLE_HOOK_FLAGS : SWOOLE_HOOK_ALL);
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
        $issetWebSocketServer = false;
        foreach ($servers as $key => $server) {
            /** @var ServerConfig[] $cfg */
            $cfg[$key] = $this->app->make(ServerConfig::class, [$server], true);
            switch ($cfg[$key]->getType()) {
                case HttpServer::class:
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
