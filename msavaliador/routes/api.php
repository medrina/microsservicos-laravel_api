<?php

use App\Http\Controllers\AvaliadorController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

# Windows
Route::prefix('msavaliador')->get('/user/msavaliador', function() {
    return 'msavaliador';
});

// criar novo usuário Adm para operar no MSAVALIADORCREDITO
Route::post('/user/register', [LoginController::class, 'register'])->prefix('msavaliador');

// fazer login no MSAVALIADORCREDITO (usuário Adm se loga e recebe um token jwt para poder utilizar as rotas no MSCLIENTES e MSCARTOES)
Route::post('/user/login', [LoginController::class, 'login'])->prefix('msavaliador');

// resetar password (informar name e email de algum usuário Adm que esteja cadastrado no MSAVALIADORCREDITO)
Route::post('/user/reset-password', [LoginController::class, 'resetPassword'])->prefix('msavaliador');

// exige token jwt por esse microsserviço MSAVALIADORCREDITO
Route::prefix('msavaliador')->middleware('jwt')->group(function() {

    // atualizar dados do usuário Adm
    Route::patch('/user/update/{id}', [LoginController::class, 'update']);

    // fazer logout do usuário Adm no MSAVALIADORCREDITO
    Route::post('/user/logout', [LoginController::class, 'logout']);

    // realiza uma avaliação de todos os cartões disponíveis conforme a renda informada pelo cliente
    Route::get('/avaliacao-credito/{renda}', [AvaliadorController::class, 'avaliarCredito']);

    // envia uma solicitação do cartão selecionado junto ao cliente para o MSCARTOES 
    Route::post('/solicitar-cartao', [AvaliadorController::class, 'enviarSolicitacaoCartao']);

    // buscar uma lista de cartões que foram solicitados pelo id do cliente
    Route::get('/cliente-cartao/{id_cliente}', [AvaliadorController::class, 'showCardsbyId']);
 });

 // rota inválida
 Route::fallback(function() {
    return response()->json([
        'message' => 'Rota não encontrada!'
    ], 404);
 });