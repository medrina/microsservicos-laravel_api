<?php
namespace App\Services;

use App\Models\Cartao;
use App\Models\ClienteCartao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PDOException;

class CartaoService {

    public function getAllCards(): array {
        $array = array();
        $list = Cartao::all();
        if(!$list) throw new PDOException;
        else {
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
        }
        return $array;
    }

    public function saveCard(array $request): Cartao {
        $card = Cartao::create([
            'nome' => $request['nome'],
            'bandeira' => $request['bandeira'],
            'renda' => $request['renda']
        ]);
        return $card;
    }

    public function getCardById(int $id): Cartao {
        $cardObject = new Cartao();
        $card = Cartao::findOrFail($id);
        if(!$card) throw new ModelNotFoundException;
        else {
            $cardObject->id = $card->id;
            $cardObject->nome = $card->nome;
            $cardObject->bandeira = $card->bandeira;
            $cardObject->renda = $card->renda;
        }
        return $cardObject;
    }

    public function updateCardById(array $request): Cartao {
        $card = Cartao::find($request['id']);
        if(!$card) {
           $cardNotFound = new Cartao();
           $cardNotFound->nome = 'FAIL';
           return $cardNotFound;
        }
        else {
            $card->update([
                'nome' => (isset($request['nome'])) ? $request['nome'] : $card->getAttributes()['nome'],
                'bandeira' => (isset($request['bandeira'])) ? $request['bandeira'] : $card->getAttributes()['bandeira'],
                'renda' => (isset($request['renda'])) ? $request['renda'] : $card->getAttributes()['renda']
            ]);
            return $card;
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

    public function deleteClientCard(int $idClient): bool {
        $clientCard = ClienteCartao::where('id_cliente', $idClient)->delete();
        return ($clientCard) ? true : false;
    }

    public function getCardsByRenda(float $faixaRenda): array {
        $array = array();
        $listCards = Cartao::all()->where('renda', '<=', $faixaRenda);
        if(!$listCards) throw new PDOException;
        else {
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
        }
        return $array;
    }

    public function getClienteCartoes(int $id) {
        $listaClienteCartoes = ClienteCartao::where('id_cliente', $id)->get();
        //dd($listaClienteCartoes[0]->getAttributes());
        /*foreach($listaClienteCartoes as $indice => $valor) {
            echo '<pre>'; print_r($listaClienteCartoes[$indice]->getAttributes()); echo '</pre>';
        }*/
        if(!$listaClienteCartoes) return null;
        else return $listaClienteCartoes;
    }

}