<?php

namespace App\Http\Controllers;

use App\Enums\BandeirasCartaoCredito;
use App\Services\CartaoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use PDOException;

class CartaoController extends Controller {
    private $cardService;

    public function __construct(CartaoService $cardService) {
        $this->cardService = $cardService;
    }
    
    public function index() {
        try {
            $listCards = $this->cardService->getAllCards();
            return response()->json($listCards, 200);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Não foi possível realizar essa consulta no banco de dados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request) {
        $request->validate([
            'nome' => 'required|string|max:50',
            'bandeira' => ['required', new Enum(BandeirasCartaoCredito::class), 'max:30'],
            'renda' => 'required|numeric'
        ],
        [
            'required' => 'Preencha os campos obrigatórios!',
            'nome.max' => 'Tamanho máximo nome do cartão não pode exceder 50 caracteres!',
            'bandeira.enum' => 'Nome da bandeira inválido!',
            'bandeira.max' => 'Tamanho máximo do nome da bandeira não pode exceder 30 caracteres!',
            'renda.numeric' => 'Informe a renda mínima do cartão em formato numérico!'
        ]);
        
        try {
            $card = $this->cardService->saveCard($request->all());
            return ($card) ? response()->json($card->getAttributes(), 200) : response()->json(['message' => 'Não foi possível cadastrar esse novo cartão!'], 500);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // retorna cartão pelo seu id
    public function show(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'id.required' => 'Por favor informe o id do cartão!',
            'id.numeric' => 'Por favor informe o id em formato numérico!'
        ]);
        try {
            $card = $this->cardService->getCardById($request->id);
            return response()->json($card, 200);
        }
        catch(ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cartão não encontrado',
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
            'nome' => 'nullable|string|max:50',
            'bandeira' => ['nullable', new Enum(BandeirasCartaoCredito::class), 'max:30'],
            'renda' => 'nullable|numeric'
        ],
        [
            'id.required' => 'Por favor informe o id!',
            'id.numeric' => 'Por favor informe o id sendo um valor numérico!',
            'nome.max' => 'Tamanho máximo nome do cartão não pode exceder 50 caracteres!',
            'bandeira.enum' => 'Nome da bandeira inválido!',
            'bandeira.max' => 'Tamanho máximo do nome da bandeira não pode exceder 30 caracteres!',
            'renda.numeric' => 'Informe a renda mínima do cartão em formato numérico!'
        ]);
        try {
            $card = $this->cardService->updateCardById($request->all());
            return (!($card->getAttributes()['nome'] == 'FAIL')) ? response()->json([$card->getAttributes()], 200) : response()->json(['message' => 'Cartão não encontrado!'], 404);
        }
        // exceção de falha na comunicação com o banco de dados
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'id.required' => 'Por favor informe o id do Cartão que se deseja excluír!',
            'id.numeric' => 'Por favor, informe o id do tipo numérico'
        ]);
        try {
            $card = $this->cardService->deleteCardById($request->id);
            return (isset($card['cartao'])) ? 
                response()->json([
                    $card], 200) : 
                response()->json([
                    'message' => 'Cartão não encontrado!'], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // MSCLIENTES
    public function deleteCardByClientId(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'id.required' => 'Por favor informe o id do Cartão que se deseja excluír!',
            'id.numeric' => 'Por favor, informe o id do tipo numérico'
        ]);
        $resultado = $this->cardService->deleteClientCard($request->id);
        return ($resultado) ?
            response()->json([
                'message' => 'success client_card'], 200) : 
            response()->json([
                'message' => 'cliente não encontrado'], 404);
    }

    // MSAVALIADORCREDITO
    // faz pesquisa de cartões conforme a renda informada pelo cliente
    public function getCartoes(Request $request) {
        try {
            $faixaRenda = (int)($request->renda);
            $faixaRenda = floatval(((int)($faixaRenda / 100)) * 100);
            $listCards = $this->cardService->getCardsByRenda($faixaRenda);
            return (!empty($listCards)) ?
                response()->json($listCards, 200) :
                response()->json([
                    'message' => 'Saldo insuficiente!',
                    'error' => 'Não há cartões disponíveis para essa faixa de renda!'
                ], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // MSAVALIADORCREDITO
    // retorna lista de cartões solicitados pelo id do cliente
    public function getCartoesByCliente(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'required' => 'O id precisa ser preenchido!',
            'id.numeric' => 'O id precisa ser do formato numérico!'
        ]);
        $listaCartoesPorCliente = $this->cardService->getClienteCartoes($request->id);
        return (!(isset($listaCartoesPorCliente[0]) == null)) ? response()->json($listaCartoesPorCliente, 200) : response()->json(['message' => 'O cliente não foi encontrado!'], 404);
    }
}
