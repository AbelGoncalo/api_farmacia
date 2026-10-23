<?php

namespace App\Services\Prescricao;

use App\Repositories\Catalogo\CatalogoRepository;
use Illuminate\Support\Str;

/**
 * Corresponde um item extraído com os produtos do catálogo.
 *
 * A correspondência é apenas uma SUGESTÃO para revisão manual: nunca
 * confirma nem autoriza vendas. A confiança calculada aqui é a de
 * correspondência (itens_prescricao.confianca_correspondencia) e nunca se
 * mistura com a confiança de extração da IA (prescricoes.resultado_ia).
 *
 * Limiares: igualdade exata → exato (1,0); semelhança ≥ 0,9 → aproximado
 * (0,95); semelhança ≥ 0,7 → aproximado (semelhança); abaixo → sem
 * correspondência.
 */
class CorrespondenciaService
{
    private const LIMIAR_ALTO = 0.9;

    private const LIMIAR_BAIXO = 0.7;

    private const CONFIANCA_ALTO = 0.95;

    public function __construct(
        private readonly CatalogoRepository $catalogoRepository,
    ) {}

    public function sugerir(string $nomeMedicamento): SugestaoCorrespondencia
    {
        $normalizado = $this->normalizar($nomeMedicamento);

        if ($normalizado === '') {
            return SugestaoCorrespondencia::semCorrespondencia();
        }

        $palavraChave = $this->palavraChave($normalizado);

        if ($palavraChave === null) {
            return SugestaoCorrespondencia::semCorrespondencia();
        }

        return $this->melhorCorrespondencia($normalizado, $palavraChave);
    }

    private function melhorCorrespondencia(string $normalizado, string $palavraChave): SugestaoCorrespondencia
    {
        $melhor = null;
        $melhorSemelhanca = 0.0;

        foreach ($this->catalogoRepository->candidatosCorrespondencia($palavraChave) as $produto) {
            $semelhanca = $this->semelhanca($normalizado, $this->normalizar($produto->nome));

            if ($semelhanca > $melhorSemelhanca) {
                $melhor = $produto;
                $melhorSemelhanca = $semelhanca;
            }
        }

        if ($melhor === null) {
            return SugestaoCorrespondencia::semCorrespondencia();
        }

        if ($melhorSemelhanca === 1.0) {
            return SugestaoCorrespondencia::sugerida($melhor, 'exato', 1.0);
        }

        if ($melhorSemelhanca >= self::LIMIAR_ALTO) {
            return SugestaoCorrespondencia::sugerida($melhor, 'aproximado', self::CONFIANCA_ALTO);
        }

        if ($melhorSemelhanca >= self::LIMIAR_BAIXO) {
            return SugestaoCorrespondencia::sugerida($melhor, 'aproximado', round($melhorSemelhanca, 2));
        }

        return SugestaoCorrespondencia::semCorrespondencia();
    }

    private function semelhanca(string $textoA, string $textoB): float
    {
        if ($textoA === $textoB) {
            return 1.0;
        }

        if ($textoA === '' || $textoB === '') {
            return 0.0;
        }

        similar_text($textoA, $textoB, $percentagem);

        return round($percentagem / 100, 4);
    }

    private function normalizar(string $texto): string
    {
        $semAcentos = Str::ascii(mb_strtolower($texto));
        $limpo = preg_replace('/[^a-z0-9]+/', ' ', $semAcentos);

        return trim($limpo ?? '');
    }

    /**
     * Palavra-chave (≥ 3 caracteres) para reduzir os candidatos do catálogo.
     */
    private function palavraChave(string $normalizado): ?string
    {
        foreach (explode(' ', $normalizado) as $palavra) {
            if (mb_strlen($palavra) >= 3) {
                return $palavra;
            }
        }

        return null;
    }
}
