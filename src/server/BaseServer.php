<?php
/**
 * 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * Project: topphp-swoole
 * Date: 2020/2/8 22:10
 * Author: sleep <sleep@kaituocn.com>
 */
declare(strict_types=1);

namespace Topphp\TopphpSwoole\server;

use RuntimeException;
use Swoole\Server as SwooleServer;
use think\facade\App;
use Topphp\TopphpSwoole\SwooleEvent;

class BaseServer
{
    public static function getEvents(): array
    {
        return [
            SwooleEvent::ON_WORKER_START,
            SwooleEvent::ON_START,
            SwooleEvent::ON_TASK,
            SwooleEvent::ON_PIPE_MESSAGE,
            SwooleEvent::ON_CLOSE,
        ];
    }

    /**
     * 在这个回调函数中可以修改管理进程的名称。
     * @param SwooleServer $server
     * @author sleep
     */
    public static function onManagerStart(SwooleServer $server): void
    {
        self::setProcessName('manager process ' . $server->manager_pid);
        echo 'onManagerStart' . PHP_EOL;
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。
     * 设置了worker_num和task_worker_num超过1时，每个进程都会触发一次onWorkerStart事件，可通过判断$worker_id区分不同的工作进程
     * @param SwooleServer $server
     * @param int $workerId
     * @author sleep
     */
    public static function onWorkerStart(SwooleServer $server, int $workerId): void
    {
        self::clearCache();
        self::setProcessName($server->taskworker ? 'task process' : 'worker process');
        echo "workerId: $workerId is working\n";
    }

    public static function onStart(SwooleServer $server): void
    {
        self::setProcessName('master process');
        self::create($server->master_pid, $server->manager_pid ?? 0);
        echo "server is started: {$server->host}:{$server->port}\n";
    }

    public static function onTask(SwooleServer $server, $taskId, $fromId, string $data): void
    {
        echo "New AsyncTask[id=$taskId]\n";
        App::getInstance()->event->trigger('topphp.BaseServer.onTask', [
            'server' => $server,
            'taskId' => $taskId,
            'fromId' => $fromId,
            'data'   => $data
        ]);
        $server->finish("$data -> OK");
    }

    public static function onPipeMessage(SwooleServer $server, $workerId, string $data): void
    {
        App::getInstance()->event->trigger('topphp.BaseServer.onPipeMessage', [
            'server'   => $server,
            'workerId' => $workerId,
            'data'     => $data
        ]);
        echo "$data\n";
    }

    public static function onClose(SwooleServer $server, int $fd, int $reactorId): void
    {
        App::getInstance()->event->trigger('topphp.BaseServer.onClose', [
            'server'    => $server,
            'fd'        => $fd,
            'reactorId' => $reactorId
        ]);
        echo "closed $fd\n";
    }

    /**
     * 此事件在Worker进程终止时发生。在此函数中可以回收Worker进程申请的各类资源
     * 注意:请勿在onWorkerStop中调用任何异步或协程相关API，触发onWorkerStop时底层已销毁了所有事件循环设施。
     * @param SwooleServer $server
     * @param int $workerId 是一个从0-$worker_num之间的数字，表示这个Worker进程的ID,和进程PID没有任何关系
     * @author sleep
     */
    public static function onWorkerStop(SwooleServer $server, int $workerId): void
    {
        App::getInstance()->event->trigger('topphp.BaseServer.onWorkerStop', [
            'server'   => $server,
            'workerId' => $workerId
        ]);
        echo "workerId: $workerId is stop\n";
    }

    /**
     * 当Worker/Task进程发生异常后会在Manager进程内回调此函数。
     * @param SwooleServer $server
     * @param int $workerId 是异常进程的编号
     * @param int $workerPid 是异常进程的ID
     * @param int $exitCode 退出的状态码，范围是 0～255
     * @param int $signal 进程退出的信号
     * @author sleep
     */
    public static function onWorkerError(
        SwooleServer $server,
        int $workerId,
        int $workerPid,
        int $exitCode,
        int $signal
    ): void {
        App::getInstance()->event->trigger('topphp.BaseServer.onWorkerError', [
            'server'    => $server,
            'workerId'  => $workerId,
            'workerPid' => $workerPid,
            'exitCode'  => $exitCode,
            'signal'    => $signal,
        ]);
        echo "workerId: $workerId,workerPid:$workerPid is error\n";
    }

    /**
     * 当管理进程结束时调用它
     * @param SwooleServer $server
     * @author sleep
     */
    public static function onManagerStop(SwooleServer $server): void
    {
        // todo onManagerStop触发时，说明Task和Worker进程已结束运行，已被Manager进程回收。
        App::getInstance()->event->trigger('topphp.BaseServer.onWorkerError', [
            'server' => $server
        ]);
    }

    private static function create(int $masterPid, int $managerPid)
    {
        $file = config('topphpServer.options.pid_file');
        if (!is_writable($file)
            && !is_writable(dirname($file))
        ) {
            throw new RuntimeException(
                sprintf('Pid file "%s" is not writable', $file)
            );
        }
        file_put_contents($file, $masterPid . ',' . $managerPid);
    }

    /**
     * @author sleep
     * 如果 PHP 开启了 APC/OpCache，reload 重载入时会受到影响
     * 在 onWorkerStart 中执行 apc_clear_cache 或 opcache_reset 刷新 OpCode 缓存
     */
    private static function clearCache()
    {
        if (extension_loaded('apc')) {
            apc_clear_cache();
        }

        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @param $process
     */
    private static function setProcessName($process)
    {
        // Mac OSX不支持进程重命名
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }
        $serverName = 'swoole_http_server';
        $appName    = config('app.name', 'topphp');
        $name       = sprintf('%s: %s for %s', $serverName, $process, $appName);
        swoole_set_process_name($name);
    }
}
