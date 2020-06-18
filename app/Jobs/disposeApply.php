<?php

namespace App\Jobs;

use App\Models\ApplyModel;
use App\Models\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class disposeApply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        if ($order['type'] == 'apply') {
            $this->order = $order;
        } else {
            $this->order = null;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        if (!is_null($order)) {
            // 处理逻辑
            if ($order['money'] > 100) {
                // 申请通过
                ApplyModel::whereId($order['id'])->update(['status' => 2]);
            } else {
                // 申请未通过
                ApplyModel::whereId($order['id'])->update(['status' => 3, 'reason' => '申请金额过少']);

                // 将金额归还到原账户
                UserModel::whereId($order['user_id'])->increment('balance', $order['money']);
            }
        }
    }
}
