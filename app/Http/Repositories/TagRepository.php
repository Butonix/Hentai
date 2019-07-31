<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:31
 */

namespace App\Http\Repositories;


use App\Http\Transformers\Tag\TagBodyResource;
use App\Http\Transformers\Tag\TagItemResource;
use App\Models\QuestionRule;
use App\Models\Tag;
use App\User;

class TagRepository extends Repository
{
    public function item($slug, $refresh = false)
    {
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("tag:{$slug}", function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->with(['content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return new TagItemResource($tag);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function relation_item($slug, $refresh = false)
    {
        if (!$slug)
        {
            return null;
        }

        $result = $this->RedisItem("tag-category:{$slug}", function () use ($slug)
        {
            $tag = Tag
                ::where('slug', $slug)
                ->with(
                    [
                        'content' => function ($query)
                        {
                            $query->orderBy('created_at', 'desc');
                        },
                        'children' => function ($query)
                        {
                            $query
                                ->with(['content' => function ($q)
                                {
                                    $q->orderBy('created_at', 'desc');
                                }])
                                ->orderBy('activity_stat', 'desc')
                                ->orderBy('pin_count', 'desc')
                                ->orderBy('followers_count', 'desc')
                                ->orderBy('seen_user_count', 'desc')
                                ->take(10);
                        }
                    ]
                )
                ->first();

            if (is_null($tag))
            {
                return 'nil';
            }

            return new TagBodyResource($tag);
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }

    public function rule($slug, $refresh = false)
    {
        return $this->RedisItem("tag-join-rule:{$slug}", function () use ($slug)
        {
            return QuestionRule
                ::where('tag_slug', $slug)
                ->first();
        }, $refresh);
    }

    public function search()
    {
        $result = $this->RedisItem('tag-all-search', function ()
        {
            $tag = Tag
                ::whereIn('parent_slug', [
                    config('app.tag.bangumi'),
                    config('app.tag.topic'),
                    config('app.tag.game')
                ])
                ->with(
                    [
                        'content' => function ($query)
                        {
                            $query->orderBy('created_at', 'desc');
                        }
                    ]
                )
                ->get();

            return TagItemResource::collection($tag);
        });

        if (gettype($result) === 'string')
        {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * @param $slug
     * @param $user
     * @return bool|null|object
     */
    public function checkTagIsMarked($slug, $user)
    {
        $tag = $this->item($slug);

        if (is_null($tag))
        {
            return null;
        }

        if (!$user->hasBookmarked(slug2id($slug), Tag::class))
        {
            return false;
        }

        return $tag;
    }

    public function bookmarks($slug, $refresh = false)
    {
        $result = $this->RedisItem("user-bookmark-tags:{$slug}", function () use ($slug)
        {
            $user = User
                ::where('slug', $slug)
                ->first();

            if (is_null($user))
            {
                return 'nil';
            }

            $list = $user
                ->bookmarks(Tag::class)
                ->with(['content' => function ($query)
                {
                    $query->orderBy('created_at', 'desc');
                }])
                ->orderBy('activity_stat', 'desc')
                ->orderBy('pin_count', 'desc')
                ->orderBy('followers_count', 'desc')
                ->orderBy('seen_user_count', 'desc')
                ->get();

            if (empty($list))
            {
                return [
                    'bangumi' => [],
                    'game' => [],
                    'topic' => [],
                    'notebook' => []
                ];
            }

            $bangumi = [];
            $game = [];
            $topic = [];
            $notebook = [];

            $bangumiSlug = config('app.tag.bangumi');
            $gameSlug = config('app.tag.game');
            $topicSlug = config('app.tag.topic');
            $notebookSlug = config('app.tag.notebook');

            $list = TagItemResource::collection($list);

            foreach ($list as $item)
            {
                if ($item['parent_slug'] === $bangumiSlug)
                {
                    $bangumi[] = $item;
                }
                else if ($item['parent_slug'] === $gameSlug)
                {
                    $game[] = $item;
                }
                else if ($item['parent_slug'] === $topicSlug)
                {
                    $topic[] = $item;
                }
                else if ($item['parent_slug'] === $notebookSlug)
                {
                    $notebook[] = $item;
                }
            }

            return [
                'bangumi' => $bangumi,
                'game' => $game,
                'topic' => $topic,
                'notebook' => $notebook
            ];
        }, $refresh);

        if ($result === 'nil')
        {
            return null;
        }

        return $result;
    }
}
