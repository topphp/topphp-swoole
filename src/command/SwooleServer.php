<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:05
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\command;

use Swoole\Http\Server as HttpServer;
use Swoole\Server;
use Swoole\WebSocket\Server as WebsocketServer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\swoole\PidManager;

class SwooleServer extends Command
{
    /**
     * @var array
     */
    protected $events = [
        'start',
        'shutDown',
        'workerStart',
        'workerStop',
        'workerError',
        'workerExit',
        'packet',
        'task',
        'finish',
        'pipeMessage',
        'managerStart',
        'managerStop',
        'request',
    ];

    protected function initialize(Input $input, Output $output)
    {
        $this->app->bind(\Swoole\Server::class, function () {
            return $this->createSwooleServer();
        });
    }

    protected function configure()
    {
        $this->setName("server")->setDescription("开启swoole http服务");
    }

    public function handle()
    {
        $http = new \Swoole\Http\Server("127.0.0.1", 9501);

        $http->on("start", function ($server) {
            echo "Swoole http server is started at http://127.0.0.1:9501\n";
        });

        $http->on("request", function ($request, $response) {
            $response->header("Content-Type", "text/plain");
            $response->end("Hello World\n");
        });

        $http->start();
        $this->output->writeln("swoole server is start");
    }

    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = Str::camel("on_$event");
            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
                $this->triggerEvent($event, func_get_args());
            };

            $this->getServer()->on($event, $callback);
        }
    }

    private function createSwooleServer()
    {
        $config     = $this->app->config;
        $host       = $config->get('topphpServer.server.host');
        $port       = $config->get('topphpServer.server.port');
        $socketType = $config->get('topphpServer.server.socket_type', SWOOLE_SOCK_TCP);
        $mode       = $config->get('topphpServer.server.mode', SWOOLE_PROCESS);

        /** @var \Swoole\Server $server */
        $server  = new $serverClass($host, $port, $mode, $socketType);
        $options = $config->get('topphpServer.server.options');
        $server->set($options);
        return $server;
    }
}
