<?php

namespace App\Http\Controllers;

use App\Enums\BandeirasCartaoCredito;
use App\Services\CartaoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

// Redis
use Illuminate\Support\Facades\Redis;

class CartaoController extends Controller {
    private $cardService;

    public function __construct(CartaoService $cardService) {
        $this->cardService = $cardService;
    }
    
    /**
     * @OA\Get(
     *    path="/mscartoes/cartoes",
     *    summary="Buscar lista de cartões de crédito cadastrados ",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>esse endpoint não exige parâmetros!</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Busca de cartões com sucesso!<p><b>Pós-condição</b></p><p>Retorna lista de todos os cartões de crédito cadastrados no microsserviço MSCARTOES, contendo os dados no formato de objeto { id, nome, bandeira, renda } .</p><p><b>NOTA: </b>Se não houver algum cartão de crédito previamente cadastrado no microsserviço MSCARTOES, será retornado um array vazio.</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="number", example="0"),
     *             @OA\Property(property="nome", type="string", example="nome_cartão"),
     *             @OA\Property(property="bandeira", type="string", example="nome_bandeira"),
     *             @OA\Property(property="renda", type="number", example="0000.00")
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Error: Unauthorized | Token expirado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Acesso não autorizado/token expirado!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error: Internal Server Error | Preenchimento vazio ou token de formato inválido!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Token de formato inválido!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (cartões e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Cartões",
     *                  value={
     *                     "status": "error_cards",
     *                     "message": "Serviço temporariamente indisponível. Tente novamente em instantes."
     *                  }
     *               ),
     *               @OA\Examples(
     *                  example="servico_redis",
     *                  summary="Erro Serviço Cache",
     *                  value={
     *                     "status": "error_cache",
     *                     "message": "Serviço de cache de tokens temporariamente indisponível! Tente novamente em instantes!"
     *                  }
     *               )
     *            }
     *         )
     *    )
     * )
     */
    public function index() {
        $listCards = $this->cardService->getAllCards();
        return response()->json($listCards, 200);
    }
    //----------------------------------------------------------------------------------------------------------

    // retorna lista de todos os cartões cadastrados
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/mscartoes/cartoes",
     *    summary="Cadastrar novo cartão de crédito",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "Dados do cartão<p><b>Pré-condição</b></p><ul><li><p>os dados do cartão devem ser preenchidos no corpo da requisição (dentro do json).</p></li><li><p><b>NOTA 1 <sup>nome</sup>: </b>o nome do cartão possui limite máximo de até 50 caracteres.</p></li><li><p><b>NOTA 2 <sup>bandeira</sup>: </b>o nome da bandeira a ser preenchido é do tipo <i>enumerado</i>, e as bandeiras estão definidas na opção <b>Schema</b>. Qualquer nome de bandeira que seja diferente das opções do tipo enumerado, a API retornará um erro de validação cód HTTP 422 .</p></li><li><p><b>NOTA 3 <sup>renda</sup>: </b>preencha somente com números (utilizando o ponto '.' como separador dos centavos) a renda do cartão (EX.: preencher o valor 1500.00 no atributo renda, equivale ao valor R$ 1.500,00).</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"nome", "bandeira", "renda"},
     *          @OA\Property(property="nome", type="string", example="nome_cartão"),
     *          @OA\Property(
     *             property="bandeira",
     *             description="bandeira do cartão",
     *             example="bandeira_enum",
     *             type="enum",
     *             enum={
     *                BandeirasCartaoCredito::VISA,
     *                BandeirasCartaoCredito::MASTERCARD,
     *                BandeirasCartaoCredito::AMEX,
     *                BandeirasCartaoCredito::ELO,
     *                BandeirasCartaoCredito::HIPERCARD,
     *                BandeirasCartaoCredito::DISCOVER,
     *                BandeirasCartaoCredito::DINERSCLUB,
     *                BandeirasCartaoCredito::JCB,
     *                BandeirasCartaoCredito::CREDZ,
     *                BandeirasCartaoCredito::SOROCRED,
     *                BandeirasCartaoCredito::CABAL,
     *                BandeirasCartaoCredito::BANESCARD
     *             }
     *          ),
     *          @OA\Property(property="renda", type="float", example= 0000.00)
     *       )
     *    ),
     *    @OA\Response(
     *       response="201",
     *       description=
     *          "Cartão cadastrado com sucesso!</b><p><b>Pós-condição</b></p><p>Retorno de uma resposta em json contendo os dados do cartão  no formato objeto { id, nome, bandeira, renda } .</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="id", type="number", example="0"),
     *          @OA\Property(property="nome", type="string", example="nome_cartao"),
     *          @OA\Property(property="bandeira", type="enum", example="nome_bandeira"),
     *          @OA\Property(property="renda", type="number", example=0)
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Error: Unauthorized | Token expirado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Acesso não autorizado/token expirado!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error: Unprocessable Content | Erro de validação. Conteúdo JSON da requisição possui erros semânticos ou lógicos",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="errors", type="object"),
     *          examples={
     *             @OA\Examples(
     *                example="preencha_nome",
     *                summary="Erro no nome",
     *                value={
     *                   "message": {"Informe o nome do cartão!", "Tamanho máximo nome do cartão não pode exceder 50 caracteres!"},
     *                   "errors": {
     *                      "nome": {"Informe o nome do cartão!", "Tamanho máximo nome do cartão não pode exceder 50 caracteres!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_bandeira",
     *                summary="Erro na bandeira",
     *                value={
     *                   "message": {"Informe o nome da bandeira!", "Nome da bandeira inválido!"},
     *                   "errors": {
     *                      "bandeira": {"Informe o nome da bandeira!", "Nome da bandeira inválido!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_renda",
     *                summary="Erro na renda",
     *                value={
     *                   "message": {"Informe a renda mínima desse cartão!", "Informe a renda mínima do cartão em formato numérico!"},
     *                   "errors": {
     *                      "renda": {"Informe a renda mínima desse cartão!", "Informe a renda mínima do cartão em formato numérico!"}
     *                   }
     *                }
     *             )
     *          }
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error: Internal Server Error | Preenchimento vazio ou token de formato inválido!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Token de formato inválido!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (cartões e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Cartões",
     *                  value={
     *                     "status": "error_cards",
     *                     "message": "Serviço temporariamente indisponível. Tente novamente em instantes."
     *                  }
     *               ),
     *               @OA\Examples(
     *                  example="servico_redis",
     *                  summary="Erro Serviço Cache",
     *                  value={
     *                     "status": "error_cache",
     *                     "message": "Serviço de cache de tokens temporariamente indisponível! Tente novamente em instantes!"
     *                  }
     *               )
     *            }
     *         )
     *    )
     * )
     */
    public function store(Request $request) {
        $request->validate([
            'nome' => 'required|string|max:50',
            'bandeira' => ['required', new Enum(BandeirasCartaoCredito::class)],
            'renda' => 'required|numeric'
        ],
        [
            'nome.required' => 'Informe o nome do cartão!',
            'nome.max' => 'Tamanho máximo nome do cartão não pode exceder 50 caracteres!',
            'bandeira.required' => 'Informe o nome da bandeira!',
            'bandeira.enum' => 'Nome da bandeira inválido!',
            'renda.required' => 'Informe a renda mínima desse cartão!',
            'renda.numeric' => 'Informe a renda mínima do cartão em formato numérico!'
        ]);
        $card = $this->cardService->saveCard($request->all());
        return ($card) ? 
            response()->json(
                $card, 201) :
            response()->json([
                'status' => 'error',
                'message' => 'Não foi possível cadastrar esse novo cartão!'
            ], 500);
    }
    //----------------------------------------------------------------------------------------------------------

    // retorna cartão pelo seu id
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *    path="/mscartoes/cartoes/{id}",
     *    summary="Retornar cartão pelo id ",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA 1 <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id: </b>informar id do cartão de crédito.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>para preencher o id, o cartão de crédito precisa estar previamente cadastrado no microsserviço MSCARTOES.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Cartão encontrado com sucesso!<p><b>Pós-condição</b></p><p>Retorno de uma resposta em json contendo os dados do cartão no formato objeto { id, nome, bandeira, renda } .</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="id", type="number", example="0"),
     *          @OA\Property(property="nome", type="string", example="nome_cartão"),
     *          @OA\Property(property="bandeira", type="string", example="nome_bandeira"),
     *          @OA\Property(property="renda", type="number", example="0000.00")
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | Parâmetro id de cartão inválido",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="O id do cartão precisa ser um número válido!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Error: Unauthorized | Token expirado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Acesso não autorizado/token expirado!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error: Not Found | Cartão não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cartão não encontrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error: Internal Server Error | Preenchimento vazio ou token de formato inválido!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Token de formato inválido!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (cartões e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Cartões",
     *                  value={
     *                     "status": "error_cards",
     *                     "message": "Serviço temporariamente indisponível. Tente novamente em instantes."
     *                  }
     *               ),
     *               @OA\Examples(
     *                  example="servico_redis",
     *                  summary="Erro Serviço Cache",
     *                  value={
     *                     "status": "error_cache",
     *                     "message": "Serviço de cache de tokens temporariamente indisponível! Tente novamente em instantes!"
     *                  }
     *               )
     *            }
     *         )
     *    )
     * )
     */
    public function showGet(int $id) {
        
        // testa se o parâmetro é do tipo inteiro
        if(!is_numeric($id)) {
            return response()->json([
                'error' => 'O id do cartão precisa ser um número válido!'
            ], 400);
        }
        else {
            $card = $this->cardService->getCardById($id);
            return response()->json($card, 200);
        }
    }
    //----------------------------------------------------------------------------------------------------------

    // atualiza dados do cartão
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Patch(
     *    path="/mscartoes/cartoes/{id}",
     *    summary="Atualizar dados (parciais) de cartão de crédito",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA 1 <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id: </b>informar id do cartão de crédito.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>para preencher o id, o cartão de crédito precisa estar previamente cadastrado no microsserviço MSCARTOES.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=false,
     *       @OA\Schema(type="string")
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p>preenchimento de forma parcial do json no corpo da requisição (nome, bandeira, renda).</p></li><li><p><b>NOTA 1: </b>o preenchimento dos atributos do json não são obrigatórios. Entretanto, se forem preenchidos para serem alterados/atualizados, a API irá aplicar as regras de validação em cada atributo.</p></li><li><p><b>NOTA 2 <sup>bandeira</sup>: </b>o nome da bandeira é do tipo enumerado, e as bandeiras estão definidas na opção <b>Schema .</b></p></li><li><p><b>NOTA 3 <sup>renda</sup>: </b>preencha somente com números (utilizando o ponto '.' como separador dos centavos) a renda do cartão (EX.: preencher o valor 1500.00 no atributo renda, equivale ao valor R$ 1.500,00).</p></li><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>não preenchendo os atributos no corpo da requisição, será retornado um json de resposta contendo os dados do cartão.</li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"nome", "bandeira", "renda"},
     *          @OA\Property(property="nome", type="string", example= ""),
     *          @OA\Property(
     *             property="bandeira",
     *             example="",
     *             type="enum",
     *             enum={
     *                BandeirasCartaoCredito::VISA,
     *                BandeirasCartaoCredito::MASTERCARD,
     *                BandeirasCartaoCredito::AMEX,
     *                BandeirasCartaoCredito::ELO,
     *                BandeirasCartaoCredito::HIPERCARD,
     *                BandeirasCartaoCredito::DISCOVER,
     *                BandeirasCartaoCredito::DINERSCLUB,
     *                BandeirasCartaoCredito::JCB,
     *                BandeirasCartaoCredito::CREDZ,
     *                BandeirasCartaoCredito::SOROCRED,
     *                BandeirasCartaoCredito::CABAL,
     *                BandeirasCartaoCredito::BANESCARD
     *             }
     *          ),
     *          @OA\Property(property="renda", type="float", example= 0)
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Atualizar um cartão com sucesso!<p><b>Pós-condição</b></p><p></p>Retornado um json de resposta contendo os dados do cartão no formato objeto { id, nome, bandeira, renda } .</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="number", example="0"),
     *             @OA\Property(property="nome", type="string", example="nome_cartao"),
     *             @OA\Property(property="bandeira", type="", example="nome_bandeira"),
     *             @OA\Property(property="renda", type="float", example="0000.00")
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | Parâmetro id de cartão inválido",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="O id do cartão precisa ser um número válido!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Error: Unauthorized | Token expirado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Acesso não autorizado/token expirado!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error: Not Found | Cartão não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cartão não encontrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error: Unprocessable Content | Erro de validação. Conteúdo JSON da requisição possui erros semânticos ou lógicos",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="errors", type="object"),
     *          examples={
     *             @OA\Examples(
     *                example="preencha_nome",
     *                summary="Erro no nome",
     *                value={
     *                   "message": "Tamanho máximo nome do cartão não pode exceder 50 caracteres!",
     *                   "errors": {
     *                      "nome": {"Tamanho máximo nome do cartão não pode exceder 50 caracteres!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_bandeira",
     *                summary="Erro na bandeira",
     *                value={
     *                   "message": {"Nome da bandeira inválido!"},
     *                   "errors": {
     *                      "bandeira": {"Nome da bandeira inválido!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_renda",
     *                summary="Erro na renda",
     *                value={
     *                   "message": "Informe a renda do cartão em formato numérico!",
     *                   "errors": {
     *                      "renda": {"Informe a renda do cartão em formato numérico!"}
     *                   }
     *                }
     *             )
     *          }
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error: Internal Server Error | Preenchimento vazio ou token de formato inválido!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Token de formato inválido!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (cartões e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Cartões",
     *                  value={
     *                     "status": "error_cards",
     *                     "message": "Serviço temporariamente indisponível. Tente novamente em instantes."
     *                  }
     *               ),
     *               @OA\Examples(
     *                  example="servico_redis",
     *                  summary="Erro Serviço Cache",
     *                  value={
     *                     "status": "error_cache",
     *                     "message": "Serviço de cache de tokens temporariamente indisponível! Tente novamente em instantes!"
     *                  }
     *               )
     *            }
     *         )
     *    )
     * )
     */
    public function update(Request $request, $id) {
        if(!is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O id do cartão precisa ser um número válido!'
            ], 400);
        }
        $request->validate([
            'nome' => 'nullable|string|max:50',
            'bandeira' => ['nullable', new Enum(BandeirasCartaoCredito::class)],
            'renda' => 'nullable|numeric'
        ],
        [
            'nome.max' => 'Tamanho máximo nome do cartão não pode exceder 50 caracteres!',
            'bandeira.enum' => 'Nome da bandeira inválido!',
            'renda.numeric' => 'Informe a renda do cartão em formato numérico!'
        ]);
        $card = $this->cardService->updateCardById($request->all(), $id);
        return (!($card['nome'] == 'FAIL')) ?
            response()->json([
                $card
            ], 200) :
            response()->json([
                'status' => 'not_found',
                'message' => 'Cartão não encontrado!'
            ], 404);
    }
    //----------------------------------------------------------------------------------------------------------

    // abaixo seguem os métodos que são acessados e executados por outros microsserviços: esses métodos NÃO estão disponíveis nos endpoints do microsserviço MSCARTOES

    // acessado pelo MICROSSERVIÇO MSAVALIADOR
    // retorna cartão pelo seu id
    public function show(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'id.required' => 'Por favor informe o id do cartão!',
            'id.numeric' => 'Por favor informe o id em formato numérico!'
        ]);
        
        $card = $this->cardService->getCardById($request->id);
        return response()->json($card, 200);
    }
    //----------------------------------------------------------------------------------------------------------

    // acessado pelo MICROSSERVIÇO MSCLIENTES
    // apaga os cartões vinculados ao cliente
    public function deleteCardByClientId(Request $request): void {
        $this->cardService->deleteClientCard($request->id);
    }
    //----------------------------------------------------------------------------------------------------------

    // acessado pelo MICROSSERVIÇO MSAVALIADOR
    // faz pesquisa de cartões conforme a renda informada pelo cliente
    public function getCartoes(Request $request) {
        
        $faixaRenda = (int)($request->renda);
        $faixaRenda = floatval(((int)($faixaRenda / 100)) * 100);
        $listCards = $this->cardService->getCardsByRenda($faixaRenda);
        return (!empty($listCards)) ?
            response()->json($listCards, 200) :
            [
                'status' => 'error',
                'message' => 'Saldo insuficiente! Não há cartões disponíveis para essa faixa de renda!'
            ];
    }
    //----------------------------------------------------------------------------------------------------------

    // acessado pelo MICROSSERVIÇO MSAVALIADOR
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
        return (!(isset($listaCartoesPorCliente[0]) == null)) ?
            response()->json(
                $listaCartoesPorCliente, 200) : 
            response()->json([
                'message' => 'O cliente não foi encontrado!'
            ], 404);
    }

}