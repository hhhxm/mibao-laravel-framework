<?php

namespace Mibao\LaravelFramework\Listeners;

use Mibao\LaravelFramework\Controllers\Auth\WeChatController;
use Overtrue\LaravelWeChat\Events\WeChatUserAuthorized;

class WeChatUserAuthorizedListener
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Registered  $event
     * @return void
     */
    public function handle(WeChatUserAuthorized $event)
    {
        // $user = $event->user;
        // $isNew = $event->isNewSession;
        // WeChatController::checkUser($event->user, $event->account);
        (new WeChatController)->checkUser($event);
    }
}
