<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 19:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\server;

use think\facade\App;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Server as SwooleServer;
use topphp\swoole\contract\SwooleHttpServerInterface;
use topphp\swoole\SwooleEvent;

class HttpServer extends SwooleHttpServer implements SwooleHttpServerInterface
{
    public static function getEvents(): array
    {
        return [
            SwooleEvent::ON_START,
            SwooleEvent::ON_REQUEST,
            SwooleEvent::ON_TASK
        ];
    }

    public static function onStart(SwooleHttpServer $server): void
    {
        echo "http server is started: {$server->host}:{$server->port}\n";
    }

    public static function onRequest(SwooleHttpRequest $req, SwooleHttpResponse $res): void
    {
        $request  = self::prepareRequest($req);
        $response = App::getInstance()->http->run($request);
        self::sendResponse($res, $response);
    }

    public static function onTask(SwooleServer $server, $taskId, $fromId, $data): void
    {
        echo "New AsyncTask[id=$taskId]\n";
        $server->finish("$data -> OK");
    }

    private static function prepareRequest(Request $req)
    {
        $header = $req->header ?: [];
        $server = $req->server ?: [];

        foreach ($header as $key => $value) {
            $server["http_" . str_replace('-', '_', $key)] = $value;
        }
        // 重新实例化请求对象 处理swoole请求数据
        /** @var \think\Request $request */
        $request = App::getInstance()->make('request', [], false);

        return $request->withHeader($header)
            ->withServer($server)
            ->withGet($req->get ?: [])
            ->withPost($req->post ?: [])
            ->withCookie($req->cookie ?: [])
            ->withFiles($req->files ?: [])
            ->withInput($req->rawContent())
            ->setBaseUrl($req->server['request_uri'])
            ->setUrl($req->server['request_uri'] . (!empty($req->server['query_string'])
                    ? '?' . $req->server['query_string'] : ''))
            ->setPathinfo(ltrim($req->server['path_info'], '/'));
    }

    /**
     * 生成返回数据对象
     * @param SwooleHttpResponse $res
     * @param \think\Response $response
     * @author sleep
     */
    private static function sendResponse(Response $res, \think\Response $response)
    {
        $res->setHeader('Content-Type', $response->getHeader('Content-Type'));
        $content = $response->getContent();
        self::sendByChunk($res, $content);
    }

    /**
     * 拆分数据显示到浏览器,大于8192字节分批写入
     * @param SwooleHttpResponse $res
     * @param $content
     * @author sleep
     */
    private static function sendByChunk(Response $res, $content)
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
