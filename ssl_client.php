<?php
/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/4/8
 * Time: 上午11:28
 */

$config = [
    'ssl_cert_file' => __DIR__.'/ca/proxy.crt',
    'ssl_key_file'  => __DIR__.'/ca/proxy.key'
];
$host = '192.168.99.10';
$port = 443;
$size = 4096;
$ssl_client = new swoole_client(SWOOLE_TCP);
$ssl_client->set($config);
$ssl_client->connect($host,$port,0.5);
$ssl_client->send('swoole ssl client test');
$content = $ssl_client->recv(4096);
print_r($content);
$ssl_client->close();
