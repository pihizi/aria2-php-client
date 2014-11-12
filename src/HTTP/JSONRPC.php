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
            CURLOPT_HTTPHEADER => array(
                'Content-Type' => 'application/json'
            ),
            CURLOPT_POST=> true
        ]);
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }

    private function _request($method, array $data=[])
    {
        if ($this->_token) array_unshift($data, $this->_token);
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
        $iWaitings = (array) $this->_request('tellWaiting', [0, -1, $keys]);
        $iStops = (array) $this->_request('tellStopped', [0, -1, $keys]);

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

        return [$iGetUris($iActives), $iGetUris($iWaitings), $iGetUris($iStops)];
    }

    public function addURL($url, $file=null)
    {
        list($iActives, $iWaitings) = $this->_getDownloadingUris();


        if (
            !empty($iActives) && !empty($iWaitings) 
            && (
                (!$file && (isset($iActives[$url]) || isset($iWaitings[$url])))
                ||
                ($file && ($iActives[$url]===$file || $iWaitings[$url]===$file))
            )
        ) {
            return true;
        }

        $iOptions = [
            'auto-file-naming'=> false
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
