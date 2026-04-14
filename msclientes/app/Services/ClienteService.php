<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use PDOException;

class ClienteService {

    public function getAllClients(): array {
        $array = [];
        $list = Cliente::all();
        if(!$list) throw new \PDOException;
        else {
            $i = 0;
            foreach($list as $client) {
                $array[$i] = [
                    'id' => $client->id,
                    'cpf' => $client->cpf,
                    'nome' => $client->nome,
                    'data_nasc' => $client->data_nasc,
                    'email' => $client->email
                ];
                $i++;
            }
        }
        return $array;
    }

    public function saveNewUser(array $request): array {
        $cliente = Cliente::create([
            'cpf' => $request['cpf'],
            'nome' => $request['nome'],
            'data_nasc' => $request['data_nasc'],
            'email' => $request['email']
        ]);
        return [
            'id' => $cliente->id,
            'cpf' => $cliente->cpf,
            'nome' => $cliente->nome,
            'data_nasc' => $cliente->data_nasc,
            'email' => $cliente->email
        ];
    }

    public function getClientById(int $id): Cliente {
        $clientObject = new Cliente();
        $client = Cliente::findOrFail($id);
        if(!$client) throw new ModelNotFoundException;
        else {
            $clientObject->id = $client->id;
            $clientObject->cpf = $client->cpf;
            $clientObject->nome = $client->nome;
            $clientObject->data_nasc = $client->data_nasc;
            $clientObject->email = $client->email;
        }
        return $clientObject;
    }

    public function getClientByCPF(int $cpf): Cliente {
        $clientObject = new Cliente();
        $client = Cliente::all()->where('cpf', '==', $cpf)->first();
        if(!$client) throw new ModelNotFoundException;
        else {
            $clientObject->id = $client->id;
            $clientObject->cpf = $client->cpf;
            $clientObject->nome = $client->nome;
            $clientObject->data_nasc = $client->data_nasc;
            $clientObject->email = $client->email;
        }
        return $clientObject;
    }

    public function updateClientById(array $request): array {
        $client = Cliente::find($request['id']);
        if(!$client) return [];
        else {
            $client->update([
                'email' => (isset($request['email'])) ? $request['email'] : $client->email
            ]);
            return [
                'id' => $client->id,
                'cpf' => $client->cpf,
                'nome' => $client->nome,
                'data_nasc' => $client->data_nasc,
                'email' => $client->email
            ];
        }
    }

    public function deleteClientById_old(int $id): bool {
        $client = Cliente::find($id);
        if(!$client) return false;
        else {
            $client->delete();
            return true;
        }
    }

    public function deleteClientById(int $id, string $token): array {
        $cliente = Cliente::find($id);
        if(!$cliente) return [];
        else {

            // apagar o registro do cliente na tabela cliente_cartaos no MSCARTOES (se houver)
            $clienteCartao = Http::withToken($token)
                ->acceptJson()
                ->post('http://localhost:8002/api/cartao/cliente-cartao-id/delete', ['id' => $id]);
            $clienteCartao = $clienteCartao->json();
            
            // apagar o registro do cliente na tabela cliente no MSCLIENTES
            $cliente->delete();

            // retornar a situação dos clientes que foram apagados
            return [
                'cliente' => 'success client',
                'cliente_cartao' => ($clienteCartao) ? 'success cliente_cartao' : 'not found cliente_cartao'
            ];
        }
    }
}
