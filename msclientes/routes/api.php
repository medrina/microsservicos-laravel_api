<?php

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function() {

        // retorna lista de todos os clientes que estão cadastrados
        Route::get('/clientes', [ClienteController::class, 'index']);

        // cadastra novo cliente
        Route::post('/cliente', [ClienteController::class, 'store']);

        // atualizar algum cadastro de cliente pelo seu id
        Route::put('/cliente', [ClienteController::class, 'update']);

        // retorna cliente pelo seu id (AVALIADOR)
        Route::post('/cliente-id', [ClienteController::class, 'getCliente']);

        // retorna cliente pelo seu cpf (AVALIADOR)
        Route::post('/cliente-cpf', [ClienteController::class, 'getClienteByCPF']);

        // apagar algum cliente pelo seu id   (MSCARTOES => Route::post('/cartao/cliente-cartao-id/delete', [CartaoController::class, 'deleteCardByClientId']);)
        Route::delete('/cliente', [ClienteController::class, 'delete']);
});

Route::fallback(function() {
        return response()->json([
                'message' => 'rota inválida!'
        ], 404);
});