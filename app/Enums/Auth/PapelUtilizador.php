<?php

namespace App\Enums\Auth;

enum PapelUtilizador: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case UTILIZADOR_SISTEMA = 'UTILIZADOR_SISTEMA';
    case CLIENTE = 'CLIENTE';

    public function destino(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'super-admin',
            self::UTILIZADOR_SISTEMA => 'utilizador-sistema',
            self::CLIENTE => 'cliente',
        };
    }
}
