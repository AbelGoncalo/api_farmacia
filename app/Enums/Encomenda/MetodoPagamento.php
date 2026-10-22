<?php

namespace App\Enums\Encomenda;

enum MetodoPagamento: string
{
    case NUMERARIO = 'NUMERARIO';
    case MULTIBANCO = 'MULTIBANCO';
    case TPA = 'TPA';
    case TRANSFERENCIA = 'TRANSFERENCIA';
}
