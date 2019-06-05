<?php /** @noinspection ALL */

/** @noinspection PhpUndefinedNamespaceInspection */


namespace App\Services;

use App\Http\Modules\RichContentService;
use App\Http\Transformers\User\UserItemResource;
use App\Models\Message;
use App\User;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
/**
 * @see https://wiki.swoole.com/wiki/page/400.html
 */
class WebSocketService implements WebSocketHandlerInterface
{
    // 声明没有参数的构造函数
    public function __construct()
    {
        // fd、reactorId：https://wiki.swoole.com/wiki/page/56.html
        // 每次 server 重启后都会因为 fd 被重置导致用户掉线，需要客户端重连
    }

    // 在触发onOpen事件之前Laravel的生命周期已经完结，所以Laravel的Request是可读的
    public function onOpen(Server $server, Request $request)
    {
        $token = $request->get['token'];
        if (!$token)
        {
            return;
        }

        $userSlug = User
            ::where('api_token', $request->get['token'])
            ->pluck('slug')
            ->first();
        if (!$userSlug)
        {
            return;
        }

        // 记录这个 table 是为在 onMessage 的时候让别人找到当前用户的 fd
        app('swoole')
            ->wsTable
            ->set('uid:' . $userSlug, ['value' => $request->fd]);
        // 记录这个 table 是为了在 onClose 的时候找到当前用户的 uid
        app('swoole')
            ->wsTable
            ->set('fd:' . $request->fd, ['value' => $userSlug]);

        // TODO：返回用户的未读消息和未读通知的数量
        $server->push($request->fd, json_encode([
            'hola' => 'Welcome to calibur.tv'
        ]));
    }

    public function onMessage(Server $server, Frame $frame)
    {
        // $frame：https://wiki.swoole.com/wiki/page/987.html
        // 信息发送者的 fd 用 frame->fd 可以知道
        // 但是接收者的 fd 不知道，只知道接受者的 uid
        // 要根据接受者 uid 找到他的 fd
        // onMessage 好像拿不到 request，所以需要把 token 带到 frame->data 里
        $data = json_decode($frame->data, true);
        $validator = Validator::make($data, [
            'message_type' => [
                'required',
                Rule::in([0, 1, 2]),
            ],
            'from_user_token' => 'required|string',
            'to_user_slug' => 'present|string',
            'content' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return;
        }

        $fromUser = User
            ::where('api_token', $data['from_user_token'])
            ->first();
        if (is_null($fromUser))
        {
            return;
        }

        $messageType = $data['message_type'];
        /**
         *  type 消息种类判定
         * 0. 私聊
         * 1. 群发
         * 2. 广播
         */

        $fromUserSlug = $fromUser->slug;
        $toUserSlug = $data['to_user_slug'];
//        if ($messageType === 0 && $fromUserSlug === $toUserSlug)
//        {
//            return;
//        }

        // XSS过滤，敏感词查询
        // 关系认证，是否可发送消息
        // 消息入库，如何做缓存？
        $message = Message::createMessage([
            'from_user_slug' => $fromUserSlug,
            'to_user_slug' => $toUserSlug,
            'type' => $messageType
        ], $data['content']);

        $targetFd = app('swoole')
            ->wsTable
            ->get('uid:' . $toUserSlug);
        if ($targetFd === false)
        {
            return;
        }

        $richContentService = new RichContentService();
        $result = [
            'message_type' => $messageType,
            'from_user' => [
                'slug' => $fromUserSlug,
                'nickname' => $fromUser->nickname,
                'avatar' => $fromUser->avatar
            ],
            'message' => $richContentService->parseRichContent($message->content->text)
        ];

        $server->push($targetFd['value'], json_encode($result));
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        // 1. 通过当前 fd 找到这个用户的 uid
        // 2. 如果 uid 存在，就删除 uid 的 table，这样就不会再接收 message
        // 3. 删除当前用户的 fd，释放内存，并且该 fd 讲会被其他人复用
        $uid = app('swoole')->wsTable->get('fd:' . $fd);
        if ($uid !== false)
        {
            app('swoole')
                ->wsTable
                ->del('uid:' . $uid['value']);
        }
        app('swoole')
            ->wsTable
            ->del('fd:' . $fd);
    }

    /* https://wiki.swoole.com/wiki/page/397.html
    // 但是 laravel-s 好像没有继承这个方法
    public function onRequest(Request $request, Response $response)
    {

    }
    */
}
