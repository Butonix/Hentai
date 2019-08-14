<?php


namespace App\Listeners\Tag\Update;


use App\Http\Repositories\TagRepository;
use App\Models\Search;

class UpdateTagSearch
{
    public function __construct()
    {

    }

    public function handle(\App\Events\Tag\Update $event)
    {
        $tag = $event->tag;
        $tagRepository = new TagRepository();
        $txtTag = $tagRepository->item($tag->slug);

        $search = Search
            ::where('type', 1)
            ->where('slug', $tag->slug)
            ->first();

        if (!$search)
        {
            return;
        }

        $text = $txtTag->alias;

        $search->update([
            'text' => $text
        ]);
    }
}
