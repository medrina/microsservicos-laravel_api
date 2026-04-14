<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginService {

    public function registerUser(array $request): array {
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password'])
        ]);
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ];
    }

    public function updateUser(array $request): ?array {
        $user = User::find($request['id']);
        if(!$user) return null;
        else {
            $user->update([
                'name' => (isset($request['name'])) ? $request['name'] : $user->name,
                'email' => (isset($request['email'])) ? $request['email'] : $user->email,
                'password' => (isset($request['password'])) ? $request['password'] : $user->password
            ]);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];
        }
    }

    public function resetPassword(array $dados): ?array {
        //dd($dados);
        $user = User::all()
            ->where('name', '==', $dados['name'])
            ->where('email', '==', $dados['email'])
            ->first();
        if(!$user) return null;
        else {
            $novaSenhaAleatoria = Str::password(10, true, true);
            $user->update([
                'password' => $this->gerarNovaSenha($novaSenhaAleatoria)
            ]);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $novaSenhaAleatoria
            ];
        }
    }

    public function deleteUserById(int $id): bool {
        $user = User::find($id);
        if(!$user)
            return false;
        else {
            $user->delete();
            return true;
        }
    }

    private function gerarNovaSenha(string $novaSenha): string {
        return Hash::make($novaSenha);
    }

}