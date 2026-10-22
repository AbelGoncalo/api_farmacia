<?php

namespace App\Enums\Encomenda;

enum EstadoEntrega: string
{
    case PENDENTE = 'PENDENTE';
    case EM_TRANSPORTE = 'EM_TRANSPORTE';
    case ENTREGUE = 'ENTREGUE';
}
