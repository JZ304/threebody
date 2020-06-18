<?php

namespace App\Listeners;

use App\Events\register;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class userRegister
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
        logger('【userRegister监听器】构造函数');
    }

    /**
     * Handle the event.
     *
     * @param  register  $event
     * @return void
     */
    public function handle(register $event)
    {
        //
        logger('【userRegister监听器】handle');
    }
}
