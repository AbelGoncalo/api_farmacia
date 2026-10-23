<?php

namespace App\Integracoes\IA;

/**
 * Resultado da extração de uma prescrição.
 *
 * A confiança aqui é a de EXTRAÇÃO e é guardada em prescricoes.resultado_ia.
 * Nunca se mistura com a confiança de correspondência com o catálogo
 * (itens_prescricao.confianca_correspondencia) e não autoriza nada por si só.
 */
final readonly class ResultadoExtracao
{
    /**
     * @param  list<ItemExtraido>  $itens
     * @param  float  $confianca  grau de confiança da extração, entre 0 e 1
     */
    public function __construct(
        public string $provedor,
        public float $confianca,
        public array $itens,
    ) {}
}
