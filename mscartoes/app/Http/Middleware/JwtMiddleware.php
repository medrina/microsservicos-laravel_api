<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware {

    // middleware responsável por verificar as rotas protegidas da API. A verificação ocorre por token JWT
    public function handle(Request $request, Closure $next): Response {
        JWTAuth::parseToken()->authenticate();
        return $next($request);
    }
}
