<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/6 19:15
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace topphp\swoole\server;

use Throwable;
use think\Cookie;
use think\facade\App;
use think\exception\Handle;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;
use topphp\swoole\contract\SwooleHttpServerInterface;
use topphp\swoole\SwooleEvent;

class HttpServer extends SwooleHttpServer implements SwooleHttpServerInterface
{
    public static $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        449 => 'Retry With',
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    public static function getEvents(): array
    {
        return [
            SwooleEvent::ON_REQUEST,
        ];
    }

    public static function onRequest(SwooleHttpRequest $req, SwooleHttpResponse $res): void
    {
        $app     = App::getInstance();
        $request = self::prepareRequest($req);
        try {
            $response = $app->http->run($request);
        } catch (Throwable $e) {
            $response = $app->make(Handle::class)->render($request, $e);
        }
        self::sendResponse($res, $response, $app->cookie);
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
     * @param Cookie $cookie
     * @author sleep
     */
    private static function sendResponse(Response $res, \think\Response $response, Cookie $cookie)
    {
        // 设置header
        foreach ($response->getHeader() as $key => $val) {
            $res->setHeader($key, $val);
        }
        //设置状态码
        $code = $response->getCode();
        if (!isset(self::$statusTexts[$code])) {
            self::$statusTexts[$code] = 'unknown status';
        }
        $res->setStatusCode($code, self::$statusTexts[$code]);

        foreach ($cookie->getCookie() as $name => $val) {
            [$value, $expire, $option] = $val;
            $res->setCookie(
                $name,
                $value,
                $expire,
                $option['path'],
                $option['domain'],
                $option['secure'] ? true : false,
                $option['httponly'] ? true : false
            );
        }
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
