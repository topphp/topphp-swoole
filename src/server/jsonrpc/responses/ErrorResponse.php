<?php
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server\jsonrpc\responses;

/**
 * A description of an error that occurred on the server
 *
 * @link https://www.jsonrpc.org/specification#error_object
 */
class ErrorResponse extends Response
{
    const PARSE_ERROR       = -32700; // 服务器接收到无效的JSON；服务器解析JSON文本发生错误。
    const INVALID_REQUEST   = -32600; // 发送的JSON不是一个有效的请求。
    const INVALID_METHOD    = -32601; // 方法不存在或不可见。
    const INVALID_ARGUMENTS = -32602; // 无效的方法参数。
    const INTERNAL_ERROR    = -32603; // JSON-RPC内部错误。

    /** @var string */
    private $message;

    /** @var int */
    private $code;

    /** @var mixed */
    private $data;

    /**
     * @param mixed $id
     * A unique identifier. This MUST be the same as the original request id.
     * If there was an error while processing the request, then this MUST be null.
     *
     * @param string $message
     * Short description of the error that occurred. This message SHOULD
     * be limited to a single, concise sentence.
     *
     * @param int $code
     * Integer identifying the type of error that occurred.
     *
     * @param null|boolean|integer|float|string|array $data
     * An optional primitive value that contains additional information about
     * the error.
     */
    public function __construct($id, string $message, int $code, $data = null)
    {
        parent::__construct($id);

        $this->message = $message;
        $this->code    = $code;
        $this->data    = $data;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData()
    {
        return $this->data;
    }
}
