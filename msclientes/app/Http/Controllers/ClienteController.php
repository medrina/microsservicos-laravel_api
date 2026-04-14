<?php

namespace App\Http\Controllers;

use App\Services\ClienteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use PDOException;

class ClienteController extends Controller {

    // objeto de serviço de operações no banco
    private $clienteService;

    public function __construct(ClienteService $clienteService) {

        // injeção de dependência do objeto de serviço
        $this->clienteService = $clienteService;
    }

    // retorna todos os clientes cadastrados na tabela clientes
    public function index() {
        try {
            $listClients = $this->clienteService->getAllClients();
            return response()->json($listClients, 200);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Não foi possível realizar essa consulta no banco de dados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // método que vai salvar novo usuário
    public function store(Request $request) {
        $request->validate([
            'cpf' => 'required|min:11|max:11',
            'nome' => 'required|min:3|max:100',
            'data_nasc' => 'required|date',
            'email' => 'required|email|string|max:100'
        ],
        [
            'required' => 'Preenchimento Obrigatório!',
            'nome.min' => 'O nome precisa ter no mínimo 3 letras',
            'nome.max' => 'O nome não pode exceder 100 letras!',
            'cpf.min' => 'O CPF precisa ter 11 dígitos numérico!',
            'cpf.max' => 'O CPF precisa ter 11 dígitos numérico!',
            'data_nasc.date' => 'Formato inválido de data! A data deve obedecer o formato YYYY-MM-DD',
            'email.email' => 'Formato inválido de e-mail!'
        ]);

        try {
            $user = $this->clienteService->saveNewUser($request->all());
            return ($user) ? response()->json($user, 200) : response()->json(['message' => 'Não foi possível cadastrar novo usuário!'], 500);
        }
        // exceção de tentativa de cadastrar um email repetido na tabela users
        catch(UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'Não é possível cadastrar email ou cpf repetidos!!!',
                'error' => $e->getMessage()
            ], 500);
        }
        // exceção de falha na comunicação com o banco de dados
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // retorna o cliente pelo seu id
    public function getCliente(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'required' => 'Informe o id!',
            'id.numeric' => 'O id é do tipo número!'
        ]);
        try {
            $client = $this->clienteService->getClientById($request->id);
            return response()->json($client, 200);
        }
        catch(ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente não foi encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request) {
        $request->validate([
            'id' => 'required|numeric',
            'email' => 'nullable|email|string|max:100'
        ],
        [
            'id.required' => 'O id do cliente precisa ser informado!',
            'id.numeric' => 'Por favor, informe um id do tipo numérico!',
            'email.email' => 'Formato inválido de emnil!'
        ]);

        try {
            $client = $this->clienteService->updateClientById($request->all());
            return ($client) ? response()->json($client, 200) : response()->json(['message' => 'Cliente não encontrado!'], 404);
        }
        
        // exceção de falha na comunicação com o banco de dados
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'id.required' => 'Por favor informe o id do Cliente que se deseja excluír!',
            'id.numeric' => 'Por favor, informe o id do tipo numérico'
        ]);
        try {
            $client = $this->clienteService->deleteClientById($request->id, $request->bearerToken());
            return (isset($client['cliente'])) ? 
                response()->json([
                    $client], 200) : 
                response()->json([
                    'message' => 'not found cliente!'], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // MSAVALIADORCREDITO
    // GET - retorna um cliente pelo seu CPF
    public function getClienteByCPF(Request $request) {
        $request->validate([
            'cpf' => 'required|numeric|'
        ],
        [
            'cpf.required' => 'Preenchimento Obrigatório!',
            'cpf.numeric' => 'O CPF precisa ser do tipo numérico'
        ]);
        try {
            $client = $this->clienteService->getClientByCPF($request->cpf);
            return response()->json($client, 200);
        }
        catch(ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente não foi encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
