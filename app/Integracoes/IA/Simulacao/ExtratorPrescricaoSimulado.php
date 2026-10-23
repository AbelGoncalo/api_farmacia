<?php

namespace App\Integracoes\IA\Simulacao;

use App\Integracoes\IA\ExtratorPrescricao;
use App\Integracoes\IA\ItemExtraido;
use App\Integracoes\IA\ResultadoExtracao;

/**
 * Provedor simulado de extração (decisão 1-C).
 *
 * Em vez de recorrer a um modelo real, devolve um conjunto fixo de itens
 * para permitir desenvolver e testar o fluxo completo. Qualquer provedor
 * real substitui-se neste mesmo contrato, sem tocar no restante código.
 */
final class ExtratorPrescricaoSimulado implements ExtratorPrescricao
{
    private const PROVEDOR = 'simulado';

    private const CONFIANCA_EXTRACAO = 0.9;

    public function extrair(string $conteudo, string $tipoFicheiro): ResultadoExtracao
    {
        if (trim($conteudo) === '') {
            throw new \RuntimeException('Documento vazio.');
        }

        return new ResultadoExtracao(
            provedor: self::PROVEDOR,
            confianca: self::CONFIANCA_EXTRACAO,
            itens: [
                new ItemExtraido(
                    textoOriginal: 'Amoxicilina 500mg - 21 cápsulas',
                    nome: 'Amoxicilina',
                    dosagem: '500mg',
                    apresentacao: 'cápsulas',
                    quantidade: 21,
                ),
                new ItemExtraido(
                    textoOriginal: 'Ibuprofeno 400mg - 20 comprimidos',
                    nome: 'Ibuprofeno',
                    dosagem: '400mg',
                    apresentacao: 'comprimidos',
                    quantidade: 20,
                ),
                new ItemExtraido(
                    textoOriginal: 'Omeprazol 20mg - 28 cápsulas',
                    nome: 'Omeprazol',
                    dosagem: '20mg',
                    apresentacao: 'cápsulas',
                    quantidade: 28,
                ),
            ],
        );
    }
}
