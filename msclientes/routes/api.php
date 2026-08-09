<?php

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::prefix('msclientes')->middleware('jwt')->group(function() {

        // retorna lista de todos os clientes que estão cadastrados
        Route::get('/clientes', [ClienteController::class, 'index']);

        // retorna cliente
        Route::get('/clientes/{id}', [ClienteController::class, 'getClienteByGet'])->whereNumber('id');

        // cadastra novo cliente
        Route::post('/clientes', [ClienteController::class, 'store']);

        // atualizar algum cadastro de cliente pelo seu id
        Route::put('/clientes/{id}', [ClienteController::class, 'update']);

        // retorna cliente pelo seu id (acessado pelo microsserviço msavaliador)
        Route::post('/cliente-id', [ClienteController::class, 'getCliente']);

        // retorna cliente pelo seu cpf
        Route::get('/cliente', [ClienteController::class, 'getClienteByCPF_GET']);

        // retorna cliente pelo seu cpf (acessado pelo microsserviço msavaliador)
        Route::post('/cliente-cpf', [ClienteController::class, 'getClienteByCPF']);

        Route::delete('/clientes/{id}', [ClienteController::class, 'delete']);

        // pesquisar por status de cliente pelo seu cpf (se o cliente foi apagado ou não)
        Route::get('/clientes/search-recovery', [ClienteController::class, 'searchRecoveryClient']);

        // reativar cliente apagado
        Route::post('/clientes/recovery', [ClienteController::class, 'recoveryClient']);
});

// rota inválida
Route::fallback(function() {
        return response()->json([
                'status' => 'not_found',
                'message' => 'rota inválida!'
        ], 404);
});
