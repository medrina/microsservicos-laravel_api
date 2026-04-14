<?php

namespace App\Jobs;

use App\Models\ClienteCartao;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PDOException;

class SolicitarCartaoJob implements ShouldQueue {
    use Queueable;
    
    public function __construct(public array $data) {
        //
    }

    // recebe os dados do msavaliadorcredito via fila de mensageria pelo RabbitMQ
    public function handle(): void {
        
        // cria uma nova solicitação de cartão de crédito na tabela cliente_cartao
        try {
            ClienteCartao::create([
                'id_cliente' => $this->data['id_cliente'],
                'id_cartao' => $this->data['id_cartao'],
                'renda' => $this->data['renda'],
                'limiteBasico' => $this->data['limiteBasico']
            ]);
        }
        catch(PDOException $e) {
            echo 'error: Não foi possível realizar o cadastro dessa solicitação!\nmessage: '. $e->getMessage();
        }
    }
}
