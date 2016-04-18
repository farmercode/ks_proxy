<?php
require "HttpParser.php";

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/4/8
 * Time: 上午11:44
 * 本地代理服务器端,服务接受浏览器请求
 */

class LocalProxy{
    const CHANNLE_TIME_OUT = 0.5;
    public $local_server;

    /**
     * @var string bind地址
     */
    public $server_host = '0.0.0.0';

    public $server_port = 5730;

    public $remoteProxyHost = '192.168.99.10';

    public $remoteProxyPort = 443;

    public $server_config = [
        'worker_num'    => 4,
        'daemonize'     => false,
    ];

    public  $client_config = [
        'ssl_cert_file' => __DIR__.'/ca/proxy.crt',
        'ssl_key_file'  => __DIR__.'/ca/proxy.key'
    ];

    /**
     * @var array 客户端发送数据
     */
    public $client_datas = [];

    public $channel_pool_map = [];

    public $client_to_channel_map = [];

    public function __construct()
    {
        $this->local_server = new swoole_server($this->server_host,$this->server_port);
        $this->local_server->set($this->server_config);
        $this->local_server->on('connect',array($this,'onServerConnect'));
        $this->local_server->on('receive',array($this,'onServerRecv'));
        $this->local_server->on('close',array($this,'onServerClose'));
    }

    public function run(){
        echo "start proxy server\r\n";
        $this->local_server->start();

    }
    /**
     * 本地服务器被连接时调用
     * @param swoole_server $server
     * @param int   $fd
     */
    public function onServerConnect($server,$fd){
        echo 'connect fd:'.$fd."\r\n";

    }

    /**
     * 服务器接到请求回调
     * @param $server
     * @param $fd
     * @param $from_id
     * @param $data
     */
    public function onServerRecv($server,$fd,$from_id,$data){
        echo "recv data:\r\n";
        $http = new HttpParser($data);
        var_dump($http->headers);
        echo "\r\n";
        if($http->headers['request']['type'] == HttpParser::REQUEST_TYPE_CONNECT){
            $this->client_datas[$fd]['data'] = $data;
            $this->establishProxyChannel($server,$fd);
            $server->send($fd,'HTTP/1.1 200 Connection Established');
        }else if($http->headers['request']['type'] == HttpParser::REQUEST_TYPE_GET){
            $channel = $this->getChannelByClientFd($fd);
            if(empty($channel)){
                $this->client_datas[$fd]['data'] = $data;
                $this->establishProxyChannel($server,$fd);
                $channel = $this->getChannelByClientFd($fd);
            }else{
                $channel->send($data);
            }
        }else{
            //$channel_fd = array_search($fd,$this->channel_pool_map);
            $channel = $this->getChannelByClientFd($fd);
            $channel->send($data);
        }
    }

    public function onServerClose($server,$fd){
        echo 'client '.$fd."close \r\n";
    }

    /**
     * 建立和远程代理服务器代理通道
     * @param $server
     * @param int   $fd 客户端与本地代理服务器建立sock的文件描述符
     *
     * @return bool
     */
    private function establishProxyChannel($server,$fd){
        if(isset($this->client_datas[$fd])) return true;
        $channel_client = new swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);
        $channel_client->set($this->client_config);
        # 通道连接成功回调
        $channel_client->on('connect',function($channel_client) use($fd){
            echo "client to proxy channel established\r\n";
            /** @var swoole_client  $channel_client */
            $channel_sock = $channel_client->sock;
            $this->channel_pool_map[$channel_client->sock] = $fd;
            $channel_data = $this->getChannelSendData($channel_sock);
            if(!empty($channel_data)){
                $channel_client->send($channel_data);
                $this->removeChannelSendData($channel_sock);
            }
        });
        $channel_client->on('receive',array($this,'onChannelRecv'));
        $channel_client->on('error',array($this,'onChannelError'));
        $channel_client->on('close',array($this,'onChannelClose'));
        # 连接远程代理服务器
        $channel_client->connect($this->remoteProxyHost,$this->remoteProxyPort,self::CHANNLE_TIME_OUT);
        $this->client_to_channel_map[$fd] = $channel_client;
    }

    public function onChannelRecv($channel_client,$data){
        $channel_fd = $channel_client->sock;
        $client_fd = $this->getClientFdByChannelFd($channel_fd);
        if(!$client_fd) return;
        echo "channel data:\n".var_dump($data);
        $this->local_server->send($client_fd,$data);
    }

    /**
     * 通道发生错误时回调
     * @param $channel_client
     */
    public function onChannelError($channel_client){
        echo "proxy channel error\r\n";
    }

    /**
     * 通道发生关闭时回调
     * @param $channel_client
     */
    public function onChannelClose($channel_client){
        echo "proxy channel closed \r\n";
    }

    /**
     * @param int   $channel_fd
     *
     * @return int
     */
    public function getClientFdByChannelFd($channel_fd) {
        return isset($this->channel_pool_map[$channel_fd])?$this->channel_pool_map[$channel_fd]:0;
    }

    /**
     * 获得频道发送数据
     * @param $channel_fd
     *
     * @return bool
     */
    public function getChannelSendData($channel_fd){
        $client_fd = $this->getClientFdByChannelFd($channel_fd);
        return $this->getClientData($client_fd);
    }

    /**
     * 移除当前频道发送内容
     * @param $channel_fd
     *
     * @return bool
     */
    public function removeChannelSendData($channel_fd){
        $client_fd = $this->getClientFdByChannelFd($channel_fd);
        if(isset($this->client_datas[$client_fd])) unset($this->client_datas[$client_fd]);
        return true;
    }

    public function getClientData($client_fd){
        if(!isset($this->client_datas[$client_fd])) return false;
        return $this->client_datas[$client_fd]['data'];
    }

    public function getChannelByClientFd($client_fd){
        return isset($this->client_to_channel_map[$client_fd])?$this->client_to_channel_map[$client_fd]:null;
    }
}

$local_proxy_server = new LocalProxy();
$local_proxy_server->run();