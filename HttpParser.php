<?php

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/4/12
 * Time: 上午12:38
 */
class HttpParser
{
    const REQUEST_TYPE_CONNECT = 'CONNECT';

    const REQUEST_TYPE_GET = 'GET';

    const REQUEST_TYPE_POST = 'POST';

    private $data="";

    public $header_raw = "";

    public $body_raw = "";

    public $headers = [];

    public function __construct($data)
    {
        $this->data = $data;
        $this->init();
    }

    protected function init(){
        list($this->header_raw,$this->body_raw) = explode("\r\n",$this->data,2);
        $this->parseHeader($this->header_raw);
    }

    public function parseHeader($header_raw){
        $tmp = explode("\r\n",$header_raw);
        $request_line = array_shift($tmp);
        list($request_type,$query,$http_version) = explode(' ',$request_line);
        $this->headers['request'] = [
            'type'=>trim($request_type),
            'query' => trim($query),
            'version'   => trim($http_version)
        ];
        foreach($tmp as $header_line){
            list($header_name,$header_content) = explode(':',$header_line,2);
            $this->headers[trim($header_name)] = trim($header_content);
        }
    }
}