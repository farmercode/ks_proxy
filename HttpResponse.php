<?php

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/4/19
 * Time: 上午12:53
 */
class HttpResponse
{
    private $body = '';

    private $responseLine = 'HTTP/1.1 200 OK';

    private $isKeepAlive = false;

    private $headers = [
        'Content-Type'  => 'text/html; charset=utf-8',
        'Keep-Alive'    => 'timeout=20',
    ];

    public function __construct($data)
    {
        $this->body = $data;
    }

    public function setKeepAlive($keepalive){
        $this->isKeepAlive = $keepalive;
    }

    public function setHeaders($headers){
        $this->headers = $headers;
    }

    public function getResponse(){
        if($this->isKeepAlive){
            $this->headers['Connection'] = 'keep-alive';
        }
        $this->headers['Content-Length'] = strlen($this->body);
        $header = $this->getHeaderString();
        $header = $this->responseLine."\r\n".$header;
        $content = $header."\r\n\r\n".$this->body;
        return $content;
    }

    public function getHeaderString(){
        if(empty($this->headers)) return '';
        $header_tmp = [];
        foreach($this->headers as $k=>$v){
            $header_tmp[]="$k : $v";
        }
        return implode("\r\n",$header_tmp);
    }
}