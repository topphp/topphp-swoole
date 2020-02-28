<?php
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server\jsonrpc;

use Topphp\TopphpSwoole\server\jsonrpc\responses\ErrorResponse;
use Topphp\TopphpSwoole\server\jsonrpc\responses\Response;
use Topphp\TopphpSwoole\server\jsonrpc\responses\ResultResponse;
use ErrorException;

/**
 * @link http://www.jsonrpc.org/specification JSON-RPC 2.0 Specifications
 *
 * @package Topphp\TopphpSwoole
 */
class Client
{
    /** @var string */
    const VERSION = '2.0';

    /** @var array */
    private $requests;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Forget any unsent queries or notifications
     */
    public function reset(): void
    {
        $this->requests = [];
    }

    /**
     * @param mixed $id
     * @param string $method
     * @param array $arguments |null
     *
     * @return self
     * Returns the object handle (so you can chain method calls, if you like)
     */
    public function query($id, string $method, array $arguments = null): self
    {
        $request       = self::getRequest($method, $arguments);
        $request['id'] = $id;
        $this->requests[] = $request;
        return $this;
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return self
     * Returns the object handle (so you can chain method calls, if you like)
     */
    public function notify($method, array $arguments = null): self
    {
        $request          = self::getRequest($method, $arguments);
        $this->requests[] = $request;
        return $this;
    }

    /**
     * Encodes the requests as a valid JSON-RPC 2.0 string
     *
     * This also resets the Client, so you can perform more queries using
     * the same Client object.
     *
     * @return null|string
     * Returns a valid JSON-RPC 2.0 message string
     * Returns null if there is nothing to encode
     */
    public function encode(): ?string
    {
        $count = count($this->requests);
        if ($count === 0) {
            return null;
        }
        if ($count === 1) {
            $input = array_shift($this->requests);
        } else {
            $input = $this->requests;
        }
        $this->reset();
        return json_encode($input);
    }

    /**
     * Translates a JSON-RPC 2.0 server reply into an array of "Response"
     * objects
     *
     * @param string $json
     * String reply from a JSON-RPC 2.0 server
     *
     * @return Response[]
     * Returns a zero-indexed array of "Response" objects
     *
     * @throws ErrorException
     * Throws an "ErrorException" if the reply was not well-formed
     */
    public function decode(string $json): array
    {
        set_error_handler(__CLASS__ . '::onError');
        try {
            $input = json_decode($json, true);
        } finally {
            restore_error_handler();
        }
        if (($input === null) && (strtolower(trim($json)) !== 'null')) {
            $valueText = self::getValueText($json);
            throw new ErrorException("Invalid JSON: {$valueText}");
        }
        if (!$this->getResponses($input, $responses)) {
            $valueText = self::getValueText($json);
            throw new ErrorException("Invalid JSON-RPC 2.0 response: {$valueText}");
        }
        return $responses;
    }

    private static function getRequest(string $method, array $arguments = null): array
    {
        $request = [
            'jsonrpc' => self::VERSION,
            'method'  => $method
        ];
        if ($arguments !== null) {
            $request['params'] = $arguments;
        }
        return $request;
    }

    private static function getValueText($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_resource($value)) {
            $type = get_resource_type($value);
            $id   = (int)$value;
            return "{$type}#{$id}";
        }
        return var_export($value, true);
    }

    private function getResponses($input, array &$responses = null): bool
    {
        if ($this->getResponse($input, $response)) {
            $responses = [$response];
            return true;
        }
        return $this->getBatchResponses($input, $responses);
    }

    /**
     * @param $input
     * @param $response
     * @return bool
     * @author sleep
     */
    private function getResponse($input, &$response): bool
    {
        return $this->getResultResponse($input, $response) ||
            $this->getErrorResponse($input, $response);
    }

    private function getResultResponse($input, &$response): bool
    {
        if (is_array($input) &&
            !array_key_exists('error', $input) &&
            $this->getVersion($input) &&
            $this->getId($input, $id) &&
            $this->getResult($input, $value)
        ) {
            $response = new ResultResponse($id, $value);
            return true;
        }

        return false;
    }

    private function getVersion(array $input): bool
    {
        return isset($input['jsonrpc']) && ($input['jsonrpc'] === self::VERSION);
    }

    private function getId(array $input, &$id): bool
    {
        if (array_key_exists('id', $input)) {
            $id = $input['id'];
            return is_null($id) || is_int($id) || is_float($id) || is_string($id);
        }
        return false;
    }

    private function getResult(array $input, &$value): bool
    {
        if (array_key_exists('result', $input)) {
            $value = $input['result'];
            return true;
        }
        return false;
    }

    private function getErrorResponse(array &$input, &$response): bool
    {
        if (is_array($input) &&
            !array_key_exists('result', $input) &&
            $this->getVersion($input) &&
            $this->getId($input, $id) &&
            $this->getError($input, $code, $message, $data)
        ) {
            $response = new ErrorResponse($id, $message, $code, $data);
            return true;
        }

        return false;
    }

    private function getError(array $input, &$code, &$message, &$data): bool
    {
        $error = $input['error'] ?? null;

        return is_array($error) &&
            $this->getErrorCode($error, $code) &&
            $this->getErrorMessage($error, $message) &&
            $this->getErrorData($error, $data);
    }

    private function getErrorCode(array $input, &$code): bool
    {
        $code = $input['code'] ?? null;

        return is_int($code);
    }

    private function getErrorMessage(array $input, &$message): bool
    {
        $message = $input['message'] ?? null;

        return is_string($message);
    }

    private function getErrorData(array $input, &$data): bool
    {
        $data = $input['data'] ?? null;
        return true;
    }

    private function getBatchResponses($input, &$responses): bool
    {
        if (!is_array($input)) {
            return false;
        }
        $responses = [];
        $i         = 0;
        foreach ($input as $key => $value) {
            if ($key !== $i++) {
                return false;
            }
            if (!$this->getResponse($value, $responses[])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $level
     * @param $message
     * @param $file
     * @param $line
     * @throws ErrorException
     * @author sleep
     */
    public static function onError($level, $message, $file, $line)
    {
        $message = trim($message);
        $code    = 0;
        throw new ErrorException($message, $code, $level, $file, $line);
    }
}
