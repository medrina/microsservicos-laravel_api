<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class CheckJwtBlacklist {

    public function handle(Request $request, Closure $next): Response {

        // obtém o token contido na requisição
        $token = $request->bearerToken();
        if($token) {
            $redisKey = 'jwt_blacklist'. md5($token);

            // se o token existir na lista negra do Redis, barra a requisição 
            if(Redis::exists($redisKey)) {
                throw new TokenExpiredException();
            }
        }
        return $next($request);
    }
}
