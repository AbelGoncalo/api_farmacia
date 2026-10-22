<?php

namespace App\Repositories\Auth;

use App\Models\User;

class AuthRepository
{
    /** @param array{nome: string, email: string, telefone?: ?string, nif?: ?string, palavra_passe: string, papel: string, estado: string} $dados */
    public function criarConta(array $dados): User
    {
        $cliente = new User;
        $cliente->nome = $dados['nome'];
        $cliente->email = $dados['email'];
        $cliente->telefone = $dados['telefone'] ?? null;
        $cliente->nif = $dados['nif'] ?? null;
        $cliente->palavra_passe = $dados['palavra_passe'];
        $cliente->papel = $dados['papel'];
        $cliente->estado = $dados['estado'];
        $cliente->save();

        return $cliente;
    }

    /** @param array{nome?: string, email?: string, telefone?: ?string, nif?: ?string} $dados */
    public function atualizarConta(User $cliente, array $dados): User
    {
        $cliente->fill($dados);
        $cliente->save();

        return $cliente->refresh();
    }

    public function buscarClienteAtivoPorEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->where('estado', 'ATIVO')
            ->first();
    }
}
