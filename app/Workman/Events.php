<?php

namespace App\Workerman;

use App\Models\UserModel;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Workerman\Worker;

class Events
{

    // workman 监听
    public static function onWorkerStart($businessWorker)
    {
        logger('[进程启动]');
    }

    public static function onConnect($client_id)
    {
        logger('[onConnect]客户端:' . $client_id . '已连接!');
    }

    public static function onWebSocketConnect($client_id, $data)
    {
        logger('[onWebSocketConnect]客户端:' . $client_id . '已连接!');
    }

    public static function onMessage($client_id, $message)
    {

        logger('[3][onMessage]客户端:' . $client_id . '发送信息:' . $message);
        $message = json_decode($message);
        if ($message->event == 'login') {
            $tel = $message->tel;
            //绑定
            Gateway::bindUid($client_id, $tel);
        }
    }

    public static function onClose($client_id)
    {
        UserModel::query()->where(['client_id' => $client_id])->update(['client_id' => 0]);
        logger('[onClose]客户端:' . $client_id . '断开连接!');
    }
}
