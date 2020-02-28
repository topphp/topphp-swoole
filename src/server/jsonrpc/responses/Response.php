<?php

namespace Topphp\TopphpSwoole\server\jsonrpc\responses;

abstract class Response
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
