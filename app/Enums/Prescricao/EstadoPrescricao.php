<?php

namespace App\Enums\Prescricao;

enum EstadoPrescricao: string
{
    case PENDENTE = 'PENDENTE';
    case EM_ANALISE = 'EM_ANALISE';
    case AGUARDA_REVISAO = 'AGUARDA_REVISAO';
    case APROVADA = 'APROVADA';
    case REJEITADA = 'REJEITADA';
    case ERRO_ANALISE = 'ERRO_ANALISE';
}
