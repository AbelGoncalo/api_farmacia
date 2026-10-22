<?php

namespace App\Policies;

use App\Enums\Auth\PapelUtilizador;
use App\Models\Carrinho;
use App\Models\User;

class CarrinhoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Carrinho $carrinho): bool
    {
        return (int) $carrinho->utilizador_id === (int) $user->id
            || $user->temPapel(PapelUtilizador::SUPER_ADMIN);
    }

    public function update(User $user, Carrinho $carrinho): bool
    {
        return $this->view($user, $carrinho);
    }

    public function delete(User $user, Carrinho $carrinho): bool
    {
        return $this->view($user, $carrinho);
    }
}
