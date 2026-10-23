<?php

namespace App\Services\Prescricao;

use App\Enums\Prescricao\EstadoCorrespondenciaPrescricao;
use App\Models\Produto;

/**
 * Sugestão de correspondência entre um item extraído e o catálogo.
 *
 * É apenas uma SUGESTÃO para revisão manual: nunca confirma nem autoriza
 * nada. A confiança é a de correspondência com o catálogo (não a de
 * extração da IA) e é guardada em itens_prescricao.confianca_correspondencia.
 */
final readonly class SugestaoCorrespondencia
{
    private function __construct(
        public ?Produto $produto,
        public EstadoCorrespondenciaPrescricao $estado,
        public ?string $metodo,
        public ?float $confianca,
    ) {}

    public static function semCorrespondencia(): self
    {
        return new self(
            produto: null,
            estado: EstadoCorrespondenciaPrescricao::SEM_CORRESPONDENCIA,
            metodo: null,
            confianca: null,
        );
    }

    public static function sugerida(Produto $produto, string $metodo, float $confianca): self
    {
        return new self(
            produto: $produto,
            estado: EstadoCorrespondenciaPrescricao::CORRESPONDENCIA_SUGERIDA,
            metodo: $metodo,
            confianca: $confianca,
        );
    }
}
