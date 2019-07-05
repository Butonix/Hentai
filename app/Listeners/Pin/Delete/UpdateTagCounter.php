<?php

namespace App\Listeners\Pin\Delete;

use App\Http\Modules\Counter\TagPatchCounter;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateTagCounter
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
     * @param  \App\Events\Pin\Delete  $event
     * @return void
     */
    public function handle(\App\Events\Pin\Delete $event)
    {
        if ($event->pin->visit_type != 1)
        {
            $event->pin->tags()
                ->increment('pin_count', -1);

            $list = $event->pin->tags()
                ->pluck('slug')
                ->toArray();

            $tagPatchCounter = new TagPatchCounter();
            foreach ($list as $slug)
            {
                $tagPatchCounter->add($slug, 'pin_count', -1);
            }
        }
    }
}