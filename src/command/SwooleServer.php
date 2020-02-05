<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/4 15:05
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\command;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use think\App;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Swoole\Http\Server as HttpServer;
use Swoole\WebSocket\Server as WebsocketServer;

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
        $this->getServer()->on("request", function (Request $req, Response $res) {
            $request = $this->prepareRequest($req);

            $response = $this->app->http->run($request);
            $this->sendResponse($res, $response);
        });

        $this->getServer()->on('task', function ($server, $task_id, $reactor_id, $data) {
            echo "New AsyncTask[id=$task_id]\n";
            $server->finish("$data -> OK");
        });
        $this->getServer()->start();
        $this->output->writeln("swoole server is start");
    }

//    protected function setSwooleServerListeners()
//    {
//        foreach ($this->events as $event) {
//            $listener = Str::camel("on_$event");
//            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
//                $this->triggerEvent($event, func_get_args());
//            };
//
//            $this->getServer()->on($event, $callback);
//        }
//    }

    protected function createSwooleServer()
    {
        $isWebsocket = $this->app->config->get('swoole.websocket.enable', false);

        $serverClass = $isWebsocket ? WebsocketServer::class : HttpServer::class;
        $config      = $this->app->config;
        $host        = $config->get('topphpServer.server.host');
        $port        = $config->get('topphpServer.server.port');
        $socketType  = $config->get('topphpServer.server.socket_type', SWOOLE_SOCK_TCP);
        $mode        = $config->get('topphpServer.server.mode', SWOOLE_PROCESS);

        /** @var \Swoole\Server $server */
        $server = new $serverClass($host, $port, $mode, $socketType);

        $options = $config->get('topphpServer.server.options');

        $server->set($options);
        return $server;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->app->make(Server::class);
    }

    protected function prepareRequest(Request $req)
    {
        $header = $req->header ?: [];
        $server = $req->server ?: [];

        foreach ($header as $key => $value) {
            $server["http_" . str_replace('-', '_', $key)] = $value;
        }

        // 重新实例化请求对象 处理swoole请求数据
        /** @var \think\Request $request */
        $request = $this->app->make('request', [], true);

        return $request->withHeader($header)
            ->withServer($server)
            ->withGet($req->get ?: [])
            ->withPost($req->post ?: [])
            ->withCookie($req->cookie ?: [])
            ->withFiles($req->files ?: [])
            ->withInput($req->rawContent())
            ->setBaseUrl($req->server['request_uri'])
            ->setUrl($req->server['request_uri'] . (!empty($req->server['query_string']) ? '?' . $req->server['query_string'] : ''))
            ->setPathinfo(ltrim($req->server['path_info'], '/'));
    }

    protected function sendResponse(Response $res, \think\Response $response)
    {
        $content = $response->getContent();
        $this->sendByChunk($res, $content);
    }

    protected function sendByChunk(Response $res, $content)
    {
        $chunkSize = 8192;
        if (strlen($content) <= $chunkSize) {
            $res->end($content);
            return;
        }

        foreach (str_split($content, $chunkSize) as $chunk) {
            $res->write($chunk);
        }
        $res->end();
    }
}
