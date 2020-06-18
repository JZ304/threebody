<?php

namespace App\Listeners;

use App\Events\income;
use App\Http\Controllers\Controller;
use App\Models\UserModel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class addUserBalance extends Controller
{
    protected $money;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param income $event
     * @return void
     */
    public function handle(income $event)
    {
        //
        $this->money = $event->money;

        $user = $this->getUserInfo();

        UserModel::where('id', $user['id'])->increment('balance', $this->money);
    }
}
