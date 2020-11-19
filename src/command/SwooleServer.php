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
use Swoole\Server;
use think\console\Command;
use think\console\input\Argument;
use think\helper\Str;
use Topphp\TopphpPool\rpc\Node;
use Topphp\TopphpSwoole\FileWatcher;
use Topphp\TopphpSwoole\server\BaseServer;
use Topphp\TopphpSwoole\server\HttpServer;
use Topphp\TopphpSwoole\server\RpcServer;
use Topphp\TopphpSwoole\server\TcpServer;
use Topphp\TopphpSwoole\server\WebSocketServer;
use Topphp\TopphpSwoole\ServerConfig;
use Topphp\TopphpSwoole\ServiceManager;
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
                $this->initSwooleServer();
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
        /** @var Node[] $services */
        $services = [];
        $servers  = $this->app->config->get('topphpServer.servers');
        $servers  = $this->sortServers($servers);
        $mode     = $this->app->config->get('topphpServer.mode', SWOOLE_PROCESS);
        $options  = $this->app->config->get('topphpServer.options');
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
            $option = array_replace($server->getOptions(), $options);
            $slaveServer->set($option);
            // 添加监听事件
            $this->setSwooleServerListeners($slaveServer, $server->getType());
            if ($server->getType() === RpcServer::class) {
                $services[$server->getName()][] = new Node($server->getHost(), $server->getPort(), 0);
            }
        }
        // 把启动的服务加入到容器中,注册服务和消费服务时调用
        $this->app->make(ServiceManager::class, [$services]);
        // 添加基础监听
        $this->setBaseServerListeners($this->server);
        if (env('APP_DEBUG')) {
            $this->hotUpdate();
        } else {
            $date = new \DateTime();
            echo "[{$date->format('Y-m-d H:i:s.u')}]
topphp server is started on {$this->server->host}:{$this->server->port}" . PHP_EOL;
        }
        $this->app->bind(SwooleApp::class, $this->server);
        $this->server->start();
    }

    /**
     * @param Server $server
     * @author sleep
     */
    private function setBaseServerListeners($server)
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
     * @param Server|HttpServer|RpcServer|WebSocketServer|TcpServer $class
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
