<?php

namespace App\Jobs;

use App\Services\Prescricao\AnalisePrescricaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Analisa uma prescrição submetida: extração dos itens e correspondência com
 * o catálogo, em segundo plano. Nunca aprova nem autoriza vendas — a decisão
 * é sempre manual por um profissional autorizado.
 */
class AnalisarPrescricao implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $prescricaoId,
    ) {}

    public function handle(AnalisePrescricaoService $servico): void
    {
        $servico->analisar($this->prescricaoId);
    }
}
