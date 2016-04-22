<?php

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/4/20
 * Time: 上午1:56
 */
class RemoteProxy
{
    /**
     * @var swoole_server swoole_server
     * swoole远程代理服务器
     */
    public $remoteServer;

    /**
     * @var string
     * 服务器监听地址
     */
    public $serverHost = '0.0.0.0';

    /**
     * @var int
     * 服务器监听端口,https默认使用443端口
     */
    public  $serverPort = 443;

    public $serverConfig = [
        'ssl_cert_file' => __DIR__.'/ca/proxy.crt',
        'ssl_key_file'  => __DIR__.'/ca/proxy.key'
    ];

    public function __construct()
    {
        $this->remoteServer = new swoole_server($this->serverHost,$this->serverPort,SWOOLE_PROCESS,SWOOLE_SOCK_TCP|SWOOLE_SSL);
        $this->set($this->serverConfig);
        $this->init();
    }

    /**
     * 初始化服务器异步回调函数
     */
    protected function init(){
        $this->remoteServer->on('connect',function($ssl_server,$fd){
            echo 'Client connect:'.$fd."\r\n";
        });

        $this->remoteServer->on('receive',function($ssl_server,$fd,$from_id,$data){
            echo "\r\nssl receive:$data\r\n\r\n";
            $ssl_server->send($fd,"king ssl server welcome!\r\n");
            $ssl_server->close($fd);
        });

        $this->remoteServer->on('close',function($ssl_server,$fd){
            echo "Client:".$fd."closed\r\n";
        });
    }

    public function run(){
        $this->remoteServer->start();
    }
}

$proxy = new RemoteProxy();
$proxy->run();