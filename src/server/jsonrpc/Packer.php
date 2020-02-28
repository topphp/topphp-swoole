<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/13 00:58
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server\jsonrpc;

class Packer
{
    protected $defaultOptions = [
        'package_length_type' => 'N',
        'package_body_offset' => 4,
    ];

    public function __construct()
    {
    }

    public function pack(string $data): string
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        return pack($this->defaultOptions['package_length_type'], strlen($data)) . $data;
    }

    public function unpack(string $data)
    {
        $data = substr($data, $this->defaultOptions['package_body_offset']);
        return json_decode($data, true);
    }
}
