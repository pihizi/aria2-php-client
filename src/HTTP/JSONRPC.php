<?php

namespace Aria2Client\HTTP;

class JSONRPC implements \Aria2Client\Aria2Interface
{

    private $_server;
    private $_token;
    private $_proxy;
    private $_ch;
    private $_uniqid = 1;

    public function __construct($server, $token=null, $proxy=null)
    {
        $this->_server = $server;
        $this->_token = $token;
        $this->_proxy = $proxy;
        $this->_ch = curl_init($server);

        $iTimeout = 5;
        curl_setopt_array($this->_ch, [
            CURLOPT_CONNECTTIMEOUT=> $iTimeout,
            CURLOPT_TIMEOUT=> $iTimeout,
            CURLOPT_RETURNTRANSFER=> true,
            CURLOPT_HEADER => false,
            CURLOPT_POST=> true
        ]);
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }

    private function _request($method, array $data=[])
    {
        if ($this->_token) array_unshift($data, "token::{$this->_token}");
        $iData = [
            'jsonrpc'=> '2.0',
            'id'=> base_convert($this->_uniqid ++, 10, 36),
            'method'=> "aria2.{$method}",
            'params'=> $data
        ];

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, json_encode($iData));

        $iResult = curl_exec($this->_ch);
        $iError = curl_errno($this->_ch) ? curl_error($this->_ch) : null;

        $iResult = json_decode($iResult, 1);

        return $iResult['result'];
    }

    private function _getDownloadingUris()
    {
        $keys = ['gid', 'files'];
        $iActives = (array) $this->_request('tellActive', [$keys]);

        $iGetUris = function(array $items=[]) {
            $tResult = [];
            foreach ($items as $item) {
                $tStart = strlen($item['dir']);
                foreach ((array) $item['files'] as $f) {
                    if (!empty($f['uri'])) {
                        $tResult[$f['uri']] = substr($f['path'], $tStart);
                    }
                }
            }
            return $tResult;
        };

        return $iGetUris($iActives);

    }

    public function addURL($url, $file=null)
    {
        $iActives = $this->_getDownloadingUris();
        if (
            !empty($iActives)
            && (
                (!$file && isset($iActives[$url]))
                ||
                ($file && $iActives[$url]===$file)
            )
        ) {
            return true;
        }

        $iOptions = [
            'allow-overwrite'=> 'true',
            'auto-file-renaming'=> 'false'
        ];

        if ($file) {
            $iOptions['out'] = $file;
        }

        if ($this->_proxy) {
            $iOptions['http-proxy'] = $this->_proxy;
        }

        $iResult = $this->_request('addUri', [[$url], $iOptions]);
        if (!$iResult) return false;

        return true;
    }

}
