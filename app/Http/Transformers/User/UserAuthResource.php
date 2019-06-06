<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-04-15
 * Time: 08:53
 */

namespace App\Http\Transformers\User;

use App\Http\Modules\DailyRecord\UserDailySign;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthResource extends JsonResource
{
    public function toArray($request)
    {
        $userDailySign = new UserDailySign();

        return [
            'slug' => $this->slug,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'birthday' => $this->birthday,
            'birth_secret' => $this->birth_secret,
            'sex' => $this->sex,
            'sex_secret' => $this->sex_secret,
            'signature' => $this->signature,
            'roles' => $this->getRoleNames(),
            'daily_signed' => $userDailySign->sign($this->id),
            'providers' => [
                'bind_qq' => !!$this->qq_unique_id,
                'bind_wechat' => !!$this->wechat_unique_id,
                'bind_phone' => !!$this->phone
            ],
            'level' => $this->level,
        ];
    }
}