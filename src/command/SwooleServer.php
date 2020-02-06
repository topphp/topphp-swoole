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
use topphp\swoole\ServerConfig;
use topphp\swoole\SwooleApp;

class SwooleServer extends Command
{
    /** @var ServerConfig $config */
    private $config;

    protected function initialize(Input $input, Output $output)
    {
        $this->app->bind(SwooleApp::class, $this->initSwooleServer());
        $this->setSwooleServerListeners();
    }

    protected function configure()
    {
        $this->setName("server")->setDescription("开启swoole服务");
    }

    public function handle()
    {
        $this->getServer('top-server2')->on('receive', function (Server $server, $fd, $reactor_id, $data) {
            echo $data . PHP_EOL;
            $server->send($fd, "Swoole: {$data}");
        });
        $this->getServer('top-server2')->on('task', function (SwooleServer $server, $taskId, $fromId, $data) {
        });

//        $this->getServer()->on('close', function (Server $server, $fd) {
//            echo "connection close: {$fd}\n";
//        });

        Runtime::enableCoroutine(true, defined('SWOOLE_HOOK_FLAGS') ? SWOOLE_HOOK_FLAGS : SWOOLE_HOOK_ALL);

        $this->getServer('top-server2')->start();
        $this->output->writeln("swoole server is start");
    }

    private function initSwooleServer()
    {
        $servers = $this->app->config->get('topphpServer.servers');
        $mode    = $this->app->config->get('topphpServer.mode', SWOOLE_PROCESS);
        $options = $this->app->config->get('topphpServer.options');
        foreach ($servers as $server) {
            /** @var ServerConfig $cfg */
            $cfg         = $this->app->make(ServerConfig::class, [$server], true);
            $serverClass = $cfg->getType();

            /** @var Server $server */
            $server = $this->app->make((string)$serverClass, [
                $cfg->getHost(),
                $cfg->getPort(),
                $mode,
                $cfg->getSockType()
            ], true);
            if (!empty($cfg->getOptions())) {
                // 如果配置文件中 某个服务内部options配置不为空,与外层options合并
                $option = array_merge($cfg->getOptions(), $options);
                $server->set($option);
            } else {
                // 否则直接使用外层options配置
                $server->set($options);
            }
            $this->app->bind($cfg->getName(), $server);
            $this->config[$cfg->getName()] = $cfg;
        }
    }

    private function setSwooleServerListeners()
    {
        //todo 遍历服务和事件获取监听
        var_dump($this->config);
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
