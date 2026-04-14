<?php

namespace App\Exceptions;

use Exception;

class RendaClienteMenorRendaCartaoException extends Exception {
    
    protected $message = "Tentativa de solicitação de um cartão cuja a renda do cliente é inferior!";

    public function render() {
        return response()->json([
            'message' => 'quebra de violação das regras de negócio!',
            'error' => $this->getMessage(),
            'code' => 422
        ], 422);
    }
}
