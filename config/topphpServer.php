<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-skeleton
 * Date: 2020/2/4 16:13
 * Author: sleep <sleep@kaituocn.com>
 */

use topphp\swoole\server\HttpServer;
use topphp\swoole\server\TcpServer;

return [
    // 运行模式 默认为SWOOLE_PROCESS
    'mode'    => SWOOLE_PROCESS,
    'servers' => [
        [
            'name'      => 'top-server1',
            'host'      => env('SWOOLE_HOST', '127.0.0.1'),
            'port'      => 9501,
            'type'      => HttpServer::class,
            'sock_type' => SWOOLE_SOCK_TCP,
            'options'   => []
        ],
        [
            'name'      => 'top-server2',
            'host'      => env('SWOOLE_HOST', '127.0.0.1'),
            'port'      => 9502,
            'type'      => TcpServer::class,
            'sock_type' => SWOOLE_SOCK_TCP,
            'options'   => [
                'open_websocket_protocol' => true
            ]
        ],
    ],
    'options' => [
        'enable_coroutine'         => true,
        'open_http2_protocol'      => true,
        'pid_file'                 => runtime_path() . 'topphp_swoole.pid',
        'log_file'                 => runtime_path() . 'topphp_swoole.log',
        // 是否开启守护进程
        'daemonize'                => false,
        // Normally this value should be 1~4 times larger according to your cpu cores.
        'reactor_num'              => swoole_cpu_num(),
        'worker_num'               => swoole_cpu_num(),
        'task_worker_num'          => swoole_cpu_num(),
        'task_enable_coroutine'    => true,
        'task_max_request'         => 3000,
        'package_max_length'       => 20 * 1024 * 1024,
        'buffer_output_size'       => 10 * 1024 * 1024,
        'socket_buffer_size'       => 128 * 1024 * 1024,
        'send_yield'               => true,
        'max_coroutine'            => 100000,
        'max_request'              => 100000,
        // 设置异步重启开关。设置为true时，将启用异步安全重启特性，Worker进程会等待异步事件完成后再退出。
        'reload_async'             => true,
        // 是否开启静态资源访问
        'enable_static_handler'    => true,
        'document_root'            => root_path('public'),
        'static_handler_locations' => ['/'],
    ],
];
