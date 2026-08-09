<?php

use App\Http\Controllers\CartaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('mscartoes')->middleware('jwt')->group(function() {

    // lista de todos os cartões de crédito cadastrados
    Route::get('/cartoes', [CartaoController::class, 'index']);

    // retorna um cartão pelo id (acessado pelo microsserviço msavaliador)
    Route::post('/cartao-id', [CartaoController::class, 'show']);
    
    // retorna um cartão pelo id
    Route::get('/cartoes/{id}', [CartaoController::class, 'showGet']);

    // cadastrar novo cartão
    Route::post('/cartoes', [CartaoController::class, 'store']);

    // atualizar cartão pelo id
    Route::patch('/cartoes/{id}', [CartaoController::class, 'update']);

    // apagar cartão vinculado ao cliente pelo id do cliente (acessado pelo microsserviço msclientes)
    Route::post('/cartao/cliente-cartao-id/delete', [CartaoController::class, 'deleteCardByClientId']);

    // avaliar cartões pelo msavaliadorcredito (acessado pelo microsserviço msavaliador)
    Route::post('/cartoes-faixa-renda', [CartaoController::class, 'getCartoes']);

    // buscar registros de solicitações de todos os cartões vinculados pelo id do cliente (acessado pelo microsserviço msavaliador)
    Route::post('/cartoes/cliente-cartoes', [CartaoController::class, 'getCartoesByCliente']);
});

// rota inválida
Route::fallback(function() {
    return response()->json([
        'message' => 'rota inválida!'
    ], 404);
});
