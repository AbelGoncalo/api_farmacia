<?php

namespace App\Enums\Encomenda;

enum TipoEntrega: string
{
    case RECOLHA_LOJA = 'RECOLHA_LOJA';
    case ENTREGA_DOMICILIO = 'ENTREGA_DOMICILIO';
}
