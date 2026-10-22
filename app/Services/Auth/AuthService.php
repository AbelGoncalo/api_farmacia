<?php

namespace App\Services\Auth;

use App\Enums\Auth\PapelUtilizador;
use App\Models\User;
use App\Repositories\Auth\AuthRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository,
    ) {}

    /** @param array{nome: string, email: string, telefone?: ?string, nif?: ?string, palavra_passe: string} $dados */
    public function criarConta(array $dados): User
    {
        $dados['papel'] = 'CLIENTE';
        $dados['estado'] = 'ATIVO';

        return $this->authRepository->criarConta($dados);
    }

    /**
     * @return array{token: string, expira_em_segundos: int, cliente: User, papel: string, destino: string}|null
     */
    public function login(string $email, string $palavraPasse): ?array
    {
        $cliente = $this->authRepository->buscarClienteAtivoPorEmail($email);

        if ($cliente === null || ! Hash::check($palavraPasse, $cliente->palavra_passe)) {
            return null;
        }

        $papel = PapelUtilizador::tryFrom($cliente->papel);

        if ($papel === null) {
            return null;
        }

        $guard = Auth::guard('api');

        return [
            'token' => $guard->login($cliente),
            'expira_em_segundos' => $guard->getTTL() * 60,
            'cliente' => $cliente,
            'papel' => $papel->value,
            'destino' => $papel->destino(),
        ];
    }

    /** @param array{nome?: string, email?: string, telefone?: ?string, nif?: ?string} $dados */
    public function atualizarConta(User $cliente, array $dados): User
    {
        return $this->authRepository->atualizarConta($cliente, $dados);
    }

    public function sair(): void
    {
        Auth::guard('api')->logout();
    }
}
