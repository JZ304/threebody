<?php

namespace App\Workerman;

use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
        if($message->event == 'login'){
            $tel = $message->tel;
            logger('TEL:'.$tel.';Client_id:'.$client_id);
            UserModel::where('tel',$tel)->update(['client_id' => $client_id]);
        }else{
            // 其它业务暂不处理
            logger('其它业务暂不处理');
        }
    }

    public static function onClose($client_id)
    {
        UserModel::query()->where(['client_id' => $client_id])->update(['client_id' => 0]);
        logger('[onClose]客户端:' . $client_id . '断开连接!');
    }
}
