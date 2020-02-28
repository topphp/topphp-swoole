<?php

namespace Topphp\TopphpSwoole\server\jsonrpc\responses;

/**
 * The result returned by the server
 *
 * @link https://www.jsonrpc.org/specification#response_object
 */
class ResultResponse extends Response
{
    /** @var mixed */
    private $value;

    /**
     * @param mixed $id
     * A unique identifier. This MUST be the same as the original request id.
     * If there was an error while processing the request, then this MUST be null.
     *
     * @param mixed $value
     * The value returned by the server.
     */
    public function __construct($id, $value)
    {
        parent::__construct($id);

        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
