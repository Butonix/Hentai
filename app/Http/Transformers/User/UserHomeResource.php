<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserHomeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'slug' => $this->slug,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'signature' => $this->signature,
            'title' => $this->title,
            'level' => $this->level,
            'sex' => $this->sex_secret ? -1 : $this->sex,
            'birthday' => $this->birth_secret ? -1 : $this->birthday,
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
            'is_following' => false,
            'is_followed_by' => false,
            'visit_count' => $this->visit_count,
            'balance' => [
                'coin' => (float)$this->virtual_coin,
                'money' => (float)$this->money_coin,
            ],
            'daily_signed' => false,
            'sign' => [
                'continuous_sign_count' => $this->continuous_sign_count,
                'total_sign_count' => $this->total_sign_count,
                'latest_signed_at' => $this->latest_signed_at,
            ],
            'stat' => [
                'activity' => $this->activity_stat,
                'exposure' => $this->exposure_stat,
            ]
        ];
    }
}
