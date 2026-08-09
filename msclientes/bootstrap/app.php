<?php

use Illuminate\Http\Request;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Database\QueryException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\UniqueConstraintViolationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // inclusão do middleware jwt
        $middleware->alias([
            'jwt' => JwtMiddleware::class
        ]);

        // publicação do middleware de checagem de tokens na lista negra no Redis
        $middleware->api(append: [
            \App\Http\Middleware\CheckJwtBlacklist::class
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // garante que o Laravel retorne um JSON para qualquer requisição do microsserviço msclientes (content-type: application/json)
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('msclientes/*');
        });

        $exceptions->render(function (UniqueConstraintViolationException $e) {
            
            // Retorna uma resposta amigável e segura para o cliente
            return response()->json([
                'status' => 'error',
                'message' => 'Esse email já foi cadastrado!'
            ], 409);
        });

        // customiza a exceção de falha/interrupção do container/serviço de banco de dados
        $exceptions->render(function (QueryException $e) {
            
            // Retorna uma resposta amigável e segura para o cliente
            return response()->json([
                'status' => 'error_clients',
                'message' => 'Serviço temporariamente indisponível. Tente novamente em instantes.'
            ], 503);
        });

        $exceptions->render(function (TokenExpiredException $e) {
        
            // Retorna uma resposta amigável e segura para o cliente
            return response()->json([
                'status' => 'error',
                'message' => 'Acesso não autorizado/token expirado!!!'
            ], 401);
        });

        $exceptions->render(function (JWTException $e) {
        
            // Retorna uma resposta amigável e segura para o cliente
            return response()->json([
                'status' => 'error',
                'message' => 'Token de formato inválido!!!'
            ], 500);
        });

        // exceção de cliente não encontrado
        $exceptions->render(function (NotFoundHttpException $e) {
        
            // Retorna uma resposta amigável e segura para o cliente
            return response()->json([
                'status' => 'not_found',
                'message' => 'Cliente não encontrado!'
            ], 404);
        });

        // exceção de falha no serviço do Redis
        $exceptions->render(function (\RedisException $e) {
            return response()->json([
                'status' => 'error_cache',
                'message' => 'Serviço de cache de tokens temporariamente indisponível! Tente novamente em instantes!'
            ], 503);
        });
    
    })->create();
