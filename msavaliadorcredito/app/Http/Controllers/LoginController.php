<?php

namespace App\Http\Controllers;

use App\Services\LoginService;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDOException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller {

    private $loginService;

    public function __construct(LoginService $loginService) {
        $this->loginService = $loginService;
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|min:3',
            //'email' => 'required|email|string|max:100',
            'email' => 'required|string|email',
            'password' => 'required|string|min:5|max:100'
        ],
        [
            'required' => 'Preenchimento Obrigatório!',
            'name.min' => 'O nome precisa ter 3 caracteres!',
            'email.email' => 'Formato de email inválido!',
            'password.min' => 'A senha precisa ter 5 caracteres!'
        ]);
        try {
            $newUser = $this->loginService->registerUser($request->all());
            return response()->json([
                'message' => 'success',
                'user' => $newUser
            ], 200);
        }
        catch(UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'Atenção, esse email já foi cadastrado!',
                'error' => $e->getMessage()
            ], 500);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Não foi possível cadastrar esse novo usuário!!!',
                'error' => $e->getMessage()
                ], 500);
        }
    }

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ],
        [
            'required' => 'Preencha todos os campos!',
            'email.email' => 'Formato de email inválido!'
        ]);
        $credentials = $request->only('email', 'password');
        try {
            if(!$token = JWTAuth::attempt($credentials)) {
                return  response()->json(['error' => 'Credenciais inválidas' ], 401);
            }
        }
        catch(JWTException $e) {
            return  response()->json(['error' => 'Não foi possível criar o token' ], 500); 
        }
        return  response()->json([
            'id' => Auth::user()->getAttributes()['id'],
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    public function update(Request $request) {
        $request->validate([
            'id' => 'required|numeric',
            'name' => 'nullable',
            'email' => "nullable|email",
            'password' => 'nullable|min:5'
        ],
        [
            'id.required' => 'O id precisa ser preenchido!',
            'id.numeric' => 'O id precisa ser no formato numérico!',
            'email.email' => 'Formato inválido de email!',
            'password.min' => 'A senha precisa ter 5 caracteres!'
        ]);
        try {
            $user = $this->loginService->updateUser($request->all());
            return ($user) ? response()->json(['message' => 'success', 'user' => $user ], 200) : response()->json(['message' => 'Usuário não encontrado!'], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Não foi possível realizar a atualização desse usuário!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email'
        ],
        [
            'required' => 'Preencha os campos obrigatórios (name e email)!',
            'email.email' => 'Formato de email inválido!'
        ]);
        try {
            $resultado = $this->loginService->resetPassword($request->all());
            return ($resultado) ? response()->json($resultado, 200) : response()->json(['message' => 'Usuário não foi encontrado!'], 404);
        }
        catch(Exception $e) {
             return response()->json([
                'message' => 'Não foi possível resetar a senha. Por favor, tente mais tarde!',
                'error' => $e
            ], 500);
        }
    }

    public function logout() {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        }
        catch(JWTException $e) {
            return response()->json([
                'message' => 'Falha no logout. Por favor, tente mais tarde!',
                'error' => $e
            ], 500);
        }
        return response()->json([
            'message' => 'Usuário foi deslogado com sucesso!!!'
        ], 200);
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|numeric'
        ],
        [
            'required' => 'Id preenchimento obrigatório!',
            'id.numeric' => 'O id precisa ser do tipo numérico!'
        ]);
        try {
            $resultado = $this->loginService->deleteUserById($request->id);
            return ($resultado) ? response(true, 200) : response()->json(['message' => 'Cliente não encontrado!'], 404);
        }
        catch(PDOException $e) {
            return response()->json([
                'message' => 'Erro de comunicação ao banco de dados!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
