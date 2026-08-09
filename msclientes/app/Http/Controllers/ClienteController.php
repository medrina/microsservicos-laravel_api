<?php

namespace App\Http\Controllers;

use App\Services\ClienteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use PDOException;

class ClienteController extends Controller {

    // objeto de serviço de operações no banco
    private $clienteService;

    public function __construct(ClienteService $clienteService) {

        // injeção de dependência do objeto de serviço
        $this->clienteService = $clienteService;
    }

    // retorna todos os clientes (ativos) cadastrados na tabela clientes
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *    path="/msclientes/clientes",
     *    summary="Retorna todos os clientes",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>esse endpoint não exige parâmetros!</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Retorna lista de clientes ativos<p><b>Pós-condição:</b></p><p>Retorna um array contendo todos os clientes ativos em formato de objeto { id, cpf, nome, data_nasc, email }.</p><ul><li><p><b>NOTA: </b>Caso não existam registros de clientes cadastrados na base de dados no microsserviço MSCLIENTES, o retorno será um array vazio.</p></li><br><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>os <i>clientes ativos</i>, significa os clientes que não foram apagados, e encontram-se ativos. A API enxerga esses clientes que estão ativos, tornando-os habilitados nos endpoints de buscas e em avaliações de crédito.<br>Clientes que foram apagados, a API não enxerga mais nos endpoints de busca e avaliações de crédito.<p>Para reativar clientes que foram apagados, você precisa consultar sobre a situação cadastral do cliente informando o CPF dele no endpoint: <b>GET - Pesquisar por situação cadastral de cliente pelo seu CPF</b> no microsserviço MSCLIENTES.</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="number", example="0"),
     *             @OA\Property(property="cpf", type="string", example="99999999999"),
     *             @OA\Property(property="nome", type="string", example="user"),
     *             @OA\Property(property="data_nasc", type="string", example="YYYY-MM-DD"),
     *             @OA\Property(property="email", type="string", example="user@email.com")
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
        $listClients = $this->clienteService->getAllClients();
        return response()->json($listClients, 200);
    }
    //----------------------------------------------------------------------------------------------------------

    // método que vai salvar novo cliente
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msclientes/clientes",
     *    summary="Cadastrar novo cliente",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p>dados de cliente preenchidos nos atributos do json (conforme exemplo abaixo).</p></li><li><p><b>NOTA 1: </b>a API aplicará as regras de validação nos atributos do json da requisição.</p></li><li><p><b>NOTA 2 <sup>cpf</sup>: </b>o CPF do cliente precisa seguir o formato de <b>11 dígitos numéricos.</b></p></li><li><p><b>NOTA 3 <sup>cpf</sup>: </b>não é possível cadastrar um CPF do cliente já existente na base de dados.</p></li><li><p><b>NOTA 4 <sup>data_nasc</sup>: </b>a data de nascimento do cliente deve seguir o formato: <b>YYYY-MM-DD.</b></p></li><li><p><b>NOTA 5 <sup>email</sup>: </b>para cadastrar um email do cliente, ele precisa seguir o formato de email: <b>user@email.com</b></p></li><li><p><b>NOTA 6 <sup>email</sup>: </b>não é possível cadastrar um email do cliente já existente na base de dados!</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"cpf", "nome", "data_nasc", "email"},
     *          @OA\Property(property="cpf", type="string", example="99999999999"),
     *          @OA\Property(property="nome", type="string", example="user"),
     *          @OA\Property(
     *             property="data_nasc",
     *             type="string",
     *             example="YYYY-MM-DD", 
     *             pattern="^\d{2}/\d{2}/\d{4}$"
     *          ),
     *          @OA\Property(property="email", type="string", format="email")
     *       )
     *    ),
     *    @OA\Response(
     *       response="201",
     *       description=
     *          "Cadastro criado com sucesso!<p><b>Pós-condição: </b></p>Cadastro de cliente salvo e persistido na base de dados. A API retornará um json de resposta contendo os dados desse novo cliente cadastrado em formato de objeto { id, cpf, nome, data_nasc, email }</p><p>O cadastro desse cliente na base de dados, torna-se habilitado a realizar avaliações de crédito no endpoint: <b>GET - Realizar uma avaliação de crédito, exibindo os cartões de crédito...</b> no microsserviço MSAVALIADOR.</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="id", type="number", example="0"),
     *          @OA\Property(property="cpf", type="string", example="99999999999"),
     *          @OA\Property(property="nome", type="string", example="user"),
     *          @OA\Property(property="data_nasc", type="date", example="YYYY-MM-DD"),
     *          @OA\Property(property="email", type="string", format="email")
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
     *       response=409,
     *       description="Error: Conflict | Atenção, não é possível cadastrar um email que já foi cadastrado!",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Esse email já foi cadastrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error: Unprocessable Content | Erro de validação. Conteúdo JSON da requisição possui erros semânticos ou lógicos. Não é possível cadastrar email e CPF que já foram cadastrados!",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(property="errors", type="object"),
     *          examples={
     *             @OA\Examples(
     *                example="preencha_cpf",
     *                summary="Erro no cpf",
     *                value={
     *                   "message": {"O CPF precisa ter 11 dígitos numérico!", "Esse CPF já foi cadastrado!"},
     *                   "errors": {
     *                      "cpf": {"O CPF precisa ter 11 dígitos numérico!", "Esse CPF já foi cadastrado!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_nome",
     *                summary="Erro no nome",
     *                value={
     *                   "message": {"Informe o nome do cliente!"},
     *                   "errors": {
     *                      "nome": {"Informe o nome do cliente!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_data_nasc",
     *                summary="Erro na data de nascimento",
     *                value={
     *                   "message": {"Informe a data de nascimento do cliente!", "Formato inválido de data!", "A data deve obedecer o formato YYYY-MM-DD"},
     *                   "errors": {
     *                      "data_nasc": {"Informe a data de nascimento do cliente!", "Formato inválido de data!", "A data deve obedecer o formato YYYY-MM-DD"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_email",
     *                summary="Erro no email",
     *                value={
     *                   "message": {"Informe o email do cliente!", "Formato inválido de e-mail!", "Esse email já foi cadastrado!"},
     *                   "errors": {
     *                      "email": {"Informe o email do cliente!", "Formato inválido de e-mail!", "Esse email já foi cadastrado!"}
     *                   }
     *                }
     *             )
     *          }
     *       )
     *     ),
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
        
        // verifica se o campo cpf foi digitado corretamente (se todos os caracteres são do tipo numérico, e que não contém traços e outros caracteres)
        if (!ctype_digit($request->cpf)) {

            // se o cpf conter traços e outros caracteres entre os números, um novo número será atribuído ao atributo cpf
            $request->merge(['cpf' => '111']);
        }
        
        // aplica validações, e respostas nos atributos da requisição
        $request->validate([
            'cpf' => 'required|min:11|max:11|unique:clientes',
            'nome' => 'required|max:100',
            'data_nasc' => 'required|date|date_format:Y-m-d',
            'email' => 'required|email|string|max:100|unique:clientes'
        ],
        [
            'nome.required' => 'Informe o nome do cliente!',
            'nome.max' => 'O nome não pode exceder 100 letras!',
            'cpf.required' => 'Informe o CPF do cliente!',
            'cpf.unique' => 'Esse CPF já foi cadastrado!',
            'cpf.min' => 'O CPF precisa ter 11 dígitos numérico!',
            'cpf.max' => 'O CPF precisa ter 11 dígitos numérico!',
            'data_nasc.required' => 'Informe a data de nascimento do cliente!',
            'data_nasc.date' => 'Formato inválido de data!',
            'data_nasc.date_format' => 'A data deve obedecer o formato YYYY-MM-DD',
            'email.required' => 'Informe o email do cliente!',
            'email.email' => 'Formato inválido de e-mail!',
            'email.unique' => 'Esse email já foi cadastrado!'
        ]);
        
        // cadastra novo cliente no banco de dados
        $user = $this->clienteService->saveNewUser($request->all());
        return ($user) ? response()->json($user, 201) : response()->json(['message' => 'Não foi possível cadastrar novo usuário!'], 500);
    }
    //----------------------------------------------------------------------------------------------------------
    
    // retorna cliente pelo seu id
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *    path="/msclientes/clientes/{id}",
     *    summary="Retornar cliente pelo id ",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA 1 <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id: </b>informar id do cliente.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>para preencher o id, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li></ul>"
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
     *          "Cliente encontrado com sucesso!<p><b>Pós-condição: </b></p>A API retornará um json de resposta contendo os dados desse cliente encontrado em formato de objeto { id, cpf, nome, data_nasc, email }</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="id", type="number", example="0"),
     *          @OA\Property(property="cpf", type="string", example="99999999999"),
     *          @OA\Property(property="nome", type="string", example="user"),
     *          @OA\Property(property="data_nasc", type="date", example="YYYY-MM-DD"),
     *          @OA\Property(property="email", type="email", example="user@email.com")
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | Parâmetro id de cliente inválido",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="O id do cliente precisa ser um número válido!")
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
     *       description="Error: Not Found | Cliente não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cliente não encontrado!")
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
    public function getClienteByGet(Request $request, int $id) {
        if(!is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O parâmetro id precisa ser um número válido!'
            ], 400);
        }
        $client = $this->clienteService->getClientById($id);
        return response()->json($client, 200);
    }
    //----------------------------------------------------------------------------------------------------------

    // atualiza cliente pelo seu id
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Put(
     *    path="/msclientes/clientes/{id}",
     *    summary="Atualizar dados do cliente",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA 1 <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id: </b>informar id do cliente.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>o id do cliente é de preenchimento obrigatório!</p></li><li><p><b>NOTA 3 <sup>id</sup>:</b> para preencher o id, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li></ul>"
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
     *          "<p><b>Pré-condição:</b></p><ul><li><p>preenchimento/atualização opcional do atributo email no json da requisição (email).</p></li><li><p><b>NOTA 1 <sup>email</sup>: </b>o preenchimento do atributo email não é obrigatório. Mas, caso o email seja preenchido para ser alterado/atualizado, a API irá aplicar as regras de validação no email.</p></li><li><p><b>NOTA 2 <sup>email</sup>: </b>para atualizar o email, ele deve obedecer o seguinte formato de email: <b>user@email.com</b></p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="email", type="string", format="email", example="")
     *       )
     *    ),
     *    @OA\Response(
     *       response="201",
     *       description=
     *          "Cadastro de cliente atualizado com sucesso!<p><b>Pós-condição</b></p>Será retornado um json de resposta contendo os dados do cliente no formato de objeto { id, cpf, nome, data_nasc, email }.</p><p><b>NOTA: </b>Havendo ou não o preenchimento do atributo email na Pré-condição, o json de retorno será retornado contendo os dados salvos na base de dados do microsserviço MSCLIENTES (como mostra o exemplo de resposta do json abaixo).</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *             type="object",
     *             @OA\Property(property="id", type="number", example="0"),
     *             @OA\Property(property="cpf", type="string", example="99999999999"),
     *             @OA\Property(property="nome", type="string", example="user"),
     *             @OA\Property(property="data_nasc", type="string", example="YYYY-MM-DD"),
     *             @OA\Property(property="email", type="string", example="user@email.com")
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | Parâmetro id de cliente inválido",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="O id do cliente precisa ser um número válido!")
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
     *       description="Error: Not Found | Cliente não encontrado!",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cliente não encontrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=409,
     *       description="Error: Conflict | Não é possível atualizar um email de cliente que já foi cadastrado!",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Esse email já foi cadastrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error: Unprocessable Content | Erro de validação. Conteúdo JSON da requisição possui erros semânticos ou lógicos!",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(
     *             property="errors",
     *             type="object"
     *          ),
     *          examples={
     *             @OA\Examples(
     *                example="preencha_email",
     *                summary="Email inválido",
     *                value={
     *                   "message": "Formato inválido de email!",
     *                   "errors": {
     *                      "email": {"Formato inválido de email!"}
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
                'message' => 'O id do cliente precisa ser um número válido!'
            ], 400);
        }
        $request->validate([
            'email' => 'nullable|email|string|max:100'
        ],
        [
            'email.email' => 'Formato inválido de email!'
        ]);
        $client = $this->clienteService->updateClientById($request->all(), $id);
        return ($client) ?
            response()->json([
                $client 
            ], 200) :
            response()->json([
                'status' => 'not_found',
                'message' => 'Cliente não encontrado!'
            ], 404);
    }
    //----------------------------------------------------------------------------------------------

    // apaga cliente pelo seu id
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Delete(
     *    path="/msclientes/clientes/{id}",
     *    summary="Apagar cadastro do cliente e seus registros de cartões",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id: </b>informar id do cliente.</p></li><li><p><b>NOTA 1 <sup>id</sup>:</b> para preencher o id, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>se a busca do cliente for encontrado pelo seu id, o registro <u>NÃO</u> será removido permanentemente, contudo receberá o status de <i>disabled</i> (desativado).</p></li><li><p><b>NOTA 3: <sup>id</sup></b>conforme citado na NOTA 2, os cartões vinculados ao cliente também não serão removidos permanentemente da base de dados.</p></li><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>a condição de não excluir o registro permanentemente, existe para manter um histórico de cartões vinculados ao cliente, caso ele seja apagado. Essa condição também permite que o cliente apagado seja reativado novamente.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=false,
     *       @OA\Schema(type="string")
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Cliente removido com sucesso!<p><b>Pós-condição</b></p><p></p>Será desativado o registro do cliente juntamente com todos os cartões solicitados e vinculados a ele (o cliente), em ambas as bases de dados dos microsserviços MSCLIENTES e MSCARTOES.</p><p><b>NOTA: </b>A API não enxerga mais esse cliente nos endpoints de busca e avaliação de crédito.</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="message", type="string", example="Cliente apagado com sucesso!")
     *       )
     *    ),
     *    @OA\Response(
     *       response="400",
     *       description="Error: Bad Request | Parâmetro id de cliente inválido",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="O id do cliente precisa ser um número válido!")
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
     *       description="Error: Not Found | Cliente não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cliente não encontrado!")
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
    public function delete(Request $request, $id) {
        if(!is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O id do cliente precisa ser um número válido!'
            ], 400);
        }
        else {
            $client = $this->clienteService->deleteClientById($id, $request->bearerToken());
            return ($client) ? 
                response()->json([
                    'status' => 'success',
                    'message' => 'Cliente apagado com sucesso!'
                ], 200) : 
                response()->json([
                    'status' => 'not_found',
                    'message' => 'Cliente não encontrado!']
                , 404);
        }
    }
    //----------------------------------------------------------------------------------------------------------

    // busca situação de cadastro do cliente pelo seu cpf (se o cliente encontra-se ativo ou foi apagado da API)
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *    path="/msclientes/clientes/search-recovery",
     *    summary="Pesquisar por situação cadastral de cliente pelo seu CPF (cliente ativo/desativado)",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>cpf: </b>informar cpf do cliente.</p></li><li><p><b>NOTA 1 <sup>cpf</sup>:</b> para preencher o cpf, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li><li><p><b>NOTA 2 <sup>cpf</sup>: </b>digite apenas números no parâmetro do cpf (sem pontos, traços, hífen, letras ou caracteres especiais).</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}}, 
     *    @OA\Parameter(
     *       name="cpf",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string")
     *    ), 
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Busca de cliente encontrado com sucesso!<p><b>Pós-condição</b></p><p>Retorna um json de resposta exibindo dados desse cliente.</p><ul><li><p><b>NOTA 1: </b>Junto com essa resposta, será exibida a situação cadastral desse cliente no atributo status: <i>enabled_client</i> para cliente ativado | <i>disabled_client</i> para cliente desativado.</p></li><br><li><p><b>NOTA 2: </b>Também será exibida a data em que esse cliente foi cadastrado no atributo <i>data_inicio</i> ou a data em que o cliente foi removido no atributo <i>data_fim .</i></p></li><br><li><p><b>NOTA 3: </b>Se o cliente nunca foi removido, ou foi reativado o seu registro, o atributo data_fim exibirá o valor <i>active .</i></p></li><br><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>Esse endpoint possui objetivo de verificar o status do cliente (ativo ou desativado). Dentro do json de resposta, será exibido o id desse cliente. Caso o objetivo seja reativar algum cliente que foi apagado, precisa informar o id retornado do cliente no endpoint: <b>POST - Reativar cadastro de cliente (cliente ativo)</b> no microsserviço MSCLIENTES.</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *             type="object",
     *             @OA\Property(property="status", type="string", example={"enabled_client", "disabled_client"}),
     *             @OA\Property(
     *                property="client",
     *                type="object",
     *                @OA\Property(property="id", type="number", example="0"),
     *                @OA\Property(property="cpf", type="string", example="99999999999"),
     *                @OA\Property(property="nome", type="string", example="user"),
     *                @OA\Property(property="email", type="email", example="user@email.com"),
     *                @OA\Property(property="data_inicio", type="date", example="YYYY-MM-DD HH:MM:SS"),
     *                @OA\Property(property="data_fim", type="date", example={"YYYY-MM-DD HH:MM:SS", "active"})
     *             )
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | CPF do cliente inválido! O CPF precisa estar no formato numérico!",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example={"O CPF precisa ser informado!", "O CPF precisa ser do formato numérico!"})
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
     *       description="Error: Not Found | Cliente não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cliente não encontrado!")
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
    public function searchRecoveryClient(Request $request) {
        if(!$request->has('cpf')) {
            return response()->json([
                'status' => 'error',
                'message' => 'O CPF precisa ser informado!'
            ], 400);
        }
        else {
            if(!is_numeric($request->cpf)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O CPF precisa ser do formato numérico!'
                ], 400);
            }
        }
        
        $client = $this->clienteService->searchRecovery($request->cpf);
        return ($client) ? 
            response()->json([
                $client
            ], 200) :
                response()->json([
                'status' => 'not_found',
                'nessage' => 'cliente não encontrado'
            ], 404);
        
    }
    //----------------------------------------------------------------------------------------------------------

    // reativa cliente
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msclientes/clientes/recovery",
     *    summary="Reativar cadastro de cliente (cliente ativo)",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p><b>id: </b>informar id do cliente no atributo id no corpo da requisição (dentro do json).</p></li><li><p><b>NOTA 1 <sup>id</sup>:</b> para preencher o id, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>o id desse cliente que se deseja reativar o seu cadastro, poderá ser obtido pelo endpoint acima: <b>GET - Pesquisar por situação cadastral de cliente pelo seu CPF</b> no microsserviço MSCLIENTES.</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"id"},
     *          @OA\Property(property="id", type="number")
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Cliente reativado com sucesso!<p><b>Pós-condição</b></p><p>Retorna um json de resposta exibindo dados desse cliente.</p><ul><li><p><b>NOTA 1: </b>Caso o cliente esteja desativado, será exibido no atributo <i>status</i>: <b>reactivated_client</b> e a mensagem no atributo <i>message</i>: <b>Cliente reativado .</b></p></li><br><li><p><b>NOTA 2: </b>Caso o cliente esteja ativado, será exibido no atributo <i>status</i>: <b>active_client</b> e a mensagem no atributo <i>message:</i><b>Cliente encontra-se ativo .</b></p></li><br><li><p><b>NOTA 3: </b>A cada remoção e reativação do cliente, o atributo <i>data_inicio</i> será atualizado sempre pelo último registro de reativação do cliente, ou seja, quando esse endpoint for executado.</p></li><br><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>Esse endpoint possui um único objetivo: reativar o cliente que foi removido pelo seu id. Uma vez que o cliente volta a estar ativo, ele voltará a ser exibido nos endpoints de pesquisas de clientes (microsserviço MSCLIENTES) e nas avaliações de crédito (microsserviço MSAVALIADOR).</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(
     *             type="object",
     *             @OA\Property(property="status", type="string", example={"reactivated_client", "active_client"}),
     *             @OA\Property(property="message", type="string", example={"Cliente reativado", "Cliente encontra-se ativo"}),
     *             @OA\Property(
     *                property="client",
     *                type="object",
     *                @OA\Property(property="id", type="number", example="0"),
     *                @OA\Property(property="cpf", type="string", example="99999999999"),
     *                @OA\Property(property="nome", type="string", example="user"),
     *                @OA\Property(property="email", type="email", example="user@email.com"),
     *                @OA\Property(property="data_inicio", type="date", example="YYYY-MM-DD")
     *             )
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
     *       response=404,
     *       description="Error: Not Found | Cliente não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cliente não encontrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=422,
     *       description="Error: Unprocessable Content | Erro de validação. Conteúdo JSON da requisição possui erros semânticos ou lógicos",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *          @OA\Property(
     *             property="errors",
     *             type="object"
     *          ),
     *          examples={
     *             @OA\Examples(
     *                example="preencha_nome",
     *                summary="Erro no id",
     *                value={
     *                   "message": {"Informe o id do Cliente que se deseja reativar!", "O id precisa ser do tipo numérico!"},
     *                   "errors": {
     *                      "id": {"Informe o id do Cliente que se deseja reativar!", "O id precisa ser do tipo numérico!"}
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
    public function recoveryClient(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'id.required' => 'Informe o id do Cliente que se deseja reativar!',
            'id.numeric' => 'O id precisa ser do tipo numérico!'
        ]);
        $client = $this->clienteService->recoveryClient($request->id);
        return ($client) ? 
            response()->json([
                $client
            ], 200) : 
            response()->json([
                'status' => 'not_found',
                'message' => 'cliente não encontrado'
            ], 404);
    }
    //----------------------------------------------------------------------------------------------------------    

    // retorna cliente pelo seu cpf
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *    path="/msclientes/cliente",
     *    summary="Retornar cliente pelo seu CPF",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>cpf: </b>informar cpf do cliente.</p></li><li><p><b>NOTA 1 <sup>cpf</sup>:</b> para preencher o cpf, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li><li><p><b>NOTA 2 <sup>cpf</sup>: </b>digite apenas números no parâmetro do cpf (sem pontos, traços, hífen, letras ou caracteres especiais).</p></li><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>esse endpoint possui o objetivo de retornar os dados do cliente, quando o id do cliente é desconhecido. Também serve para utilizar nos endpoints que exigem que o id do cliente seja o único dado a ser enviado por parâmetro.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}}, 
     *    @OA\Parameter(
     *       name="cpf",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string")
     *    ), 
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Cliente encontrado com sucesso!<p><b>Pós-condição</b></p><p>Retorna um json de resposta contendo os dados do cliente, dentre eles o <i>id</i> no formato objeto { id, cpf, nome, data_nasc, email } .</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="id", type="number", example="0"),
     *          @OA\Property(property="cpf", type="string", example="99999999999"),
     *          @OA\Property(property="nome", type="string", example="user"),
     *          @OA\Property(property="data_nasc", type="date", example="YYYY-MM-DD"),
     *          @OA\Property(property="email", type="string", format="email")
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | CPF do cliente inválido! O CPF precisa estar no formato numérico!",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example={"O CPF precisa ser informado!", "O CPF precisa ser do formato numérico!"})
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
     *       description="Error: Not Found | Cliente não encontrado",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Cliente não encontrado!")
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (clientes e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Clientes",
     *                  value={
     *                     "status": "error_clients",
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
    public function getClienteByCPF_GET(Request $request) {
        if(!$request->has('cpf')) {
            return response()->json([
                'status' => 'error',
                'message' => 'O CPF precisa ser informado!'
            ], 400);
        }
        else {
            if(!is_numeric($request->cpf)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O CPF precisa ser do formato numérico!'
                ], 400);
            }
        }
        $client = $this->clienteService->getClientByCPF($request->cpf);
        return response()->json($client, 200);
    }
    //----------------------------------------------------------------------------------------------------------

    // abaixo seguem os métodos que são acessados e executados por outros microsserviços: esses métodos NÃO estão disponíveis nos endpoints do microsserviço MSCLIENTES

    // retorna um cliente pelo seu id (microsserviço MSAVALIADOR)
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
    //----------------------------------------------------------------------------------------------------------

    // endpoint executado pelo microsserviço MSAVALIADOR
    // retorna um cliente pelo seu CPF
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
                'status' => 'not_found',
                'message' => 'Cliente não foi encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}