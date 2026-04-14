<?php

use App\Http\Controllers\CartaoController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->group(function() {

    // lista de todos os cartões de crédito cadastrados no microsserviço mscartoes
    Route::get('/cartoes', [CartaoController::class, 'index']);

    // retorna um cartão pelo id
    Route::post('/cartao-id', [CartaoController::class, 'show']);

    // cadastrar novo cartão
    Route::post('/cartao', [CartaoController::class, 'store']);

    // atualizar cartão pelo id
    Route::put('/cartao', [CartaoController::class, 'update']);

    // apagar cartão pelo id do cartão
    Route::delete('/cartao', [CartaoController::class, 'destroy']);

    // apagar cartão vinculado ao cliente pelo id do cliente (MSCLIENTES)
    Route::post('/cartao/cliente-cartao-id/delete', [CartaoController::class, 'deleteCardByClientId']);

    // avaliar cartões pelo msavaliadorcredito (MSAVALIADOR)
    Route::post('/cartoes-faixa-renda', [CartaoController::class, 'getCartoes']);

    // buscar registros de solicitações de todos os cartões vinculados pelo id do cliente (MSAVALIADOR)
    Route::post('/cartoes/cliente-cartoes', [CartaoController::class, 'getCartoesByCliente']);
});

Route::fallback(function() {
    return response()->json([
        'message' => 'rota inválida!'
    ], 404);
});