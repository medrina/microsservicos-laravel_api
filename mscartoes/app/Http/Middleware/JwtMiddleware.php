<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        try {
            JWTAuth::parseToken()->authenticate();
        }
        catch(Exception $e) {
            return response()->json([
                'message' => 'Acesso Não Autorizado!',
                'error' => $e->getMessage()
                ], 401);
        }
        return $next($request);
    }
}
