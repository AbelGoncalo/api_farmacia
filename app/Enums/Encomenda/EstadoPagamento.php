<?php

namespace App\Enums\Encomenda;

enum EstadoPagamento: string
{
    case PENDENTE = 'PENDENTE';
    case CONFIRMADO = 'CONFIRMADO';
    case FALHADO = 'FALHADO';
    case REEMBOLSADO = 'REEMBOLSADO';
}
