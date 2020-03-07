<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-skeleton
 * Date: 2020/2/4 16:13
 * Author: sleep <sleep@kaituocn.com>
 */

use Topphp\TopphpSwoole\server\HttpServer;
use Topphp\TopphpSwoole\server\RpcServer;
use Topphp\TopphpSwoole\server\WebSocketServer;

return [
    'mode'    => SWOOLE_PROCESS,                  // 运行模式为SWOOLE_PROCESS时支持热重启.
    'servers' => [
        [
            'type'      => HttpServer::class,
            'name'      => 'gateway',
            'host'      => '0.0.0.0',                       // 监听地址
            'port'      => 9501,                            // 监听端口
            'sock_type' => SWOOLE_SOCK_TCP,
            'options'   => [
                // 开启websocket服务时设为true
                'open_websocket_protocol' => false
            ]
        ],
        [
            'type'      => RpcServer::class,
            'name'      => 'film-server',
            'host'      => '0.0.0.0',                       // 监听地址
            'port'      => 9502,                            // 监听端口
            'sock_type' => SWOOLE_SOCK_TCP,
            'options'   => []
        ],
        [
            'type'      => RpcServer::class,
            'name'      => 'film-server',
            'host'      => '0.0.0.0',                       // 监听地址
            'port'      => 9503,                            // 监听端口
            'sock_type' => SWOOLE_SOCK_TCP,
            'options'   => []
        ],
//        [
//            'type'      => WebSocketServer::class,
//            'name'      => 'top-WebSocketServer',
//            'host'      => '0.0.0.0',                        // 监听地址
//            'port'      => 9504,                             // 监听端口
//            'sock_type' => SWOOLE_SOCK_TCP,
//            'options'   => []
//        ],
    ],
    'options' => [
        'pid_file'              => runtime_path() . 'topphp_swoole.pid',
        'log_file'              => runtime_path() . 'topphp_swoole.log',
        'daemonize'             => !env('APP_DEBUG'),   // 是否开启守护进程
        // Normally this value should be 1~4 times larger according to your cpu cores.
        'worker_num'            => swoole_cpu_num(),
        'task_worker_num'       => swoole_cpu_num(),    // 配置 Task 进程的数量。【默认值：未配置则不启动 task】
        'task_enable_coroutine' => true,
        'task_max_request'      => 1000000,
        'enable_static_handler' => true,
        'document_root'         => root_path('public'),
        'package_max_length'    => 20 * 1024 * 1024,
        'buffer_output_size'    => 10 * 1024 * 1024,
        'socket_buffer_size'    => 128 * 1024 * 1024,
        'max_request'           => 1000000,
        'max_wait_time'         => 60,
        'send_yield'            => true,
    ],

    'clients' => [
        [
            'name'     => 'film-server',
            'balancer' => 'random',
            'nodes'    => [
                ['host' => '0.0.0.0', 'port' => 9502],
                ['host' => '127.0.0.1', 'port' => 9503]
            ],
            'publish'  => [
                'name'    => 'consul',
                'address' => '127.0.0.1:8500'
            ],
            'options'  => []
        ],
        [
            'name'     => 'cinema-server',
            'balancer' => 'random',
            'nodes'    => [
                ['host' => '192.168.31.108', 'port' => 9502],
                ['host' => '127.0.0.1', 'port' => 9503]
            ],
            'publish'  => [
                'name'    => 'consul',
                'address' => '127.0.0.1:8500'
            ],
            'options'  => []
        ]
    ],
];
