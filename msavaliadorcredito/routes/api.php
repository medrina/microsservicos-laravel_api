<?php

use App\Http\Controllers\AvaliadorController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// criar novo usuário Adm para operar no MSAVALIADORCREDITO
Route::post('/avaliador/user/register', [LoginController::class, 'register']);

// fazer login no MSAVALIADORCREDITO (usuário Adm se loga e recebe um token jwt para poder utilizar as rotas no MSCLIENTES e MSCARTOES)
Route::post('/avaliador/user/login', [LoginController::class, 'login']);

// resetar password (informar name e email de algum usuário Adm que esteja cadastrado no MSAVALIADORCREDITO)
Route::post('/avaliador/user/reset-password', [LoginController::class, 'resetPassword']);

// exige token jwt por esse microsserviço MSAVALIADORCREDITO
Route::middleware('jwt')->group(function() {

    // atualizar dados do usuário Adm
    Route::put('/avaliador/user/update', [LoginController::class, 'update']);

    // fazer logout do usuário Adm no MSAVALIADORCREDITO
    Route::post('/avaliador/user/logout', [LoginController::class, 'logout']);

    // deletar algum usuário Adm no MSAVALADORCREDITO
    Route::delete('/avaliador/user/delete', [LoginController::class, 'delete']);

    // realiza uma avaliação de todos os cartões disponíveis conforme a renda informada pelo cliente
    Route::post('/avaliador/avaliacao-credito', [AvaliadorController::class, 'avaliarCredito']);

    // envia uma solicitação do cartão selecionado junto ao cliente para o MSCARTOES 
    Route::post('/avaliador/solicitar-cartao', [AvaliadorController::class, 'enviarSolicitacaoCartao']);

    // buscar uma lista de cartões que foram solicitados pelo id do cliente
    Route::post('/avaliador/cliente-cartao', [AvaliadorController::class, 'showCardsbyId']);
 });

 Route::fallback(function() {
    return response()->json([
        'message' => 'Rota não encontrada!'
    ], 404);
 });