<?php

namespace Aria2Client;

use Aria2Client\HTTP\JSONRPC as JSONRPC;

class Client
{
    private $_client;

    public function __construct($server='http://127.0.0.1:6800/jsonrpc', $token=null, $proxy=null)
    {
        $this->_client = new JSONRPC($server, $token, $proxy);
    }

    public function __call($method, array $params=[]) 
    {
        return call_user_func_array([$this->_client, $method], $params);
    }
}
