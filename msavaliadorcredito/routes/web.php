<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'rota inválida!'
    ], 404);
});

Route::fallback(function() {
    return response()->json([
        'message' => 'rota inválida!'
    ], 404);
});
