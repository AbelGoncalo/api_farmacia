<?php

namespace App\Enums\Encomenda;

enum EstadoEncomenda: string
{
    case AGUARDANDO_PAGAMENTO = 'AGUARDANDO_PAGAMENTO';
    case PAGA = 'PAGA';
    case EM_PREPARACAO = 'EM_PREPARACAO';
    case ENVIADA = 'ENVIADA';
    case ENTREGUE = 'ENTREGUE';
    case CANCELADA = 'CANCELADA';
}
