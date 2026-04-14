<?php

namespace App\Http\Controllers;

use App\Exceptions\RendaClienteMenorRendaCartaoException;
use App\Services\AvaliadorService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use TypeError;

class AvaliadorController extends Controller {
    
    private $avaliadorService;

    public function __construct(AvaliadorService $avaliadorService) {
        $this->avaliadorService = $avaliadorService;
    }

    // POST - JSON: cpf, renda
    public function avaliarCredito(Request $request) {
        $request->validate([
            'cpf' => 'required|min:11|max:11',
            'renda' => 'required|numeric'
        ],
        [
            'required' => 'Preencha os campos obrigatórios!',
            'cpf.min' => 'Preenchimento obrigatório do CPF de 11 dígitos!',
            'cpf.max' => 'Preenchimento obrigatório do CPF de 11 dígitos!',
            'renda.numeric' => 'A renda a ser informada precisa ser no formato numérico!'
        ]);

        try {
            $situacaoCliente = $this->avaliadorService->avaliarCreditoMSClientesMSCartoes($request->cpf, $request->renda, $request->bearerToken());
            return (!isset($situacaoCliente['error'])) ? response()->json($situacaoCliente, 200) : response()->json($situacaoCliente, 404);
        }
        catch(ConnectionException $e) {
            return response()->json([
                'error' => 'Erro! Falha de conexão com outros serviços. Tente mais tarde!',
                'message' => $e->getMessage()
            ], 500);
        }
        /*catch(TypeError $e) {
            return response()->json([
                'message' => 'Erro! Esse método precisa do token do msclientes!',
                'error' => $e->getMessage()
            ], 500);
        }*/
    }

    // POST - JSON: cliente_id, cartao_id, renda
    // RabbitMQ
    public function enviarSolicitacaoCartao(Request $request) {
        $request->validate([
            'cliente_id' => 'required|numeric',
            'cartao_id' => 'required|numeric',
            'renda' => 'required|numeric'
        ],
        [
            'required' => 'Preencha todos os campos!',
            'cliente_id.numeric' => 'O cliente_id precisa ser um id do tipo numérico!',
            'cartao_id.numeric' => 'O cartao_id precisa ser um id do tipo numérico!',
            'renda.numeric' => 'A renda do cliente precisa ser do tipo numérico!'
        ]);
        try {
            $solicitarCartao = $this->avaliadorService->solicitarCartao($request->all(), $request->bearerToken());
            return (!isset($solicitarCartao['error'])) ? response()->json(['message' => 'Sua solicitação de cartão passará por uma breve análise!'], 202) : response()->json($solicitarCartao, 500);
        }
        catch(ConnectionException $e) {
            return response()->json([
                'error' => 'Erro! Falha de conexão com outros serviços. Tente mais tarde!',
                'message' => $e->getMessage()
            ], 500);
        }
        catch(RendaClienteMenorRendaCartaoException $e) {
            return $e->render();
        }
    }

    // recuperar todos os cartões que foram solicitados pelo cpf do cliente
    public function showCardsbyId(Request $request) {
        $request->validate([
            'cliente_id' => 'required|numeric'
        ],
        [
            'required' => 'Informe o id!',
            'cliente_id.numeric' => 'O id precisa ser do formato numérico!'
        ]);
        try {
            $clienteCartoes = $this->avaliadorService->getCardsClientById($request->cliente_id, $request->bearerToken());
            return ($clienteCartoes) ? response()->json($clienteCartoes, 200) : response()->json(['message' => 'Cliente não encontrado!'], 404);
        }
        catch(TypeError $e) {
            return response()->json([
                'message' => 'Não autorizado! Essa operação precisa do token do msclientes!',
                'error' => $e->getMessage()
            ]);
        }
    }

}
