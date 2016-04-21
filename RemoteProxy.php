<?php

/**
 * Created by PhpStorm.
 * User: king
 * Date: 16/4/20
 * Time: 上午1:56
 */
class RemoteProxy
{
    public $serverConfig = [
        'ssl_cert_file' => __DIR__.'/ca/proxy.crt',
        'ssl_key_file'  => __DIR__.'/ca/proxy.key'
    ];

    public function __construct()
    {
        
    }
}