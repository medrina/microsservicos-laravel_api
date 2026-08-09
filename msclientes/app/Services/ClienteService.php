<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;

class ClienteService {

    public function getAllClients(): array {
        $array = [];
        $list = Cliente::all();
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

    // função para consultar por cpf cliente que já foi apagado da API
    // quando o usuário adm for efetuar algum novo cadastro de um cliente que a API acusar de cpf repetido cadastrado, essa função irá reativar esse cliente 
    public function searchRecovery(string $cpf): ?array {
        
        // selects incluindo clientes que já foram apagados
        $cpfClient = Cliente::withTrashed()
            ->where('cpf', $cpf)
            ->first();
        
        if($cpfClient && $cpfClient->trashed()) { 
            return [
                'status' => 'disabled_client',
                'client' => [
                    'id' => $cpfClient->getAttributes()['id'],
                    'cpf' => $cpfClient->getAttributes()['cpf'],
                    'nome' => $cpfClient->getAttributes()['nome'],
                    'email' => $cpfClient->getAttributes()['email'],
                    'data_inicio' => $cpfClient->getAttributes()['created_at'],
                    'data_fim' => $cpfClient->getAttributes()['deleted_at']
                ]
            ];
        }
        else if($cpfClient) {
            return [
                'status' => 'enabled_client',
                'client' => [
                    'id' => $cpfClient->getAttributes()['id'],
                    'cpf' => $cpfClient->getAttributes()['cpf'],
                    'nome' => $cpfClient->getAttributes()['nome'],
                    'email' => $cpfClient->getAttributes()['email'],
                    'data_inicio' => $cpfClient->getAttributes()['created_at'],
                    'data_fim' => 'active'
                ]
            ];
        }
        else {
            return null;
        }
    }

    public function recoveryClient(int $id): ?array {
        
        // selects incluindo clientes que já foram apagados
        $client = Cliente::withTrashed()
            ->where('id', $id)
            ->first();
        
        if($client && $client->trashed()) {
            $client->restore();
            return [
                'status' => 'reactivated_client',
                'message' => 'Cliente reativado!',
                'client' => [
                    'id' => $client->getAttributes()['id'],
                    'cpf' => $client->getAttributes()['cpf'],
                    'nome' => $client->getAttributes()['nome'],
                    'email' => $client->getAttributes()['email'],
                    'data_inicio' => $client->getAttributes()['created_at']
                ]
            ];
        }
        else if($client) {
            return [
                'status' => 'active_client',
                'message' => 'Cliente encontra-se ativo!',
                'client' => [
                    'id' => $client->getAttributes()['id'],
                    'cpf' => $client->getAttributes()['cpf'],
                    'nome' => $client->getAttributes()['nome'],
                    'email' => $client->getAttributes()['email'],
                    'data_inicio' => $client->getAttributes()['created_at']
                ]
            ];
        }
        else {
            return null;
        }
    }

    public function getClientById(int $id): Cliente {
        $clientObject = new Cliente();
        $client = Cliente::findOrFail($id);
        $clientObject->id = $client->id;
        $clientObject->cpf = $client->cpf;
        $clientObject->nome = $client->nome;
        $clientObject->data_nasc = $client->data_nasc;
        $clientObject->email = $client->email;
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

    public function updateClientById(array $request, $id): array {
        $client = Cliente::find($id);
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

    public function deleteClientById(int $id, string $token): bool {
        $cliente = Cliente::find($id);
        if(!$cliente) return false;
        else {

            // apagar o registro do cliente na tabela cliente no MSCLIENTES
            $cliente->delete();

            // executa a rota do microsserviço mscartoes
            $url = 'http://'. request()->server('HTTP_HOST') .'/mscartoes/cartao/cliente-cartao-id/delete';
            
            // apagar o registro do cliente na tabela cliente_cartaos no MSCARTOES (se houver)
            Http::retry(3, 100)
                ->connectTimeout(2)
                ->withToken($token)
                ->acceptJson()
                ->post($url, ['id' => $id]);

            return true;
        }
    }
}
