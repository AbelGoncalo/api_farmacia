<?php

namespace App\Policies;

use App\Enums\Auth\PapelUtilizador;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPapel(PapelUtilizador::SUPER_ADMIN, PapelUtilizador::UTILIZADOR_SISTEMA);
    }

    public function view(User $user, User $modelo): bool
    {
        return (int) $user->id === (int) $modelo->id
            || $user->temPapel(PapelUtilizador::SUPER_ADMIN);
    }

    public function update(User $user, User $modelo): bool
    {
        return (int) $user->id === (int) $modelo->id
            || $user->temPapel(PapelUtilizador::SUPER_ADMIN);
    }

    public function delete(User $user, User $modelo): bool
    {
        return $user->temPapel(PapelUtilizador::SUPER_ADMIN);
    }
}
