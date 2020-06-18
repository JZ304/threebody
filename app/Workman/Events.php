<?php

namespace App\Workerman;

class Events
{
    // workman 监听
    public static function onWorkerStart($businessWorker)
    {
        logger('[进程启动]');
    }

    public static function onConnect($client_id)
    {
        logger('[onConnect]客户端:'.$client_id.'已连接!');
    }

    public static function onWebSocketConnect($client_id, $data)
    {
        logger('[onWebSocketConnect]客户端:'.$client_id.'已连接!');
    }

    public static function onMessage($client_id, $message)
    {
        logger('[onMessage]客户端:'.$client_id.'发送信息:'.$message);
    }

    public static function onClose($client_id)
    {
        logger('[onClose]客户端:'.$client_id.'断开连接!');
    }
}
