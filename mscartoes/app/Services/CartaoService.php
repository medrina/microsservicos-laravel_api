<?php
namespace App\Services;

use App\Models\Cartao;
use App\Models\ClienteCartao;
use Illuminate\Database\Eloquent\Collection;

class CartaoService {

    public function getAllCards(): array {
        $array = array();
        $list = Cartao::all();
            $i = 0;
            foreach($list as $card) {
                $array[$i] = [
                    'id' => $card->id,
                    'nome' => $card->nome,
                    'bandeira' => $card->bandeira,
                    'renda' => $card->renda
                ];
                $i++;
            }
        return $array;
    }

    public function saveCard(array $request): array {
        $card = Cartao::create([
            'nome' => $request['nome'],
            'bandeira' => $request['bandeira'],
            'renda' => $request['renda']
        ]);
        return [
            'id' => $card->id,
            'nome' => $card->nome,
            'bandeira' => $card->bandeira,
            'renda' => $card->renda
        ];
    }

    public function getCardById(int $id): Cartao {
        $cardObject = new Cartao();
        $card = Cartao::findOrFail($id);
            $cardObject->id = $card->id;
            $cardObject->nome = $card->nome;
            $cardObject->bandeira = $card->bandeira;
            $cardObject->renda = $card->renda;
        return $cardObject;
    }

    public function updateCardById(array $request, $id): array {
        $card = Cartao::find($id);
        if(!$card) {
            return [
                'nome' => 'FAIL'
            ];
        }
        else {
            $card->update([
                'nome' => (isset($request['nome'])) ? $request['nome'] : $card->getAttributes()['nome'],
                'bandeira' => (isset($request['bandeira'])) ? $request['bandeira'] : $card->getAttributes()['bandeira'],
                'renda' => (isset($request['renda'])) ? $request['renda'] : $card->getAttributes()['renda']
            ]);
            return [
                'id' => $card->getAttributes()['id'],
                'nome' => $card->getAttributes()['nome'],
                'bandeira' => $card->getAttributes()['bandeira'],
                'renda' => $card->getAttributes()['renda']
            ];
        }
    }

    public function deleteCardById(int $id): array {
        $card = Cartao::find($id);
        if(!$card) return [];
        else {

            // apagar o cartão na tabela cartaos
            $card->delete();

            // apagar o cartão vinculado ao cliente na tabela cliente_cartaos (se houver registros)
            $clienteCartao = ClienteCartao::where('id_cartao', $id)->delete();
            
            return [
                'cartao' => 'success card',
                'cliente_cartao' => ($clienteCartao) ? 'success cliente_cartao' : 'not found cliente_cartao'
            ];
        }
    }

    public function deleteClientCard(int $idClient): void {
        $clientCard = ClienteCartao::where('id_cliente', $idClient)->delete();
    }

    public function getCardsByRenda(float $faixaRenda): array {
        $array = array();
        $listCards = Cartao::all()->where('renda', '<=', $faixaRenda);
            $i = 0;
            foreach($listCards as $card) {
                $array[$i] = [
                    'id' => $card->id,
                    'nome' => $card->nome,
                    'bandeira' => $card->bandeira,
                    'renda' => $card->renda
                ];
                $i++;
            }
        return $array;
    }

    public function getClienteCartoes(int $id) {
        $listaClienteCartoes = ClienteCartao::where('id_cliente', $id)->get();
        if(!$listaClienteCartoes) return null;
        else return $listaClienteCartoes;
    }

}