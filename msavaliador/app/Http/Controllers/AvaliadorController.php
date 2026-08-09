<?php

namespace App\Http\Controllers;

use App\Services\AvaliadorService;
use ErrorException;
use PhpAmqpLib\Exception\AMQPIOException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use TypeError;

class AvaliadorController extends Controller {
    
    private $avaliadorService;

    public function __construct(AvaliadorService $avaliadorService) {
        $this->avaliadorService = $avaliadorService;
    }

    // realiza uma avaliação de crédito pelo cpf e renda do cliente
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *      path="/msavaliador/avaliacao-credito/{renda}",
     *      summary="Realizar uma avaliação de crédito, exibindo os cartões de crédito calculado mediante a renda do cliente.",
     *      description=
     *         "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA 1 <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>renda: </b>a ser informada pelo cliente.</p></li><li><p><b>NOTA 2 <sup>renda</sup>: </b>preencha somente números (utilizando o ponto '.' como separador dos centavos) a renda mensal do cliente (EX.: preencher o valor: 1500.15 equivale a R$ 1.500,15).</p></li><li><p><b>cpf: </b>preencha o cpf do cliente.</p></li><li><p><b>NOTA 3 <sup>cpf</sup>: </b>para preencher o cpf, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li></ul>"
     *      ,
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="renda",
     *          in="path",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="cpf",
     *          in="query",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(response="200",
     *         description=
     *            "Avaliação de crédito realizada com sucesso!<p><b>Pós-condição:</b></p><p>A resposta irá exibir um json contendo dados do cliente, a lista de cartões disponíveis de acordo com a renda do cliente, e com as faixas de renda dos cartões de crédito.</p><p>Dentre esses dados de resposta: <b>id do cliente, id do cartão, renda informada</b> do cliente e o <b>limite básico</b> (esses 4 dados devem ser preenchidos no endpoint: <b>POST - Solicitar um cartão de crédito</b> no microsserviço MSAVALIADOR).</p>"
     *      ,
     *         @OA\JsonContent(
     *            properties={
     *               @OA\Property(property="status", type="string", example="success"),
     *               @OA\Property(
     *                  property="cliente",
     *                  type="object",
     *                  properties={
     *                     @OA\Property(property="id", type="integer", example=0),
     *                     @OA\Property(property="cpf", type="string", example="99999999999"),
     *                     @OA\Property(property="nome", type="string", example="user"),
     *                     @OA\Property(property="data_nasc", type="string", format="date", example="YYYY-MM-DD"),
     *                     @OA\Property(property="email", type="string", format="email")
     *                  }
     *               ),
     *               @OA\Property(
     *                  property="cartoes",
     *                  type="array",
     *                  items=@OA\Items(
     *                     properties={
     *                        @OA\Property(property="id", type="integer", example=0),
     *                        @OA\Property(property="nome", type="string", example="nome_cartao"),
     *                        @OA\Property(property="bandeira", type="string", example="nome_bandeira"),
     *                        @OA\Property(property="renda", type="number", format="float", example="0000.00"),
     *                        @OA\Property(property="limiteBasico", type="number", format="float", example=0000.00)
     *                     }
     *                  )
     *               )
     *            }
     *         )
     *      ),
     *      @OA\Response(
     *         response=400,
     *         description="Error: Bad Request | Parâmetros na requisição inválidos",
     *         @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="preencha_cpf",
     *                  summary="Erro no cpf",
     *                  value={
     *                     "status": "error",
     *                     "message": {"O CPF precisa ser preenchido!", "O CPF precisa estar no formato numérico!", "O CPF precisa conter 11 dígitos!"}
     *                  }
     *               ),
     *               @OA\Examples(
     *                  example="preencha_renda",
     *                  summary="Erro na renda",
     *                  value={
     *                     "status": "error",
     *                     "message": "A renda precisa estar no formato numérico!"
     *                  }
     *               )
     *            }
     *         )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Error: Unauthorized | Token expirado",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="error"),
     *              @OA\Property(property="message", type="string", example="Acesso não autorizado/token expirado!!!")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Error: Not Found | CPF não foi encontrado!",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="not_found"),
     *              @OA\Property(property="message", type="string", example="CPF não encontrado!")
     *          )
     *      ),
     *      @OA\Response(
     *         response=422,
     *         description="Error: Unprocessable Content | Erro de validação. Renda informada do cliente está abaixo do limite mínimo da faixa de valor dos cartões de crédito",
     *         @OA\JsonContent(
     *            type="object",
     *            @OA\Property(property="status", type="string", example="error"),
     *            @OA\Property(property="message", type="string", example="Saldo insuficiente! Não há cartões disponíveis para essa faixa de renda!")
     *         )
     *      ),
     *      
     *     @OA\Response(
     *       response=500,
     *       description="Error: Internal Server Error | Preenchimento vazio ou token de formato inválido!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Token de formato inválido!!!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=502,
     *       description="Error: Bad Gateway | Falha de conexão com serviço dos cartões!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error_cards"),
     *          @OA\Property(property="message", type="string", example="Serviço de cartões temporariamente indisponível. Tente novamente em instantes!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviços externos indisponíveis",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Dados",
     *                  value={
     *                     "status": "error",
     *                     "message": "Serviço temporariamente indisponível. Tente novamente em instantes!"
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
    public function avaliarCredito(Request $request, string $renda) {
        if(!$request->has('cpf')) {
            return response()->json([
                'status' => 'error',
                'message' => 'O CPF precisa ser preenchido!'
            ], 400);
        }
        else {
            if(!ctype_digit($request->cpf)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O CPF precisa estar no formato numérico!'
                ], 400);
            }
            else if(mb_strlen($request->cpf) != 11) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O CPF precisa conter 11 dígitos!'
                ], 400);
            }
        }
        if(!is_numeric($renda)) {
            return response()->json([
                'status' => 'error',
                'message' => 'A renda precisa estar no formato numérico!'
            ], 400);
        }
        
        try {
            $situacaoCliente = $this->avaliadorService->avaliarCreditoMSClientesMSCartoes($request->cpf, $renda, $request->bearerToken());
            return ($situacaoCliente['status'] == 'success') ?
                response()->json($situacaoCliente, 200) :
                response()->json($situacaoCliente, 422);
        }
        catch(RequestException $e) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'CPF não encontrado!'
            ], 404);
        }
        catch (ErrorException $e) {
            return response()->json([
                'status' => 'error_cards',
                'message' => 'Serviço de cartões temporariamente indisponível. Tente novamente em instantes!'
            ], 502);
        }
        catch(ConnectionException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Serviço temporariamente indisponível. Tente novamente em instantes!'
            ], 503);
        }
    }
    //----------------------------------------------------------------------------------------------------------

    // envia solicitação de cartão para o broker do RabbitMQ
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msavaliador/solicitar-cartao",
     *    summary="Solicitar um cartão de crédito disponível conforme a sua renda, escolhido por um cliente cadastrado. O limite inicial, será estabelecido pelo limite básico",
     *    description=
     *       "<p><b>Pré-condição</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p>preencher o json no corpo da requisição com os 4 dados (id do cliente, id do cartão, renda do cliente e o limite básico) obtidos no endpoint: <b>GET - Realizar uma avaliação de crédito</b> no microsserviço MSAVALIADOR.</p></li><li><p><b>NOTA 1: </b>todos os dados dos atributos do json devem ser do tipo numéricos.</p></li><li><p><b>NOTA 2: </b>todos os dados dos atributos do json devem ser maiores que o valor 0 .</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"cliente_id", "cartao_id", "renda_cliente", "limite_basico"},
     *          @OA\Property(property="cliente_id", type="integer"),
     *          @OA\Property(property="cartao_id", type="integer"),
     *          @OA\Property(property="renda_cliente", type="float"),
     *          @OA\Property(property="limite_basico", type="float"),
     *       )
     *    ),
     *    @OA\Response(
     *       response="202",
     *       description=
     *          "Solicitação de cartão enviada com sucesso!<p><b>Pós-condição:</b></p><p>Envia a solicitação ao microsserviço MSCARTOES, que recebendo, irá persistir na sua base de dados.</p><p><b>INFORMAÇÃO COMPLEMENTAR: </b>Caso o microsserviço MSCARTOES esteja ausente ou interrompido, a solicitação será enviada e armazenada temporariamente no broker da fila de mensageria. Quando o microsserviço MSCARTOES voltar/entrar em execução, a solicitação será processada e persistida na sua base de dados.</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="message", type="string", example="Sua solicitação de cartão passará por uma breve análise!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Error: Unauthorized | Token expirado",
     *       @OA\JsonContent(
     *          type="object",
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
     *                example="preencha_cliente_id",
     *                summary="Erro no id do cliente",
     *                value={
     *                   "message": {"Preencha o id do cliente!", "O id do cliente precisa ser do tipo numérico!", "O id do cliente não pode ser igual a zero!"},
     *                   "errors": {
     *                      "cliente_id": {"Preencha o id do cliente!", "O id do cliente precisa ser do tipo numérico!", "O id do cliente não pode ser igual a zero!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_cartao_id",
     *                summary="Erro no id do cartão",
     *                value={
     *                   "message": {"Preencha o id do cartão!", "O id do cartão precisa ser do tipo numérico!", "O id do cartão não pode ser igual a zero!"},
     *                   "errors": {
     *                      "cartao_id": {"Preencha o id do cartão!", "O id do cartão precisa ser do tipo numérico!", "O id do cartão não pode ser igual a zero!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_renda_cliente",
     *                summary="Erro na renda do cliente",
     *                value={
     *                   "message": {"Preencha a renda do cliente!", "A renda do cliente precisa ser um valor numérico!", "A renda do cliente não pode ser igual a zero!"},
     *                   "errors": {
     *                      "renda_cliente": {"Preencha a renda do cliente!", "A renda do cliente precisa ser um valor numérico!", "A renda do cliente não pode ser igual a zero!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="preencha_limite_basico",
     *                summary="Erro no limite básico do cliente",
     *                value={
     *                   "message": {"Preencha o limite básico do cliente!", "O limite básico precisa ser um valor numérico!", "O limite básico do cliente não pode ser igual a zero!"},
     *                   "errors": {
     *                      "limite_basico": {"Preencha o limite básico do cliente!", "O limite básico precisa ser um valor numérico!", "O limite básico do cliente não pode ser igual a zero!"}
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_redis",
     *                  summary="Erro Serviço Cache",
     *                  value={
     *                     "status": "error_cache",
     *                     "message": "Serviço de cache de tokens temporariamente indisponível! Tente novamente em instantes!"
     *                  }
     *               ),
     *               @OA\Examples(
     *                  example="servico_rabbitmq",
     *                  summary="Erro Solicitação Cartão",
     *                  value={
     *                     "status": "error_solicit_cards",
     *                     "message": "Serviço de solicitação de cartão temporariamente indisponível! Tente novamente em instantes!"
     *                  }
     *               )
     *            }
     *         )
     *    )
     * )
     */
    public function enviarSolicitacaoCartao(Request $request) {
        $request->validate([
            'cliente_id' => 'required|numeric|min:1',
            'cartao_id' => 'required|numeric|min:1',
            'renda_cliente' => 'required|numeric|min:1',
            'limite_basico' => 'required|numeric|min:1'
        ],
        [
            'cliente_id.required' => 'Preencha o id do cliente!',
            'cartao_id.required' => 'Preencha o id do cartão!',
            'renda_cliente.required' => 'Preencha a renda do cliente!',
            'limite_basico.required' => 'Preencha o limite básico do cliente!',
            'cliente_id.numeric' => 'O id do cliente precisa ser do tipo numérico!',
            'cartao_id.numeric' => 'O id do cartão precisa ser do tipo numérico!',
            'renda_cliente.numeric' => 'A renda do cliente precisa ser um valor numérico!',
            'limite_basico.numeric' => 'O limite básico precisa ser um valor numérico',
            'cliente_id.min' => 'O id do cliente não pode ser igual a zero!',
            'cartao_id.min' => 'O id do cartão não pode ser igual a zero!',
            'renda_cliente.min' => 'A renda do cliente não pode ser igual a zero!',
            'limite_basico.min' => 'O limite básico do cliente não pode ser igual a zero!'
        ]);
        try {
            $solicitarCartao = $this->avaliadorService->solicitarCartao($request->all());
            return (!isset($solicitarCartao['error'])) ?
                response()->json([
                    'status' => 'success',
                    'message' => 'Sua solicitação de cartão passará por uma breve análise!'
                ], 202) :
                response()->json(
                    $solicitarCartao,
                500);
        }
        catch(AMQPIOException $e) {
            return response()->json([
                'status' => 'error_solicit_cards',
                'message' => 'Serviço de solicitação de cartão temporariamente indisponível! Tente novamente em instantes!',
            ], 503);
        }
    }
    //----------------------------------------------------------------------------------------------------------

    // recuperar todos os cartões que foram solicitados pelo id do cliente
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Get(
     *      path="/msavaliador/cliente-cartao/{id_cliente}",
     *      summary="Buscar lista de cartões de crédito registrados junto ao cliente, através do id do cliente ",
     *      description=
     *         "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA 1<sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id_cliente: </b>informar id do cliente.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>para preencher o id, o cliente precisa estar previamente cadastrado no microsserviço MSCLIENTES.</p></li></ul>"
     *      ,
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="id_cliente",
     *          in="path",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(response="200",
     *         description=
     *            "Exibe todos os cartões que foram solicitados pelo cliente<p><b>Pós-condição:</b></p><p>Retorna um json contendo os dados do cliente, e a lista de cartões registrados desse cliente dentro de um array.<p><b>INFORMAÇÃO COMPLEMENTAR: </b>Dentro da resposta do json de cada cartão, serão exibidos o nome do cartão, nome da bandeira, e 3 informações discriminadas importantes de valores que seguem abaixo:</p></p><ul><li><b>renda-cartao: </b>exibe o valor da faixa de renda do cartão de crédito.</li><li><b>renda-cliente: </b>exibe o valor da renda mensal informada pelo cliente.</li><li><b>limite-basico: </b>exibe o valor do limite inicial do cartão concedido a esse cliente.</li></ul>"
     *         ,
     *         @OA\JsonContent(
     *            properties={
     *               @OA\Property(property="status", type="string", example="success"),
     *               @OA\Property(
     *                  property="cliente",
     *                  type="object",
     *                  properties={
     *                     @OA\Property(property="id", type="integer", example=0),
     *                     @OA\Property(property="cpf", type="string", example="99999999999"),
     *                     @OA\Property(property="nome", type="string", example="user"),
     *                     @OA\Property(property="data_nasc", type="string", format="date", example="YYYY-MM-DD"),
     *                     @OA\Property(property="email", type="string", format="email")
     *                  }
     *               ),
     *               @OA\Property(
     *                  property="cartoes",
     *                  type="array",
     *                  items=@OA\Items(
     *                     properties={
     *                        @OA\Property(property="id", type="integer", example=0),
     *                        @OA\Property(property="nome", type="string", example="nome_cartao"),
     *                        @OA\Property(property="bandeira", type="string", example="nome_bandeira"),
     *                        @OA\Property(property="renda-cartao", type="number", example="0000.00"),
     *                        @OA\Property(property="renda-cliente", type="number", example="0000.00"),
     *                        @OA\Property(property="limite-basico", type="number", example="0000.00")
     *                     }
     *                  )
     *               )
     *            }
     *         )
     *      ),
     *      @OA\Response(
     *         response=400,
     *         description="Error: Bad Request | Parâmetro id na requisição inválido",
     *         @OA\JsonContent(
     *            type="object",
     *            @OA\Property(property="status", type="string", example="error"),
     *            @OA\Property(property="message", type="string", example="O id do cliente precisa ser um número válido!")
     *         )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Error: Unauthorized | Token expirado",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="error"),
     *              @OA\Property(property="message", type="string", example="Acesso não autorizado/token expirado!!!")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Error: Not Found | Cliente não encontrado!",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="not_found"),
     *              @OA\Property(property="message", type="string", example="Cliente não encontrado!")
     *          )
     *      ),
     *       @OA\Response(
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviços Clientes-Cartões",
     *                  value={
     *                     "status": "error_client_cards",
     *                     "message": "Serviço temporariamente indisponível. Tente novamente em instantes!"
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
    public function showCardsbyId(Request $request, string $id_cliente) {
        if(!is_numeric($id_cliente)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O id do cliente precisa ser um número válido!'
            ], 400);
        }
        else {
            try {
                $clienteCartoes = $this->avaliadorService->getCardsClientById($id_cliente, $request->bearerToken());
                return ($clienteCartoes) ? 
                    response()->json(
                        $clienteCartoes,
                    200) : 
                    response()->json([
                        'status' => 'not_found',
                        'message' => 'Cliente não encontrado!'
                    ], 404);
            }
            catch(TypeError $e) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Cliente não encontrado!'
                ], 404);
            }
            catch(RequestException $e) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Cliente não encontrado!'
                ], 404);
            }
            catch(ConnectionException $e) {
                return response()->json([
                    'status' => 'error_client_cards',
                    'message' => 'Serviço de cliente-cartões temporariamente indisponível. Tente novamente em instantes!'
                ], 503);
            }
        }
    }

}
