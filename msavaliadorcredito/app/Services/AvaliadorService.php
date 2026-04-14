<?php
namespace App\Services;

use App\Exceptions\RendaClienteMenorRendaCartaoException;
use App\Jobs\SolicitarCartaoJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class AvaliadorService {

    public function avaliarCreditoMSClientesMSCartoes(string $cpf, int $renda, string $token): ?array {
        $cliente = Http::withToken($token)
            ->acceptJson()
            ->post('http://localhost:8001/api/cliente-cpf', ['cpf' => $cpf]);
        $cliente = $cliente->json();
        if(isset($cliente['error'])) return $cliente;
        else {
            $cartoes = Http::withToken($token)
                ->acceptJson()
                ->post('http://localhost:8002/api/cartoes-faixa-renda', ['renda' => $renda]);
            $cartoes = $cartoes->json();
            if(isset($cartoes['error'])) return $cartoes;
            $novaListaCartoes = [];
            $novaListaCartoes = $this->aplicarRegrasCredito($cartoes, $this->calcularIdade($cliente['data_nasc']), $renda);
            $array = [
                'cliente' => $cliente,
                'cartoes' => $novaListaCartoes
            ];
            return $array;
        }
    }

    public function solicitarCartao(array $dados, string $token) {
        
        // recupera o cliente para pegar a sua idade
        $cliente = Http::withToken($token)
            ->acceptJson()
            ->post('http://localhost:8001/api/cliente-id', ['id' => $dados['cliente_id']]);
        $cliente = $cliente->json();
        if(isset($cliente['error'])) return $cliente;
        else {
            $cartao = Http::withToken($token)
                ->acceptJson()
                ->post('http://localhost:8002/api/cartao-id', ['id' => $dados['cartao_id']]);
            $cartao = $cartao->json();
            if(isset($cartao['error'])) return $cartao;
            if($dados['renda'] < $cartao['renda'])
                throw new RendaClienteMenorRendaCartaoException();
            
            // definir o índice do fatorIdade em relação a idade do cliente 
            $idadeCliente = $this->calcularIdade($cliente['data_nasc']);
            $fatorIdade = 0;
            if($idadeCliente >= 18 && $idadeCliente <= 21) $fatorIdade = 0.6;
            else if($idadeCliente > 21 && $idadeCliente <= 25) $fatorIdade = 0.8;
            else if($idadeCliente > 25 && $idadeCliente <= 40) $fatorIdade = 1.0;
            else if($idadeCliente > 40 && $idadeCliente <= 60) $fatorIdade = 1.1;
            else if($idadeCliente > 60) $fatorIdade = 0.9;

            // definir o índice do fatorRenda sobre a renda do cliente
            $fatorRenda = 0;
            if($dados['renda'] >= 1500 && $dados['renda'] <= 2000) $fatorRenda = 0.3;
            else if($dados['renda'] > 2000 && $dados['renda'] <= 6000) $fatorRenda = 0.4;
            else if($dados['renda'] > 6000) $fatorRenda = 0.5;
            if($fatorRenda != 0.3) $cartao['limiteBasico'] = $dados['renda'] - (($dados['renda'] * $fatorRenda * $fatorIdade) - ($cartao['renda'] * 0.10));
            else $cartao['limiteBasico'] = ($cartao['renda'] * 0.10) + ($dados['renda'] * $fatorRenda * $fatorIdade);
            $clienteCartao = [
                'id_cliente' => $cliente['id'],
                'id_cartao' => $cartao['id'],
                'renda' => $dados['renda'],
                'limiteBasico' => $cartao['limiteBasico']
            ];

            // enviar a solicitação dos dados desse cartão à fila do Laravel
            try {
                SolicitarCartaoJob::dispatch($clienteCartao)->onConnection('rabbitmq');
                return true;
            }
            catch(Exception $e) {
                $erro = [];
                return $erro = [
                    'error' => 'Erro de comunicação!',
                    'message' => $e->getMessage()
                ];
            }
        }
    }

    // aplicação das regras de crédito sob a renda do cliente em todos os cartões disponíveis que o serviço de cartões retornou pra ele
    private function aplicarRegrasCredito(array $listaCartoes, int $idade, int $renda) {

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
            
            // 
            if($fatorRenda != 0.3) $cartao['limiteBasico'] = $renda - (($renda * $fatorRenda * $fatorIdade) - ($cartao['renda'] * 0.10));
            else $cartao['limiteBasico'] = ($cartao['renda'] * 0.10) + ($renda * $fatorRenda * $fatorIdade);
            return $cartao;
        }, $listaCartoes);
        return $limitesBasicos;
    }

    public function getCardsClientById(int $cliente_id, string $token) {
        $clienteCartoes = Http::withToken($token)
            ->acceptJson()
            ->post('http://localhost:8002/api/cartoes/cliente-cartoes', ['id' => $cliente_id]);
        $clienteCartoes = $clienteCartoes->json();
        if(isset($clienteCartoes['message']))
            return null;
        $listaCartoes = [];
        $i = 0;
        foreach($clienteCartoes as $indice => $valor) {
            $cartao = Http::withToken($token)
                ->acceptJson()
                ->post('http://localhost:8002/api/cartao-id', ['id' => $clienteCartoes[$indice]['id_cartao']])
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
        $cliente = Http::withToken($token)
            ->acceptJson()
            ->post('http://localhost:8001/api/cliente-id', ['id' => $cliente_id]);
        $cliente = $cliente->json();
        if(isset($cliente['message']))
            return null;
        return $array = [
            'cliente' => $cliente,
            'cartoes' => $listaCartoes
        ];

        // OK
        // retorna os dados do cartão do cliente do mscartoes (precisa ver a estrutura do json retornado da tabela cliente_cartoes)
        // post 'http://localhost:8002/api/cartao-id', ['id' => $id]
        /*$cartao = Http::withToken($token)
            ->acceptJson()
            ->post('http://localhost:8002/api/cartao-id', ['id' => 3]);
        $cartao = $cartao->json();*/
        //dd($cartao);
    }

    private function calcularIdade(string $dataNasc): int {
        return Carbon::parse($dataNasc)->age;
    }

}

//----------------------------------------------------------------------------------
/* regras de negócio
idade:
18-21 anos:
- limites menores ou cartões básicos
22-35 anos:
- chances maiores de aprovação
35-60 anos:
- podem receber limites maiores
60 anos >
- avaliação de renda fixa e aposentadoria
-----------------------------------
renda mensal:
muitos bancos tentam manter o limite entre 20% e 40% da renda mensal
Renda mensal      Limite inicial comum
R$ 1.500,00          R$ 300,00    |  R$ 800,00
R$ 3.000,00          R$ 1.000,00  |  R$ 3000,00
R$ 5.000,00          R$ 2.000,00  |  R$ 6.000,00
R$ 10.000,00+        R$ 5.000,00

GEPETO: Fórmula básica simplificada

Limite = renda x fator_de_renda x fator_de_idade

fator_de_renda: muitos bancos trabalham entre 20% a 40% da renda mensal

  Perfil            fator_de_renda
conservador             0,20
médio                   0,30
agressivo               0,40
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
fator_de_idade: a idade pode ajustar o risco

Idade       fator_de_idade
18-21           0,6
22-25           0,8
26-40           1,0
41-60           1,1
 60+            0,9

Exemplo prático:
Pessoa com:
- renda: R$ 3.000,00
- idade: 24 anos

fator_de_renda:
- médio = 0,30

fator_de_idade:
- 0,8

Cálculo:
Limite = 3000 x 0,30 x 0,8
Limite = R$ 720,00
-------------------------------------------------------------------------------
Muitos bancos usam um pouco mais realista (eles incluem o score do Serasa)

Limite = renda x fator_de_renda x fator_de_idade x fator_de_score_serasa

Score           fator_de_score_serasa
0-300                   0,4
301-500                 0,7
501-700                 1,0
701-1000                1,3

*/