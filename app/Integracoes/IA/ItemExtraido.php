<?php

namespace App\Integracoes\IA;

/**
 * Item lido numa prescrição, antes de qualquer correspondência com o catálogo.
 */
final readonly class ItemExtraido
{
    public function __construct(
        public string $textoOriginal,
        public string $nome,
        public ?string $dosagem,
        public ?string $apresentacao,
        public ?float $quantidade,
    ) {}
}
