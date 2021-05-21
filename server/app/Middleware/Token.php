<?php
declare(strict_types=1);

namespace App\Middleware;

use Closure;
use Minimal\Contracts\Middleware;
use Minimal\Foundation\Request;
use Minimal\Foundation\Response;
use Minimal\Foundation\Exception;
use App\Common\Token as TokenCommon;

/**
 * 令牌中间件
 */
class Token implements Middleware
{
    /**
     * 处理程序
     */
    public function handle(Request $req, Response $res, Closure $next) : mixed
    {
        if (empty($req->header('authorization')) || !str_starts_with($req->header('authorization'), 'Token ')) {
            throw new Exception('很抱歉、请登录后再操作！', 403);
        }

        $token = substr($req->header('authorization'), 6);
        if (!TokenCommon::has($token)) {
            throw new Exception('很抱歉、登录超时请重新登录！', 403, $req->header());
        }

        $req->uid = TokenCommon::get($token);

        return $next($req);
    }
}