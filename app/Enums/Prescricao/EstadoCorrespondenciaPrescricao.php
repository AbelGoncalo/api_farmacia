<?php

namespace App\Enums\Prescricao;

enum EstadoCorrespondenciaPrescricao: string
{
    case PENDENTE = 'PENDENTE';
    case CORRESPONDENCIA_SUGERIDA = 'CORRESPONDENCIA_SUGERIDA';
    case CONFIRMADA = 'CONFIRMADA';
    case SEM_CORRESPONDENCIA = 'SEM_CORRESPONDENCIA';
}
