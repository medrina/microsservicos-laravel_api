<?php

namespace App\Http\Controllers;

use App\Services\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller {

    private $loginService;

    public function __construct(LoginService $loginService) {
        $this->loginService = $loginService;
    }

    //----------------------------------------------------------------------------------------------------------
    // cadastrar novo usuário na API
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msavaliador/user/register",
     *    summary="Cadastra um novo usuário Administrador. Esse cadastro possibilita a esse usuário realizar login, para se autenticar na API",
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p>preencher o json no corpo da requisição no formato de objeto { name, email, password } .</p></li><li><p><b>NOTA 1 <sup>email</sup>: </b>não é possível cadastrar um email já existente para um novo usuário Administrador.</p></li><li><p><b>NOTA 2 <sup>email</sup>: </b>o email deve obedecer o seguinte formato de email: <b>user@email.com .</b></p></li><li><p><b>NOTA 3 <sup>password</sup>: </b>o password deve ter no mínimo 5 caracteres.</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"name","email", "password"},
     *          @OA\Property(property="name", type="string", example="user"),
     *          @OA\Property(property="email", type="string", format="email"),
     *          @OA\Property(property="password", type="string", format="password", example="")
     *       )
     *    ),
     *    @OA\Response(
     *       response="201",
     *       description=
     *          "Cadastro de usuário Administrador criado com sucesso!<p><b>Pós-condição:</b></p><p>Nome e email de usuário administrador cadastrados e persistidos na base de dados do microsserviço MSAVALIADOR.</p><p>Usuário Administrador está habilitado a executar o endpoint de login: <b>POST - Efetuar login de usuário Administrador... </b> no microsserviço MSAVALIADOR.</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="message", type="string", example="Cadastro criado com sucesso!"),
     *          @OA\Property(
     *             property="user",
     *             type="object",
     *             @OA\Property(property="name", type="string", example="user"),
     *             @OA\Property(property="email", type="email", example="user@email.com")
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=409,
     *       description="Error: Conflict | Não é possível cadastrar um email que já foi cadastrado!",
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
     *          @OA\Property(property="errors", type="object"),
     *          examples={
     *             @OA\Examples(
     *                example="preencha_nome",
     *                summary="Erro no nome",
     *                value={
     *                   "message": {"Cadastre nome de usuário!"},
     *                   "errors": {
     *                      "nome": {"Cadastre nome de usuário!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="email_invalido",
     *                summary="Erro no e-mail",
     *                value={
     *                   "message": {"Cadastre email para esse usuário!", "Formato de email inválido!"},
     *                   "errors": {
     *                      "email": {"Cadastre email para esse usuário!", "Formato de email inválido!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="senha_em_branco",
     *                summary="Erro na senha",
     *                value={
     *                   "message": {"Cadastre uma senha de usuário!", "A senha precisa ter 5 caracteres!"},
     *                   "errors": {
     *                      "password": {"Cadastre uma senha de usuário!", "A senha precisa ter 5 caracteres!"}
     *                   }
     *                }
     *             )
     *          }
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviço de criar usuário Administrador indisponível!",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Usuários",
     *                  value={
     *                     "status": "error_users",
     *                     "message": "Serviço de usuário temporariamente indisponível. Tente novamente em instantes!"
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
     *       )
     *    )
     * )
     */
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:5|max:100'
        ],
        [
            'name.required' => 'Cadastre nome de usuário!',
            'email.required' => 'Cadastre email para esse usuário!',
            'password.required' => 'Cadastre uma senha de usuário!',
            'email.email' => 'Formato de email inválido!',
            'password.min' => 'A senha precisa ter 5 caracteres!'
        ]);
        $newUser = $this->loginService->registerUser($request->all());
        return response()->json([
            'status'=> 'success',
            'message' => 'Cadastro criado com sucesso!',
            'user' => $newUser
        ], 201);
    }
    //----------------------------------------------------------------------------------------------------------

    // efetuar login na API
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msavaliador/user/login",
     *    summary="Efetuar login de usuário Administrador (obtém token válido de duração de 1h)",
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p>usuário administrador precisa ter executado o endpoint de cadastro de usuário administrador: <b>POST - Cadastra um novo usuário Administrador...</b> no microsserviço MSAVALIADOR.</p></li><li><p><b>NOTA: </b>informar email e senha de usuário administrador que já esteja cadastrado no microsserviço MSAVALIADOR.</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"email", "password"},
     *          @OA\Property(property="email", type="string", format="email"),
     *          @OA\Property(property="password", type="string", format="password", example="")
     *       )
     *    ),
     *    @OA\Response(response="200",
     *       description=
     *          "<p>Login efetuado com sucesso!</p><p><b>Pós-condição:</b></p><p>A resposta irá retornar o id do usuário autorizado, e irá gerar um token de usuário autenticado, para poder acessar os endpoints protegidos dos 3 microsserviços (MSAVALIADOR, MSCLIENTES e MSCARTOES).</p><p><b>NOTA <sup>expires_in</sup>: </b>O tempo do token irá vir discriminado no atributo <i>expires_in</i>. Duração do token de acesso: 3600s equivale a 1h.</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="id", type="number", example="0"),
     *          @OA\Property(property="token", type="string", example="token_de_acesso"),
     *          @OA\Property(property="expires_in", type="number", example="3600")
     *       )          
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Error: Unauthorized | Email ou senha são inválidas",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="Credenciais inválidas")
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
     *                example="email_invalido",
     *                summary="Erro no e-mail",
     *                value={
     *                   "message": {"Formato de email inválido!", "Preencha o email!"},
     *                   "errors": {
     *                      "email": {"Formato de email inválido!", "Preencha o email!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="senha_em_branco",
     *                summary="Erro na senha",
     *                value={
     *                   "message": "Preencha a senha!",
     *                   "errors": {
     *                      "password": {"Preencha a senha!"}
     *                   }
     *                }
     *             )
     *          }
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviço de verificação de senhas indisponível!",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Usuários",
     *                  value={
     *                     "status": "error_users",
     *                     "message": "Serviço de usuário temporariamente indisponível. Tente novamente em instantes!"
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
     *       )
     *    )
     * )
     */
    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ],
        [
            'email.required' => 'Preencha o email!',
            'password.required' => 'Preencha a senha!',
            'email.email' => 'Formato de email inválido!'
        ]);
        $credentials = $request->only('email', 'password');
        if(!$token = JWTAuth::attempt($credentials)) {
            return  response()->json([
                'status' => 'error',
                'message' => 'Credenciais inválidas' ], 401);
        }
        return response()->json([
            'status' => 'success',
            'id' => Auth::user()->getAttributes()['id'],
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 200);
    }
    //----------------------------------------------------------------------------------------------------------

    // atualizar dados e/ou senha de usuário
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Patch(
     *    path="/msavaliador/user/update/{id}",
     *    summary="Atualizar dados (parciais) de usuário Administrador",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>id: </b>preenchimento do id do usuário administrador.</p></li><li><p><b>NOTA 1 <sup>id</sup>: </b>o id do usuário administrador é obrigatório o seu preenchimento.</p></li><li><p><b>NOTA 2 <sup>id</sup>: </b>para preencher o id, o usuário administrador precisa estar previamente cadastrado no microsserviço MSAVALIADOR.</p></li></ul>"
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
     *          "<p><b>Pré-condição:</b></p><ul><li><p>preenchimento de forma parcial do json no corpo da requisição no formato objeto { name, email, password } .</p></li><li><p><b>NOTA 1: </b>o preenchimento dos atributos do json não são obrigatórios. Entretanto, se forem preenchidos para serem alterados/atualizados, a API irá aplicar as regras de validação em cada atributo.</p></li><li><p><b>NOTA 2 <sup>email</sup>: </b>para atualizar o email, ele deve obedecer o seguinte formato de email: <b>user@email.com .</b></p></li><li><p><b>NOTA 3 <sup>password</sup>: </b>se o password for preenchido, ele será persistido na base de dados, porém, o novo password <u>NÃO</u> será retornado no json de resposta.</p></li><li><p><b>NOTA 4 <sup>password</sup>: </b>o novo password deve conter no mínimo 5 caracteres.</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"name", "email", "password"},
     *          @OA\Property(property="name", type="string", example=""),
     *          @OA\Property(property="email", type="string", example=""),
     *          @OA\Property(property="password", type="string", example="")
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Dados atualizados com sucesso<p><b>Pós-condição</b></p><p>Será retornado um json de resposta contendo os dados do usuário Administrador (exceto o password do usuário).</p><p><b>NOTA: </b>Havendo ou não o preenchimento dos atributos na Pré-condição, o json de retorno será retornado contendo os dados salvos na base de dados do microsserviço MSAVALIADOR (como mostra o exemplo de retorno do json abaixo).</p>"
     *       ,
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(
     *             property="user",
     *             type="object",
     *             @OA\Property(property="id", type="number", example="0"),
     *             @OA\Property(property="name", type="string", example="user"),
     *             @OA\Property(property="email", type="email", example="user@email.com")
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Error: Bad Request | Parâmetro id do usuário inválido",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="error"),
     *          @OA\Property(property="message", type="string", example="O id do usuário precisa ser um número válido!")
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
     *       response=404,
     *       description="Error: Not Found | Usuário Administrador não encontrado!",
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Usuário não encontrado!")
     *       )
     *    ),
     *    @OA\Response(
     *       response=409,
     *       description="Error: Conflict | Não é possível atualizar um email que já foi cadastrado!",
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
     *          @OA\Property(property="errors", type="object"),
     *          examples={
     *             @OA\Examples(
     *                example="email_invalido",
     *                summary="Erro no e-mail",
     *                value={
     *                   "message": {"Formato de email inválido!"},
     *                   "errors": {
     *                      "email": {"Formato de email inválido!"}
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (atualizar usuários e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Usuários",
     *                  value={
     *                     "status": "error_users",
     *                     "message": "Serviço de usuário temporariamente indisponível. Tente novamente em instantes!"
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
     *       )
     *    )
     * )
     */
    public function update(Request $request, $id) {
        if(!is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'O id do usuário precisa ser um número válido!'
            ], 400);
        }
        $request->validate([
            'name' => 'nullable',
            'email' => "nullable|email",
            'password' => 'nullable|min:5'
        ],
        [
            'email.email' => 'Formato inválido de email!',
            'password.min' => 'A senha precisa ter 5 caracteres!'
        ]);
        $user = $this->loginService->updateUser($request->all(), $id);
        return ($user) ?
            response()->json([
                'status' => 'success',
                'user' => $user
            ], 200) :
            response()->json([
                'status' => 'not_found',
                'message' => 'Usuário não encontrado!'
            ], 404);
    }
    //----------------------------------------------------------------------------------------------------------

    // resetar senha de usuário
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msavaliador/user/reset-password",
     *    summary="Gerar nova senha aleatória de usuário Administrador",
     *    @OA\RequestBody(
     *       required=true,
     *       description=
     *          "<p><b>Pré-condição:</b></p><ul><li><p>preencher o json no corpo da requisição no formato de objeto { name, email } .</p></li><li><p><b>NOTA 1<sup>name</sup>: </b>o name do usuário Administrador, deve estar previamente cadastrado na base de dados do microsserviço MSAVALIADOR.</p></li><li><p><b>NOTA 2<sup>email</sup>: </b>o email do usuário Administrador, deve estar previamente cadastrado na base de dados do microsserviço MSAVALIADOR.</p></li><li><p><b>NOTA 3 <sup>email</sup>: </b>para o preenchimento do email, ele deve obedecer o seguinte formato de email: <b>user@email.com</b> .</p></li></ul>"
     *       ,
     *       @OA\JsonContent(
     *          required={"name", "email"},
     *          @OA\Property(property="name", type="string", example="user"),
     *          @OA\Property(property="email", type="string", format="email", example="user@email.com")
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description=
     *          "Nova senha gerada com sucesso!<p><b>Pós-condição:</b></p><p>Será retornado um json de resposta contendo name, email e uma nova senha/password gerado aleatoriamente pela API.</p><p><b>INFORMAÇÃO COMPLEMENTAR: </b>Com esse novo password gerado aleatoriamente pela API, o usuário Administrador pode:</p><p><b>1) </b> realizar login informando o seu email e a nova senha gerada aleatoriamente, acessando o endpoint: <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR, obtendo o id do seu usuário Administrador, e também o token de acesso,</p><p><b>2) </b>atualizar dados de usuário Administrador informando o seu id e o token de acesso no endpoint: <b>PATCH - Atualizar dados (parciais) de usuário Administrador</b> no microsserviço MSAVALIADOR, e atualizar um novo password de sua preferência.</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="name", type="string", example="user"),
     *          @OA\Property(property="email", type="string", format="email"),
     *          @OA\Property(property="password", type="string", example="senha_gerada_aleatória")
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error: Not Found | Usuário Administrador não encontrado!",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="not_found"),
     *          @OA\Property(property="message", type="string", example="Usuário não encontrado!")
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
     *                example="name_invalido",
     *                summary="Erro no nome",
     *                value={
     *                   "message": {"Preencha o nome!"},
     *                   "errors": {
     *                      "email": {"Preencha o nome!"}
     *                   }
     *                }
     *             ),
     *             @OA\Examples(
     *                example="email_invalido",
     *                summary="Erro no e-mail",
     *                value={
     *                   "message": {"Preencha o email!", "Formato de email inválido!"},
     *                   "errors": {
     *                      "email": {"Preencha o email!", "Formato de email inválido!"}
     *                   }
     *                }
     *             )
     *          }
     *       )
     *    ),
     *    @OA\Response(
     *       response=503,
     *       description="Error: Service Unavailable | Serviço de resetar senhas indisponível!",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Usuários",
     *                  value={
     *                     "status": "error_users",
     *                     "message": "Serviço de usuário temporariamente indisponível. Tente novamente em instantes!"
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
     *       )
     *    )
     * )
     */
    public function resetPassword(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email'
        ],
        [
            'name.required' => 'Preencha o nome!',
            'email.required' => 'Preencha o email',
            'email.email' => 'Formato de email inválido!'
        ]);
        $resultado = $this->loginService->resetPassword($request->all());
        return ($resultado) ?
            response()->json($resultado, 200) :
            response()->json([
                'status' => 'not_found',
                'message' => 'Usuário não encontrado!'
            ], 404);
    }
    //----------------------------------------------------------------------------------------------------------
    
    // logout de usuário administrador utilizando Redis
    // DOCUMENTAÇÃO SWAGGER
    /**
     * @OA\Post(
     *    path="/msavaliador/user/logout",
     *    summary="Deslogar usuário Administrador. Realizar logout da API (invalidar token de acesso)",
     *    description=
     *       "<p><b>Pré-condição:</b></p><ul><li><p><b>token: </b>usuário Administrador deve estar autenticado, e possuir um token válido (obtido no endpoint <b>POST - Efetuar login de usuário Administrador...</b> no microsserviço MSAVALIADOR).</p></li><li><p><b>NOTA <sup>token</sup>: </b>o token deverá ser preenchido no ícone do cadeado desse endpoint. Após preencher o token no campo <i>Value</i>, clique no botão <i>Authorize</i> e esse endpoint estará autorizado para a plena execução.</p></li><li><p><b>INFORMAÇÃO COMPLEMENTAR: </b>esse endpoint não exige parâmetros!</p></li></ul>"
     *    ,
     *    security={{"bearerAuth": {}}},
     *    @OA\Response(
     *       response=200,
     *       description=
     *          "Logout efetuado com sucesso!<p><b>Pós-condição: </b></p><p>Invalida token de acesso, impedindo desse usuário administrador acessar os endpoints protegidos de todos os microsserviços.</p>"
     *       ,
     *       @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="message", type="string", example="Logout realizado com sucesso!")
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
     *       description="Error: Service Unavailable | Serviços externos indisponíveis (serviço de autenticação e cache)",
     *       @OA\JsonContent(
     *            examples={
     *               @OA\Examples(
     *                  example="servico_dados",
     *                  summary="Erro Serviço Usuários",
     *                  value={
     *                     "status": "error",
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
    public function logout(Request $request) {
        
        // pega o token do cabeçalho Authorization da requisição
        $token = $request->bearerToken();

        if($token) {
            
            // cria uma assinatura única para a chave
            $redisKey = 'jwt_blacklist'. md5($token);

            // descobrir quanto tempo falta para o token expirar (em segundos)
            // se o seu pacote JWT mão der esse número, você pode decodificar o payload e ler o 'exp'
            $tempoRestanteSegundos = 3600; // 3600 => 1 hora

            // salva o Redis com expiração automática (SETEX)
            Redis::setex($redisKey, $tempoRestanteSegundos, 'invalidado');
        }

        // retorna
        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso!'
        ], 200);
    }

}
