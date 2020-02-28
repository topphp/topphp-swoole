<?php

namespace Topphp\TopphpSwoole\server\jsonrpc\exceptions;

use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;

/**
 * If a method cannot be called (e.g. if the method doesn't exist, or is a
 * private method), then you should throw a "MethodException".
 *
 * If the method is callable, but the user-supplied arguments are incompatible
 * with the method's type signature, or an argument is invalid, then you should
 * throw an "ArgumentException".
 *
 * If the method is callable, and the user-supplied arguments are valid, but an
 * issue arose when the server-side application was evaluating the method, then
 * you should throw an "ApplicationException".
 *
 * If you've extended this JSON-RPC 2.0 library, and an issue arose in your
 * implementation of the JSON-RPC 2.0 specifications, then you should throw an
 * "ImplementationException".
 *
 * @link http://www.jsonrpc.org/specification#error_object
 */
class ArgumentException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid params', ErrorResponse::INVALID_ARGUMENTS);
    }
}
