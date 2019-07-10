<?php

namespace App\Listeners\Pin\Create;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Trial
{
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
     * @param  \App\Events\Pin\Create  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        if ($event->doPublish)
        {
            dispatch(new \App\Jobs\Trial\PinTrial($event->pin->slug, 0));
        }
    }
}
