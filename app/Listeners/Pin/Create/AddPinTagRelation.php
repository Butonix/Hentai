<?php

namespace App\Listeners\Pin\Create;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddPinTagRelation
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
     * @param  ExampleEvent  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Create $event)
    {
        $event->pin->tags()->save($event->area);
        $event->pin->tags()->save($event->notebook);
    }
}
