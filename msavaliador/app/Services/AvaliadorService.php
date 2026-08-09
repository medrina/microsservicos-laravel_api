<?php
namespace App\Services;

use App\Jobs\SolicitarCartaoJob;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AvaliadorService {

    public function avaliarCreditoMSClientesMSCartoes(string $cpf, int $renda, string $token): ?array {

        // montagem da URL da rota para obter os dados do cliente pelo seu CPF
        $url = 'http://'. request()->server('HTTP_HOST') .'/msclientes/cliente-cpf';
            $cliente = Http::retry(3, 100)
                ->connectTimeout(2)
                ->timeout(5)
                ->withToken($token)
                ->acceptJson()
                ->post($url, ['cpf' => $cpf]);
        
        $cliente = $cliente->json();
        if(isset($cliente['cod'])) throw new RequestException();
        
        if(isset($cliente['error'])) return $cliente;
        else {

            // montagem da URL da rota para obter os dados de acordo com a renda informada pelo cliente
            $url = 'http://'. request()->server('HTTP_HOST') .'/mscartoes/cartoes-faixa-renda';
            try {
                $cartoes = Http::retry(3, 100)
                    ->connectTimeout(2)
                    ->withToken($token)
                    ->acceptJson()
                    ->post($url, ['renda' => $renda]);
            }
                        
            // exceção de falha de conexão com o microsserviço mscartoes
            catch(RequestException $e) {
                return [
                    'message' => 'Falha de conexão com serviço indisponível. Tente novamente mais tarde!'
                ];
            }
            $cartoes = $cartoes->json();
            
            if(isset($cartoes['status']) == 'error') return $cartoes;
            $novaListaCartoes = [];
            $novaListaCartoes = $this->aplicarRegrasCredito($cartoes, $this->calcularIdade($cliente['data_nasc']), $renda);
            $array = [
                'status' => 'success',
                'cliente' => $cliente,
                'cartoes' => $novaListaCartoes
            ];
            return $array;
        }
    }

    public function solicitarCartao(array $dados): mixed {
        $clienteCartao = [
            'id_cliente' => $dados['cliente_id'],
            'id_cartao' => $dados['cartao_id'],
            'renda' => $dados['renda_cliente'],
            'limiteBasico' => $dados['limite_basico']
        ];

        SolicitarCartaoJob::dispatch($clienteCartao)->onConnection('rabbitmq');
        return true;
    }

    // aplicação das regras de crédito sob a renda do cliente em todos os cartões disponíveis que o serviço de cartões retornou pra ele
    private function aplicarRegrasCredito(array $listaCartoes, int $idade, int $renda): array {

        // vai aplicar as regras de crédito em cada cartão
        $limitesBasicos = array_map(function ($cartao) use ($idade, $listaCartoes, $renda) {
                        
            // definir o índice do fatorIdade em relação a idade do cliente 
            $fatorIdade = 0;
            if($idade >= 18 && $idade <= 21) $fatorIdade = 0.6;
            else if($idade > 21 && $idade <= 25) $fatorIdade = 0.8;
            else if($idade > 25 && $idade <= 40) $fatorIdade = 1.0;
            else if($idade > 40 && $idade <= 60) $fatorIdade = 1.1;
            else if($idade > 60) $fatorIdade = 0.9;

            // definir o índice do fatorRenda sobre a renda do cliente
            $fatorRenda = 0;
            if($renda >= 1500 && $renda <= 2000) $fatorRenda = 0.3;
            else if($renda > 2000 && $renda <= 6000) $fatorRenda = 0.4;
            else if($renda > 6000) $fatorRenda = 0.5;
            if($fatorRenda != 0.3) $cartao['limiteBasico'] = $renda - (($renda * $fatorRenda * $fatorIdade) - ($cartao['renda'] * 0.10));
            else $cartao['limiteBasico'] = ($cartao['renda'] * 0.10) + ($renda * $fatorRenda * $fatorIdade);
            return $cartao;
        }, $listaCartoes);
        return $limitesBasicos;
    }

    public function getCardsClientById(int $cliente_id, string $token): ?array {
        $url = 'http://'. request()->server('HTTP_HOST') .'/mscartoes/cartoes/cliente-cartoes';
        $clienteCartoes = Http::retry(3, 100)
            ->connectTimeout(2)
            ->withToken($token)
            ->acceptJson()
            ->post($url, ['id' => $cliente_id]);

        if($clienteCartoes == null) throw new ConnectionException('mscartoes');
        $clienteCartoes = $clienteCartoes->json();
        if(isset($clienteCartoes['message'])) return null;
        $listaCartoes = [];
        $i = 0;
        $url = 'http://'. request()->server('HTTP_HOST') .'/mscartoes/cartao-id';
        foreach($clienteCartoes as $indice => $valor) {
            
            $cartao = Http::retry(3, 100)
                ->connectTimeout(2)
                ->withToken($token)
                ->acceptJson()
                ->post($url, ['id' => $clienteCartoes[$indice]['id_cartao']])
                ->json();
            $listaCartoes[$i] = [
                'id' => $cartao['id'],
                'nome' => $cartao['nome'],
                'bandeira' => $cartao['bandeira'],
                'renda-cartao' => $cartao['renda'],
                'renda-cliente' => $clienteCartoes[$i]['renda'],
                'limite-basico' => $clienteCartoes[$i]['limiteBasico']
            ];
            $i++;
        }
        $url = 'http://'. request()->server('HTTP_HOST') .'/msclientes/cliente-id';
        $cliente = Http::withToken($token)
            ->acceptJson()
            ->post($url, ['id' => $cliente_id]);
        $cliente = $cliente->json();
        if($cliente == null) throw new ConnectionException("msclientes");
        else if(isset($cliente['message']))
            return null;
        return $array = [
            'status' => 'success',
            'cliente' => $cliente,
            'cartoes' => $listaCartoes
        ];
    }

    private function calcularIdade(string $dataNasc): int {
        return Carbon::parse($dataNasc)->age;
    }

}
